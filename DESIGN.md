# Design Guidelines: Sistem Informasi Supervisi Keperawatan
**RS. St. Elisabeth Semarang** — *"Pancaran Cintanya Menyembuhkan Derita Sesama"*

---

## 1. Brand Identity & Personality
Aplikasi ini digunakan oleh perawat, kepala ruangan, dan supervisor klinis dalam ritme kerja rumah sakit yang cepat, padat, dan membutuhkan fokus tinggi.
- **Personality**: Tenang, profesional, terpercaya, empatik, dan bebas distraksi visual (*zero cognitive clutter*).
- **Core Value**: Mengutamakan keselamatan pasien (*patient safety*), kemudahan pelaporan insiden, dan akurasi pencatatan logbook tanpa hambatan teknis.

---

## 2. Color Palette (Classic Healthcare)
Menghilangkan warna neon kontras tajam dari sistem lama dan menggantinya dengan palet medis yang menenangkan mata:

| Peran Warna | Hex / Tailwind Token | Kegunaan |
| :--- | :--- | :--- |
| **Primary Navy** | `#1E3A8A` (`blue-900` / `indigo-950`) | Header, sidebar aktif, dan identitas utama RS Elisabeth |
| **Action Blue** | `#2563EB` (`blue-600`) | Tombol interaktif utama (*Primary Actions*, Tambah Kejadian) |
| **Elisabeth Emerald** | `#059669` (`emerald-600`) | Tombol Simpan Laporan, status selesai (*resolved*), konfirmasi sukses |
| **Elisabeth Amber** | `#D97706` (`amber-600`) | Indikator butir fasilitas/sarpras, status *in-progress*, perhatian |
| **Clinical Rose** | `#E11D48` (`rose-600`) | Indikator *Complain* pasien/keluarga, status eskalasi (*high urgency*) |
| **Teal Medical** | `#0D9488` (`teal-600`) | Indikator pasien *JKN ≥ 6 Hari*, audit clinical pathway |
| **Slate Base** | `#F8FAFC` (`slate-50`) | Background utama aplikasi (meredam silau lampu bangsal) |
| **Card Surface** | `#FFFFFF` (`white`) | Kartu konten dengan border halus `#E2E8F0` (`slate-200`) |
| **Primary Text** | `#0F172A` (`slate-900`) | Tipografi teks utama (rasio kontras > 10:1 untuk kemudahan baca) |

---

## 3. Typography & Hierarchy
- **Font Family**: `Instrument Sans`, `ui-sans-serif`, `system-ui`, `-apple-system`, `sans-serif`.
- **Hierarki**:
  - H1 / Page Header: `text-xl md:text-2xl font-bold tracking-tight text-slate-900`
  - Section Header: `text-sm font-semibold uppercase tracking-wider text-slate-500`
  - Body Text: `text-sm text-slate-700 leading-relaxed`
  - Meta & Timestamps: `text-xs text-slate-500`

---

## 4. Antislop Dials & Rules
- **ENERGY: 2** (Fokus klinis, tidak ada gradien berlebihan atau dekorasi neon tanpa tujuan).
- **RHYTHM: 3** (Struktur grid rapi dan konsisten dengan *spacing rhythm* 4/8/16/24px).
- **MOTION: 2** (Transisi modal/drawer halus `150ms-200ms ease-out`, tanpa animasi lambat yang membuang waktu perawat).
- **Mobile First & Ergonomi Lapangan**:
  - Semua tombol aksi memiliki tinggi minimal `44px` (ramah sentuhan sarung tangan/layar sentuh).
  - Modal dan drawer mendukung interaksi cepat dengan keyboard (shortcut `Esc` untuk menutup, `Enter` untuk submit).
  - *Unsaved Changes Indicator*: Memberikan kepastian status simpan agar perawat tidak ragu data sudah terekam.

