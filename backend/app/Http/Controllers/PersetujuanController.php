<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; // Gunakan Request standar
use App\Models\Request as RequestCuti;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PersetujuanController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Security Check: Return JSON 403 jika bukan hak aksesnya
        if (!in_array($user->role, ['Manager', 'HRD'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // --- 1. DATA PERMINTAAN REKOMENDASI (Status: PENDING) ---
        $recommendations = RequestCuti::with(['user', 'leave'])
            ->where('status', 'PENDING')
            ->where(function($query) use ($user) {
                // KONDISI A: Bawahan langsung
                $query->where('manager_nik', $user->nik);

                // KONDISI B: Peer Review sesama HRD
                if ($user->role === 'HRD') {
                    $query->orWhere(function($subQuery) use ($user) {
                        $subQuery->whereNull('manager_nik')
                            ->whereHas('user', function($q) use ($user) {
                                $q->where('role', 'HRD')
                                  ->where('nik', '!=', $user->nik);
                            });
                    });
                }
            })
            ->orderBy('tglRequest', 'asc')
            ->get()
            ->map(function ($req) {
                return $this->formatRequestData($req);
            });

        // --- 2. DATA PERMINTAAN PERSETUJUAN (Status: RECOMMENDED) ---
        $approvals = [];
        
        if ($user->role === 'HRD') {
            $approvals = RequestCuti::where('status', 'RECOMMENDED')
                ->where('user_nik', '!=', $user->nik)
                ->with(['user', 'leave', 'approver'])
                ->orderBy('tglRequest', 'asc')
                ->get()
                ->map(function ($req) {
                    return $this->formatRequestData($req);
                });
        }

        // RETURN JSON
        return response()->json([
            'recommendationRequests' => $recommendations,
            'approvalRequests' => $approvals,
        ]);
    }

    public function update(Request $request, $id)
    {
        // Cari data manual karena Route Model Binding API kadang butuh penyesuaian
        $cutiRequest = RequestCuti::findOrFail($id);

        $validated = $request->validate([
            'action' => 'required|in:recommend,approve,reject',
            'catatan' => 'required_if:action,reject|nullable|string',
        ]);

        $action = $validated['action'];
        $catatan = $validated['catatan'] ?? null;

        if ($action === 'reject') {
            $cutiRequest->status = 'REJECTED';
            $cutiRequest->catatanManajer = $catatan;
            $cutiRequest->tglApproval = now(); 
        } 
        elseif ($action === 'recommend') {
            $cutiRequest->status = 'RECOMMENDED';
            // Simpan siapa yang merekomendasi
            $cutiRequest->manager_nik = Auth::user()->nik; 
        } 
        elseif ($action === 'approve') {
            $cutiRequest->status = 'APPROVED';
            $cutiRequest->tglApproval = now(); 
        }

        $cutiRequest->save();

        return response()->json([
            'message' => 'Status pengajuan berhasil diperbarui.',
            'data' => $cutiRequest
        ]);
    }

    private function formatRequestData($req)
    {
        Carbon::setLocale('id');
        
        // Handle Lampiran URL (Supabase/S3 atau Local)
        $lampiranUrl = null;
        if ($req->lampiran) {
            // Cek apakah sudah link external (URL lengkap)
            if (filter_var($req->lampiran, FILTER_VALIDATE_URL)) {
                $lampiranUrl = $req->lampiran;
            } else {
                // Build Supabase public URL
                // Format dari .env: AWS_URL=https://xxx.supabase.co/storage/v1/object/public/bucket
                $baseUrl = env('AWS_URL'); // Sudah include bucket dari .env
                
                if ($baseUrl) {
                    // Pastikan baseUrl tidak diakhiri dengan slash
                    $baseUrl = rtrim($baseUrl, '/');
                    // Pastikan path tidak diawali dengan slash
                    $path = ltrim($req->lampiran, '/');
                    
                    $lampiranUrl = "{$baseUrl}/{$path}";
                } else {
                    // Fallback: gunakan path mentah jika env tidak ada
                    $lampiranUrl = $req->lampiran;
                }
            }
        }

        return [
            'id' => $req->id,
            'nama_pemohon' => $req->user->nama,
            'nik_pemohon' => $req->user->nik,
            'departemen' => $req->user->departemen,
            'nama_cuti' => $req->leave->namaCuti,
            'tgl_pengajuan' => $req->tglRequest->translatedFormat('l, d-m-Y H.i'),
            'tgl_mulai' => $req->tglMulai->translatedFormat('l, d-m-Y'),
            'tgl_selesai' => $req->tglSelesai->translatedFormat('l, d-m-Y'),
            'durasi' => $req->tglMulai->diffInDays($req->tglSelesai) + 1,
            'alasan' => $req->alasan,
            'lampiran' => $lampiranUrl,
            'status' => $req->status,
            'rekomendasi_oleh' => $req->approver->nama ?? '-', 
        ];
    }
}