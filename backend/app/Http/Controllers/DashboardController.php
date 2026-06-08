<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Holiday;
use App\Models\Request as RequestCuti;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // 1. HITUNG STATISTIK (SISA CUTI)
        $jatahCuti = 12;
        
        $tglMasuk = $user->tglMasuk;
        $eligible = false;
        
        if ($tglMasuk instanceof Carbon || $tglMasuk instanceof \Illuminate\Support\Carbon) {
            $yearsOfService = $tglMasuk->diffInYears(Carbon::today());
            $eligible = $yearsOfService >= 1;
        }
        
        if (!$eligible) {
            $jatahCuti = 0;
        }
        
        $cutiTerpakai = RequestCuti::where('user_nik', $user->nik)
            ->where('status', 'APPROVED')
            ->whereHas('leave', function ($query) {
                $query->where('namaCuti', 'like', '%Tahunan%'); 
            })
            ->get()
            ->sum(function ($req) {
                return $req->tglMulai->diffInDays($req->tglSelesai) + 1;
            });
        
        $sisaCuti = max(0, $jatahCuti - $cutiTerpakai);
        
        $leaveStats = [
            'remaining' => $sisaCuti,
            'used' => $cutiTerpakai,
            'pendingRecommendation' => 0,
            'pendingApproval' => 0,
        ];
        
        if (in_array($user->role, ['Manager', 'HRD'])) {
            $leaveStats['pendingRecommendation'] = RequestCuti::where('status', 'PENDING')
                ->where(function($query) use ($user) {
                    $query->where('manager_nik', $user->nik);

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
                ->count();
        }
        
        if ($user->role === 'HRD') {
            $leaveStats['pendingApproval'] = RequestCuti::where('status', 'RECOMMENDED')
                ->where('user_nik', '!=', $user->nik)
                ->count();
        }

        // 2. RIWAYAT DATA
        $leaveHistory = RequestCuti::where('user_nik', $user->nik)
            ->with(['leave', 'user.manager'])
            ->orderBy('tglRequest', 'desc') 
            ->get()
            ->map(function ($req) use ($sisaCuti) {
                Carbon::setLocale('id');

                return [
                    'id' => $req->id,
                    'tgl_pengajuan_format' => $req->tglRequest->translatedFormat('l, d-m-Y'),
                    'date' => $req->tglMulai->translatedFormat('l, d-m-Y'),
                    'tgl_mulai_raw' => $req->tglMulai->format('Y-m-d'), 
                    'nama_pemohon' => $req->user->nama,
                    'departemen' => $req->user->departemen,
                    'nama_cuti' => $req->leave->namaCuti ?? '-', 
                    'tgl_mulai' => $req->tglMulai->translatedFormat('l, d-m-Y'),
                    'tgl_selesai' => $req->tglSelesai->translatedFormat('l, d-m-Y'),
                    'durasi' => $req->tglMulai->diffInDays($req->tglSelesai) + 1,
                    'alasan' => $req->alasan,
                    'lampiran' => $req->lampiran ? config('filesystems.disks.supabase.url') . '/' . $req->lampiran : null,
                    'status' => $req->status,
                    'sisa_cuti_saat_ini' => $sisaCuti,
                    'approver' => $req->user->manager->nama ?? '-',
                    'catatan_manager' => $req->catatanManajer 
                ];
            });
        
        // 3. LIBUR NASIONAL
        $today = Carbon::today();
        $nationalHolidays = Holiday::where('tgl', '>=', $today)
            ->orderBy('tgl', 'asc')
            ->take(5)
            ->get()
            ->map(function ($holiday) {
                $date = Carbon::parse($holiday->tgl);
                return [
                    'date' => $date->translatedFormat('l, d-m-Y'),
                    'description' => $holiday->deskripsi,
                ];
            });
        
        // RETURN JSON
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'leaveStats' => $leaveStats,
                'leaveHistory' => $leaveHistory,
                'nationalHolidays' => $nationalHolidays,
            ]
        ]);
    }
}