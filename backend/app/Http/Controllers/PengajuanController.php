<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request as HttpRequest;
use App\Models\Leave;
use App\Models\Holiday;
use App\Models\Request as RequestCuti; 
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PengajuanController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Hitung Sisa Cuti
        $jatahCuti = 12; 
        $cutiTerpakai = RequestCuti::where('user_nik', $user->nik)
            ->where('status', 'APPROVED')
            ->whereHas('leave', function ($query) {
                $query->where('namaCuti', 'like', '%Tahunan%'); 
            })
            ->get()
            ->sum(function ($req) {
                $start = Carbon::parse($req->tglMulai);
                $end = Carbon::parse($req->tglSelesai);
                return $start->diffInDays($end) + 1;
            });
        
        $sisaCuti = max(0, $jatahCuti - $cutiTerpakai);

        return response()->json([
            'leaves' => Leave::all(), 
            'holidays' => Holiday::where('tgl', '>=', Carbon::today())->get(),
            'sisaCuti' => $sisaCuti,
        ]);
    }

    public function store(HttpRequest $request)
    {
        $rules = [
            'leave_id' => 'required|exists:leaves,id',
            'tglMulai' => 'required|date|after_or_equal:today',
            'tglSelesai' => 'required|date|after_or_equal:tglMulai',
            'alasan' => 'required|string|max:255',
        ];

        $jenisCuti = Leave::find($request->leave_id);
        
        if ($jenisCuti && stripos($jenisCuti->namaCuti, 'Cuti Tahunan') === false) {
            $rules['lampiran'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        } else {
            $rules['lampiran'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }

        $validated = $request->validate($rules);

        $start = Carbon::parse($validated['tglMulai']);
        $end = Carbon::parse($validated['tglSelesai']);

        // --- PERUBAHAN: Validasi Min Notice Days (H-5 Hari Kerja) ---
        $noticeDays = $jenisCuti->minNoticeDay ?? 0; 
        
        if ($noticeDays > 0) {
            $minAllowedDate = Carbon::today()->addWeekdays($noticeDays);
            
            if ($start->lessThan($minAllowedDate)) {
                return response()->json([
                    'errors' => [
                        'tglMulai' => ["Pengajuan {$jenisCuti->namaCuti} harus dilakukan minimal {$noticeDays} hari kerja sebelumnya."]
                    ]
                ], 422);
            }
        }

        // Validasi Max Hari
        $durasi = $start->diffInDays($end) + 1;

        if ($jenisCuti->maxHari && $durasi > $jenisCuti->maxHari) {
            if (stripos($jenisCuti->namaCuti, 'Cuti Tahunan') === false) {
                 return response()->json(['errors' => ['tglSelesai' => ["Durasi melebihi batas maksimal ({$jenisCuti->maxHari} hari)."]]], 422);
            }
        }

        // Validasi Holiday
        $holidayCount = Holiday::whereBetween('tgl', [$validated['tglMulai'], $validated['tglSelesai']])->count();
        if ($holidayCount > 0) {
            return response()->json(['errors' => ['tglMulai' => ["Tanggal merah tidak boleh diajukan."]]], 422);
        }

        // Validasi Masa Kerja (Cuti Tahunan)
        if ($jenisCuti && stripos($jenisCuti->namaCuti, 'Cuti Tahunan') !== false) {
            $tglMasuk = Auth::user()->tglMasuk;
            $tglMasukDate = Carbon::parse($tglMasuk);
            if ($tglMasukDate->diffInYears(Carbon::today()) < 1) {
                 return response()->json(['errors' => ['leave_id' => ["Hak Cuti Tahunan setelah 1 tahun kerja."]]], 422);
            }
        }

        $path = null;
        if ($request->hasFile('lampiran')) {
            $path = $request->file('lampiran')->store('lampiran', 'supabase'); 
        }

        RequestCuti::create([
            'user_nik' => Auth::user()->nik,
            'leave_id' => $validated['leave_id'],
            'tglRequest' => now(),
            'tglMulai' => $validated['tglMulai'],
            'tglSelesai' => $validated['tglSelesai'],
            'alasan' => $validated['alasan'],
            'lampiran' => $path,
            'status' => 'PENDING',
            'manager_nik' => Auth::user()->manager_nik, 
        ]);

        return response()->json(['message' => 'Pengajuan cuti berhasil dikirim.']);
    }

    public function cancel($id)
    {
        $request = RequestCuti::findOrFail($id);

        if ($request->user_nik !== Auth::user()->nik) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (in_array($request->status, ['REJECTED', 'CANCELLED'])) {
            return response()->json(['message' => 'Pengajuan sudah tidak aktif.'], 400);
        }

        $today = Carbon::now();
        $tglMulai = Carbon::parse($request->tglMulai);
        $cutoffTime = $tglMulai->copy()->setTime(8, 0, 0); 

        if ($today->gt($tglMulai->endOfDay())) {
            return response()->json(['message' => 'Tidak dapat membatalkan cuti yang sudah berlalu.'], 400);
        }

        if ($today->isSameDay($tglMulai) && $today->gt($cutoffTime)) {
            return response()->json(['message' => 'Pembatalan pada hari H maksimal pukul 08:00 pagi.'], 400);
        }

        $request->update(['status' => 'CANCELLED']);

        return response()->json(['message' => 'Pengajuan cuti berhasil dibatalkan.']);
    }
}