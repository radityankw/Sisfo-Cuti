<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Cuti Karyawan</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }

        /* --- HEADER --- */
        .header-table { width: 100%; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 20px; }
        .logo-cell { width: 80px; text-align: left; vertical-align: middle; }
        .text-cell { text-align: center; vertical-align: middle; }
        .spacer-cell { width: 80px; }
        .logo-img { height: 60px; width: auto; }
        .title { font-size: 14pt; font-weight: bold; text-transform: uppercase; margin: 0; color: #1e3a8a; }
        .subtitle { font-size: 10pt; font-weight: normal; margin: 5px 0 0 0; color: #555; }

        /* --- META INFO --- */
        .meta-table { width: 100%; margin-bottom: 20px; font-size: 10pt; }
        .meta-table td { padding: 2px 0; vertical-align: top; }
        .label { font-weight: bold; width: 120px; color: #1e3a8a; }

        /* --- SUMMARY SECTION (STYLE KHUSUS SESUAI GAMBAR) --- */
        .summary-container {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: separate;
            border-spacing: 15px 0; /* Memberi jarak antar kotak */
        }
        .summary-box {
            padding: 15px 10px;
            border-radius: 8px; /* Sudut melengkung */
            text-align: center;
            width: 48%;
            vertical-align: middle;
        }
        
        /* WARNA BOX BIRU (Normatif) */
        .box-normatif {
            background-color: #eff6ff; /* Biru sangat muda */
            border: 1px solid #bfdbfe; /* Garis biru muda */
        }
        .text-normatif-title {
            color: #3b82f6; /* Biru sedang */
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .text-normatif-value {
            color: #1e3a8a; /* Biru tua */
            font-size: 24pt;
            font-weight: bold;
        }

        /* WARNA BOX MERAH (Non-Normatif) */
        .box-non-normatif {
            background-color: #fef2f2; /* Merah sangat muda */
            border: 1px solid #fecaca; /* Garis merah muda */
        }
        .text-non-title {
            color: #dc2626; /* Merah sedang */
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .text-non-value {
            color: #991b1b; /* Merah tua */
            font-size: 24pt;
            font-weight: bold;
        }

        .unit { font-size: 12pt; font-weight: normal; color: #555; margin-left: 5px; }

        /* --- MAIN TABLE --- */
        .section-title { font-size: 11pt; font-weight: bold; margin-bottom: 10px; text-decoration: underline; color: #1e3a8a; }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        .data-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 8px;
            border: 1px solid #1e3a8a;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }
        .data-table td {
            padding: 6px 8px;
            border: 1px solid #1e3a8a;
            vertical-align: middle;
            text-align: center;
        }
        .data-table tr:nth-child(even) {
            background-color: #e3f2fd;
        }

        .text-left { text-align: left !important; }
        .font-bold { font-weight: bold; }

        /* --- FOOTER --- */
        .footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            text-align: center; font-size: 8pt; color: #1e3a8a;
            border-top: 1px solid #1e3a8a; padding-top: 5px;
        }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo MK">
                @endif
            </td>
            <td class="text-cell">
                <h1 class="title">PT. MENARA KUDUS</h1>
                <h2 class="subtitle">Laporan Rekapitulasi & Riwayat Cuti Karyawan</h2>
            </td>
            <td class="spacer-cell"></td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td class="label">Periode</td>
            <td>: {{ $startDate }} s/d {{ $endDate }}</td>
            <td class="label" style="text-align: right;">Departemen</td>
            <td style="text-align: right; width: 150px;">: {{ $departemen }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Cetak</td>
            <td>: {{ $generatedAt }}</td>
            <td colspan="2"></td>
        </tr>
    </table>

    <table class="summary-container">
        <tr>
            <td class="summary-box box-normatif">
                <div class="text-normatif-title">TOTAL CUTI NORMATIF</div>
                <div class="text-normatif-value">
                    {{ $summary['normatif'] }}<span class="unit">Hari</span>
                </div>
            </td>
            
            <td class="summary-box box-non-normatif">
                <div class="text-non-title">TOTAL CUTI NON-NORMATIF</div>
                <div class="text-non-value">
                    {{ $summary['nonNormatif'] }}<span class="unit">Hari</span>
                </div>
            </td>
        </tr>
    </table>

    @if($tab === 'absence')
        <div class="section-title">Riwayat Ketidakhadiran</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 20%;">Tanggal</th>
                    <th>Nama Karyawan</th>
                    <th style="width: 15%;">Departemen</th>
                    <th style="width: 20%;">Jenis Cuti</th>
                    <th style="width: 15%;">Kategori</th>
                    <th style="width: 10%;">Durasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($absenceLog as $index => $log)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $log['tgl'] }}</td>
                    <td class="text-left" style="padding-left: 10px;">{{ $log['nama'] }}</td>
                    <td>{{ $log['departemen'] }}</td>
                    <td>{{ $log['nama_cuti'] }}</td>
                    <td>
                        {{ $log['kategori'] }} 
                    </td>
                    <td>{{ $log['durasi'] }} Hari</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding: 15px; color: #555;">
                        Tidak ada data cuti pada periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

    @else
        <div class="section-title">Rekapitulasi Kuota Cuti</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th>Nama Karyawan</th>
                    <th style="width: 20%;">Departemen</th>
                    <th style="width: 15%;">Kuota Awal</th>
                    <th style="width: 15%;">Terpakai (Normatif)</th>
                    <th style="width: 15%;">Terpakai (Non-Normatif)</th>
                    <th style="width: 15%;">Sisa Kuota</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotaRecap as $index => $user)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left" style="padding-left: 10px;">{{ $user['nama'] }}</td>
                    <td>{{ $user['departemen'] }}</td>
                    <td>{{ $user['kuota_awal'] }}</td>
                    <td>{{ $user['terpakai_normatif'] }}</td>
                    <td>{{ $user['terpakai_non_normatif'] }}</td>
                    <td class="font-bold">{{ $user['sisa_kuota'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding: 15px; color: #555;">
                        Tidak ada data karyawan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <div class="footer">
        Dokumen ini dicetak otomatis oleh Sistem Informasi Cuti PT. Menara Kudus | Halaman <span class="page-number"></span>
    </div>

</body>
</html>
