<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Supervisi #RPT-{{ str_pad((string) $report->id, 5, '0', STR_PAD_LEFT) }} - {{ $report->room_name }}</title>
    <style>
        @page {
            margin: 18mm 16mm 18mm 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 10pt;
            line-height: 1.45;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        /* Kop Surat Instansi */
        .letterhead-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .letterhead-logo-cell {
            width: 78px;
            vertical-align: middle;
            text-align: left;
            padding-right: 12px;
        }

        .letterhead-logo {
            width: 66px;
            height: auto;
        }

        .letterhead-text-cell {
            vertical-align: middle;
            text-align: left;
        }

        .hospital-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 0.4px;
            margin: 0;
            text-transform: uppercase;
        }

        .department-name {
            font-size: 10pt;
            font-weight: bold;
            color: #0f766e;
            margin: 2px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .hospital-address {
            font-size: 8pt;
            color: #475569;
            margin: 3px 0 0 0;
        }

        .hospital-motto {
            font-size: 7.5pt;
            font-style: italic;
            color: #64748b;
            margin: 1px 0 0 0;
        }

        .letterhead-divider-thick {
            border-top: 2.5px solid #1e3a8a;
            margin-top: 8px;
        }

        .letterhead-divider-thin {
            border-top: 1px solid #94a3b8;
            margin-top: 2px;
            margin-bottom: 14px;
        }

        /* Judul Dokumen */
        .doc-title-box {
            text-align: center;
            margin-bottom: 14px;
            padding: 8px 12px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }

        .doc-title {
            font-size: 11.5pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .doc-subtitle {
            font-size: 8.5pt;
            color: #475569;
            margin: 3px 0 0 0;
        }

        /* Section Heading */
        .section-heading {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e3a8a;
            letter-spacing: 0.4px;
            margin: 14px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #cbd5e1;
        }

        /* Tabel Ringkasan Operasional */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .summary-table td {
            padding: 6px 9px;
            border: 1px solid #cbd5e1;
            font-size: 9pt;
            vertical-align: top;
        }

        .summary-label {
            width: 22%;
            font-weight: bold;
            color: #334155;
            background-color: #f1f5f9;
        }

        .summary-value {
            width: 28%;
            color: #0f172a;
        }

        .collaborator-badge {
            display: inline-block;
            padding: 2px 7px;
            margin: 1px 3px 2px 0;
            font-size: 8pt;
            font-weight: bold;
            color: #115e59;
            background-color: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 3px;
        }

        /* Tabel Insiden */
        .incidents-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .incidents-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            text-align: left;
            padding: 7px 8px;
            border: 1px solid #1e3a8a;
        }

        .incidents-table td {
            padding: 7px 8px;
            border: 1px solid #cbd5e1;
            font-size: 8.5pt;
            vertical-align: top;
            color: #1e293b;
        }

        .incidents-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .category-pill {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            background-color: #e2e8f0;
            color: #0f172a;
            border-radius: 3px;
            margin-bottom: 3px;
        }

        .time-label {
            display: block;
            font-size: 7.5pt;
            color: #475569;
            margin-top: 2px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            border-radius: 3px;
            text-align: center;
        }

        .status-resolved {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-escalated {
            background-color: #fff1f2;
            color: #9f1239;
            border: 1px solid #fecdd3;
        }

        .status-in-progress {
            background-color: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .empty-box {
            padding: 12px 14px;
            background-color: #f8fafc;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            text-align: center;
            font-size: 9pt;
            color: #334155;
            margin-bottom: 10px;
        }

        .notes-box {
            padding: 10px 12px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 9pt;
            color: #1e293b;
            white-space: pre-line;
            margin-bottom: 14px;
        }

        /* Blok Tanda Tangan */
        .signature-wrapper {
            width: 100%;
            margin-top: 22px;
            page-break-inside: avoid;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-left {
            width: 55%;
            vertical-align: bottom;
            font-size: 8pt;
            color: #64748b;
        }

        .signature-right {
            width: 45%;
            text-align: center;
            vertical-align: top;
            font-size: 9pt;
            color: #0f172a;
        }

        .signature-space {
            height: 58px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
        }

        .signature-role {
            font-size: 8pt;
            color: #475569;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <!-- Kop Surat RS St. Elisabeth Semarang -->
    <table class="letterhead-table">
        <tr>
            @if(!empty($logoBase64))
                <td class="letterhead-logo-cell">
                    <img src="{{ $logoBase64 }}" alt="Logo RS St. Elisabeth Semarang" class="letterhead-logo">
                </td>
            @endif
            <td class="letterhead-text-cell">
                <p class="hospital-name">RS St. Elisabeth Semarang</p>
                <p class="department-name">Bidang Keperawatan &middot; Sistem Informasi Supervisi Keperawatan</p>
                <p class="hospital-address">Jl. Kawi No. 1, Tegalsari, Kec. Candisari, Kota Semarang, Jawa Tengah 50614</p>
                <p class="hospital-motto">"Pancaran Cintanya Menyembuhkan Derita Sesama"</p>
            </td>
        </tr>
    </table>
    <div class="letterhead-divider-thick"></div>
    <div class="letterhead-divider-thin"></div>

    <!-- Judul & Identitas Dokumen -->
    <div class="doc-title-box">
        <p class="doc-title">Laporan Logbook Supervisi Keperawatan</p>
        <p class="doc-subtitle">
            ID Laporan: <strong>#RPT-{{ str_pad((string) $report->id, 5, '0', STR_PAD_LEFT) }}</strong>
            &nbsp;|&nbsp;
            Status: <strong>Tersimpan &amp; Terkirim</strong>
            &nbsp;|&nbsp;
            Waktu Cetak: <strong>{{ $printedAt->translatedFormat('d F Y, H:i') }} WIB</strong>
        </p>
    </div>

    <!-- Bagian I: Ringkasan Operasional -->
    <div class="section-heading">I. Informasi Operasional &amp; Petugas Supervisi</div>
    @php
        $collaborators = is_array($report->nurse_in_charge)
            ? $report->nurse_in_charge
            : $report->collaboratorList();
    @endphp
    <table class="summary-table">
        <tr>
            <td class="summary-label">Ruangan / Bangsal</td>
            <td class="summary-value"><strong>{{ $report->room_name }}</strong></td>
            <td class="summary-label">Tanggal Supervisi</td>
            <td class="summary-value"><strong>{{ $report->report_date->translatedFormat('l, d F Y') }}</strong></td>
        </tr>
        {{-- Baris Shift disembunyikan sesuai arahan operasional (dapat diaktifkan kembali bila diperlukan) --}}
        {{--
        <tr>
            <td class="summary-label">Shift Dinas</td>
            <td class="summary-value" colspan="3">Shift {{ ucfirst((string) $report->shift) }}</td>
        </tr>
        --}}
        <tr>
            <td class="summary-label">Jumlah Pasien (BOR)</td>
            <td class="summary-value"><strong>{{ $report->patient_count }}</strong> Orang</td>
            <td class="summary-label">Perawat Dinas</td>
            <td class="summary-value"><strong>{{ $report->nurse_count }}</strong> Ners</td>
        </tr>
        <tr>
            <td class="summary-label">Supervisor Pengawas</td>
            <td class="summary-value"><strong>{{ $report->supervisor_name }}</strong></td>
            <td class="summary-label">Waktu Pencatatan</td>
            <td class="summary-value">{{ $report->created_at ? $report->created_at->translatedFormat('d F Y, H:i').' WIB' : '-' }}</td>
        </tr>
        <tr>
            <td class="summary-label">Kolaborator ({{ count($collaborators) }})</td>
            <td class="summary-value" colspan="3">
                @forelse($collaborators as $collabName)
                    <span class="collaborator-badge">{{ $collabName }}</span>
                @empty
                    <span style="color: #64748b; font-style: italic;">Tanpa kolaborator</span>
                @endforelse
            </td>
        </tr>
    </table>

    <!-- Bagian II: Rincian Kejadian & Tindak Lanjut -->
    <div class="section-heading">II. Rincian Kejadian, Insiden &amp; Tindak Lanjut ({{ $report->incidents->count() }})</div>
    @if($report->incidents->isEmpty())
        <div class="empty-box">
            <strong>Dinas Berjalan Aman &amp; Kondusif</strong><br>
            Tidak ada insiden, komplain, atau kendala fasilitas yang dilaporkan pada sesi supervisi ini.
        </div>
    @else
        <table class="incidents-table">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No</th>
                    <th style="width: 22%;">Kategori &amp; Waktu</th>
                    <th style="width: 34%;">Uraian Masalah / Kronologi</th>
                    <th style="width: 25%;">Tindak Lanjut &amp; Penanganan</th>
                    <th style="width: 14%; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report->incidents as $index => $incident)
                    <tr>
                        <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                        <td>
                            <span class="category-pill">{{ $incident->categoryLabel() }}</span>
                            @if($incident->incident_time)
                                <span class="time-label">Pukul {{ $incident->incident_time }} WIB</span>
                            @else
                                <span class="time-label">Waktu tidak dicantumkan</span>
                            @endif
                        </td>
                        <td style="white-space: pre-line;">{{ $incident->description }}</td>
                        <td style="white-space: pre-line;">{{ $incident->action_taken ?: '-' }}</td>
                        <td style="text-align: center;">
                            @if($incident->status === 'resolved')
                                <span class="status-badge status-resolved">Selesai</span>
                            @elseif($incident->status === 'escalated')
                                <span class="status-badge status-escalated">Butuh Eskalasi</span>
                            @else
                                <span class="status-badge status-in-progress">Sedang Ditangani</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Bagian III: Catatan Umum / Handover -->
    <div class="section-heading">III. Pesan Khusus Operan (Handover) / Catatan Evaluasi</div>
    <div class="notes-box">{{ $report->general_notes ?: 'Tidak ada catatan khusus tambahan pada laporan supervisi ini.' }}</div>

    <!-- Blok Pengesahan & Tanda Tangan -->
    <div class="signature-wrapper">
        <table class="signature-table">
            <tr>
                <td class="signature-left">
                    Dokumen ini dihasilkan secara otomatis melalui Sistem E-Supervisi Keperawatan RS St. Elisabeth Semarang.<br>
                    Referensi Dokumen: #RPT-{{ str_pad((string) $report->id, 5, '0', STR_PAD_LEFT) }} &middot; Dicetak oleh: {{ $printedBy }}
                </td>
                <td class="signature-right">
                    <div>Semarang, {{ $printedAt->translatedFormat('d F Y') }}</div>
                    <div style="font-weight: bold; margin-top: 2px;">Supervisor Pengawas,</div>
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $report->supervisor_name }}</div>
                    <div class="signature-role">Bidang Keperawatan RS St. Elisabeth Semarang</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

