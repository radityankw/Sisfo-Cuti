<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Holiday;
use App\Models\Request as RequestCuti;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

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

        // ====== PERUBAHAN: gabung pendingRecommendation & pendingApproval ======
        // Sebelumnya ini 2 query COUNT terpisah (masing-masing 1 round-trip ke Supabase).
        // Sekarang digabung jadi 1 query pakai conditional aggregation (SUM CASE WHEN),
        // jadi cuma 1 round-trip untuk dapat kedua angka sekaligus.
        if (in_array($user->role, ['Manager', 'HRD'])) {
            $isHrd = $user->role === 'HRD';

            $counts = RequestCuti::selectRaw(
                "SUM(CASE WHEN status = 'PENDING' AND (
                        manager_nik = ?
                        " . ($isHrd ? "OR (manager_nik IS NULL AND EXISTS (
                            SELECT 1 FROM users
                            WHERE users.nik = requests.user_nik
                            AND users.role = 'HRD'
                            AND users.nik != ?
                        ))" : "") . "
                    ) THEN 1 ELSE 0 END) as pending_recommendation,
                    SUM(CASE WHEN status = 'RECOMMENDED' AND user_nik != ? THEN 1 ELSE 0 END) as pending_approval",
                $isHrd
                    ? [$user->nik, $user->nik, $user->nik]
                    : [$user->nik, $user->nik]
            )->first();

            $leaveStats['pendingRecommendation'] = (int) ($counts->pending_recommendation ?? 0);

            if ($isHrd) {
                $leaveStats['pendingApproval'] = (int) ($counts->pending_approval ?? 0);
            }
        }

        // 2. RIWAYAT DATA
        // ====== PERUBAHAN: batasi jumlah riwayat yang ditarik ======
        // Sebelumnya ->get() tanpa limit, jadi semua riwayat cuti user ditarik
        // sekaligus. Kalau datanya banyak, ini menambah waktu transfer + processing.
        // Sesuaikan angka take() ini dengan kebutuhan tampilan dashboard kamu,
        // atau ganti ke pagination kalau frontend sudah support.
        $leaveHistory = RequestCuti::where('user_nik', $user->nik)
            ->with(['leave', 'user.manager'])
            ->orderBy('tglRequest', 'desc')
            ->take(20)
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
                    'catatan_manager' => $req->catatanManager
                ];
            });

        // 3. LIBUR NASIONAL
        // ====== PERUBAHAN: cache 6 jam ======
        // Data libur nasional jarang berubah, jadi nggak perlu query ke Supabase
        // tiap kali dashboard dibuka. Query cuma jalan sekali per 6 jam (atau saat
        // cache kosong), sisanya diambil dari cache lokal (cepat, tanpa network).
        $today = Carbon::today();
        $nationalHolidays = Cache::remember('national_holidays', now()->addHours(6), function () use ($today) {
            return Holiday::where('tgl', '>=', $today)
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