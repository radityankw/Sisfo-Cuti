<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Request as RequestCuti;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Security Check
        if ($user->role !== 'HRD') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 1. Ambil Filter
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $deptFilter = $request->input('departemen');

        // --- QUERY DASAR (Request yang Approved) ---
        $queryRequests = RequestCuti::with(['user', 'leave'])
            ->where('status', 'APPROVED')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('tglMulai', [$startDate, $endDate])
                  ->orWhereBetween('tglSelesai', [$startDate, $endDate]);
            });

        if ($deptFilter) {
            $queryRequests->whereHas('user', function($q) use ($deptFilter) {
                $q->where('departemen', $deptFilter);
            });
        }

        $allRequests = $queryRequests->get();

        // --- HITUNG SUMMARY ---
        $totalNormatif = 0;
        $totalNonNormatif = 0;

        foreach ($allRequests as $req) {
            $durasi = $req->tglMulai->diffInDays($req->tglSelesai) + 1;
            if ($req->leave->kategori === 'Normatif') {
                $totalNormatif += $durasi;
            } else {
                $totalNonNormatif += $durasi;
            }
        }

        // --- DATA TAB 1: ABSENCE LOG ---
        Carbon::setLocale('id');
        $absenceLog = $allRequests->map(function ($req) {
            return [
                'id' => $req->id,
                'tgl' => $req->tglMulai->setTimezone('Asia/Jakarta')->translatedFormat('d M Y') . ' - ' . $req->tglSelesai->setTimezone('Asia/Jakarta')->translatedFormat('d M Y'),
                'nama' => $req->user->nama,
                'departemen' => $req->user->departemen,
                'nama_cuti' => $req->leave->namaCuti,
                'kategori' => $req->leave->kategori,
                'durasi' => $req->tglMulai->diffInDays($req->tglSelesai) + 1,
            ];
        });

        // --- DATA TAB 2: QUOTA RECAP ---
        $queryUsers = User::query(); 
        if ($deptFilter) {
            $queryUsers->where('departemen', $deptFilter);
        }
        
        $users = $queryUsers->with(['requests' => function($q) {
            $q->where('status', 'APPROVED')->with('leave');
        }])->get();

        $quotaRecap = $users->map(function ($u) {
            $jatahAwal = 12;
            $terpakaiNormatif = 0;
            $terpakaiNonNormatif = 0;

            foreach ($u->requests as $req) {
                // Hitung kuota tahun ini
                if ($req->tglMulai->year == Carbon::now()->year) {
                    $durasi = $req->tglMulai->diffInDays($req->tglSelesai) + 1;
                    if ($req->leave->kategori === 'Normatif') {
                        $terpakaiNormatif += $durasi;
                    } else {
                        $terpakaiNonNormatif += $durasi;
                    }
                }
            }

            return [
                'id' => $u->nik,
                'nama' => $u->nama,
                'departemen' => $u->departemen,
                'kuota_awal' => $jatahAwal,
                'terpakai_normatif' => $terpakaiNormatif,
                'terpakai_non_normatif' => $terpakaiNonNormatif,
                'sisa_kuota' => max(0, $jatahAwal - $terpakaiNormatif), // Koreksi logic: Sisa kuota hanya dikurangi cuti Pribadi (Non-Normatif)
            ];
        });

        $departments = User::select('departemen')->distinct()->pluck('departemen')->filter()->values();

        // RETURN JSON
        return response()->json([
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'departemen' => $deptFilter
            ],
            'departments' => $departments,
            'summary' => [
                'normatif' => $totalNormatif,
                'nonNormatif' => $totalNonNormatif
            ],
            'absenceLog' => $absenceLog,
            'quotaRecap' => $quotaRecap
        ]);
    }

    public function export(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'HRD') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 1. Ambil Filter
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $deptFilter = $request->input('departemen');
        $tab = $request->input('tab', 'absence');

        // --- GAMBAR LOGO UNTUK PDF ---
        // Kita encode base64 agar PDF reader tidak bingung baca path gambar
        $logoPath = public_path('images/logo.png');
        $logoBase64 = null;
        
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        // --- QUERY (Sama Logic-nya) ---
        $queryRequests = RequestCuti::with(['user', 'leave'])
            ->where('status', 'APPROVED')
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('tglMulai', [$startDate, $endDate])
                  ->orWhereBetween('tglSelesai', [$startDate, $endDate]);
            });

        if ($deptFilter) {
            $queryRequests->whereHas('user', function($q) use ($deptFilter) {
                $q->where('departemen', $deptFilter);
            });
        }

        $allRequests = $queryRequests->get();

        // --- HITUNG SUMMARY ---
        $totalNormatif = 0;
        $totalNonNormatif = 0;

        foreach ($allRequests as $req) {
            $durasi = $req->tglMulai->diffInDays($req->tglSelesai) + 1;
            if ($req->leave->kategori === 'Normatif') {
                $totalNormatif += $durasi;
            } else {
                $totalNonNormatif += $durasi;
            }
        }

        // Data Absence
        Carbon::setLocale('id');
        $absenceLog = $allRequests->map(function ($req) {
            return [
                'id' => $req->id,
                'nik' => $req->user->nik,
                'tgl' => $req->tglMulai->setTimezone('Asia/Jakarta')->translatedFormat('d M Y') . ' - ' . $req->tglSelesai->setTimezone('Asia/Jakarta')->translatedFormat('d M Y'),
                'nama' => $req->user->nama,
                'departemen' => $req->user->departemen,
                'nama_cuti' => $req->leave->namaCuti,
                'kategori' => $req->leave->kategori,
                'durasi' => $req->tglMulai->diffInDays($req->tglSelesai) + 1,
            ];
        });

        // Data Quota
        $queryUsers = User::query(); 
        if ($deptFilter) {
            $queryUsers->where('departemen', $deptFilter);
        }
        
        $users = $queryUsers->with(['requests' => function($q) {
            $q->where('status', 'APPROVED')->with('leave');
        }])->get();

        $quotaRecap = $users->map(function ($u) {
            $jatahAwal = 12;
            $terpakaiNormatif = 0;
            $terpakaiNonNormatif = 0;

            foreach ($u->requests as $req) {
                if ($req->tglMulai->year == Carbon::now()->year) {
                    $durasi = $req->tglMulai->diffInDays($req->tglSelesai) + 1;
                    if ($req->leave->kategori === 'Normatif') {
                        $terpakaiNormatif += $durasi;
                    } else {
                        $terpakaiNonNormatif += $durasi;
                    }
                }
            }

            return [
                'nik' => $u->nik,
                'nama' => $u->nama,
                'departemen' => $u->departemen,
                'kuota_awal' => $jatahAwal,
                'terpakai_normatif' => $terpakaiNormatif,
                'terpakai_non_normatif' => $terpakaiNonNormatif,
                'sisa_kuota' => max(0, $jatahAwal - $terpakaiNormatif),
            ];
        });

        $data = [
            'logoBase64' => $logoBase64,
            'tab' => $tab,
            'startDate' => Carbon::parse($startDate)->setTimezone('Asia/Jakarta')->translatedFormat('d F Y'),
            'endDate' => Carbon::parse($endDate)->setTimezone('Asia/Jakarta')->translatedFormat('d F Y'),
            'departemen' => $deptFilter ?: 'Semua Departemen',
            'summary' => [
                'normatif' => $totalNormatif,
                'nonNormatif' => $totalNonNormatif
            ],
            'absenceLog' => $absenceLog,
            'quotaRecap' => $quotaRecap,
            'generatedAt' => Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.laporan', $data);
        $pdf->setPaper('A4', 'landscape');
        
        $filename = 'laporan-cuti-' . ($tab === 'absence' ? 'ketidakhadiran' : 'kuota') . '-' . date('Ymd') . '.pdf';
        
        // PENTING: return download agar browser/Vue menerima stream file
        return $pdf->download($filename);
    }
}