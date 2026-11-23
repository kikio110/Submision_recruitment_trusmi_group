# Trusmi Group – Marketing KPI & Task Deadline Dashboard (Laravel)

Satu project Laravel yang menjawab **soal 1–5** pada `Test_Programmer.xlsx`:

| Soal | Isi | Lokasi |
|---|---|---|
| 1 | Query KPI per karyawan (MySQL 8+/PostgreSQL) | `docs/sql/soal1_kpi_per_karyawan.sql` |
| 2 | Query ONTIME/LATE + persentase | `docs/sql/soal2_ontime_late.sql` |
| 3 | Dashboard grafik hasil soal 1 | halaman **KPI Performance** (`/kpi`) |
| 4 | Dashboard grafik hasil soal 2 | halaman **Task Monitoring** (`/tasks`) |
| 5 | Normalisasi tabel | `docs/sql/soal5_normalisasi.sql` + migration Laravel |

UI mengikuti desain Google Stitch "Executive Precision" (token warna/tipografi Tailwind diambil langsung dari file desain).

## Kebutuhan
PHP >= 8.2, Composer, Laravel 11 / 12 (skeleton terbaru). Database default SQLite (tanpa setup); MySQL/PostgreSQL cukup ubah `.env`.

## Menjalankan

**Cara cepat** (membuat project Laravel baru + menyalin source ini):

```bash
bash setup.sh trusmi-kpi
cd trusmi-kpi
php artisan serve        # buka http://127.0.0.1:8000
```

**Cara manual:**

```bash
composer create-project laravel/laravel trusmi-kpi
cp -R app routes resources public database tests docs README.md trusmi-kpi/   # timpa file default
cd trusmi-kpi
php artisan migrate:fresh --seed
php artisan serve
php artisan test
```

> `routes/web.php` dan `database/seeders/DatabaseSeeder.php` milik skeleton akan tertimpa – memang disengaja.
> Tailwind, Chart.js, dan font dimuat via CDN, jadi tidak perlu `npm install` / `npm run dev` (butuh internet di browser).

## Halaman
`/` Overview · `/kpi` KPI Performance · `/employees` Employee Performance · `/tasks` Task Monitoring · `/settings` aturan KPI & model data

## Arsitektur
```
app/Services/KpiCalculator.php      <- SELURUH business logic (satu-satunya sumber angka)
app/Http/Controllers/ApiController  <- GET /api/kpi , GET /api/tasks  (JSON, filter di-whitelist)
app/Http/Controllers/PageController <- halaman Blade
app/Models/{Employee,KpiCategory,Tasklist}
database/migrations/...normalized   <- employees, kpi_categories, tasklists + VIEW table_kpi_marketing
database/seeders/KpiSeeder          <- 13 baris data asli dari Excel
public/js/{common,kpi,tasks}.js     <- render + Chart.js (instance dibuat sekali, filter memakai chart.update())
tests/Feature/KpiCalculationTest    <- PHPUnit: PHP == SQL == nilai di soal
```
Perhitungan dilakukan di PHP, JavaScript hanya menampilkan. Dengan begitu kartu, grafik, dan tabel selalu memakai angka yang sama.

### Normalisasi (soal 5)
Tabel asli menyimpan nama karyawan & KPI berulang di tiap baris dan tidak menyimpan aturan KPI. Hasil 3NF:
`employees(id, name)`, `kpi_categories(id, name, target, weight, late_penalty)`, `tasklists(id, name, employee_id, kpi_category_id, deadline, actual_date)`.
View `table_kpi_marketing` menjaga struktur lama, sehingga query soal 1 & 2 jalan **tanpa diubah**. Target/bobot/penalti dibaca dari `kpi_categories`, bukan hard-code.

### Business logic
```
Achievement  = Actual / Target                 (Actual = jumlah tasklist per KPI)
Base Weight  = Achievement × 0.50
Late Deduct  = jumlah tasklist LATE × 0.07 (Sales) | × 0.05 (Report)
Final Score  = Base Weight − Late Deduct
Total KPI    = Sales Final + Report Final
Status       = aktual <= deadline ? ONTIME : LATE   (tanggal tidak valid -> INVALID)
Days Diff    = aktual − deadline (hari; negatif = lebih awal)
```
- Tidak ada pembulatan antar-langkah; pembulatan hanya saat tampil (`roundForDisplay` / `Fmt.num`).
- Tanggal dibandingkan sebagai nomor hari UTC dari `YYYY-MM-DD` → bebas masalah timezone.
- Setiap tasklist late dikurangkan tepat satu kali. Pembagian nol ditangani.
- Perilaku sama dengan SQL: `Actual` = `COUNT` tasklist per KPI. Tasklist INVALID tetap dihitung sebagai Actual tetapi tidak dihitung LATE maupun ONTIME.
- *Performance Status* (Excellent ≥ 90%, Good ≥ 75%, Needs Improvement < 75%) hanya klasifikasi visual tambahan, bukan ketentuan soal.
- Pada Task Monitoring, filter **employee + status** memengaruhi kartu, donut, bar chart, dan kedua tabel (data terfilter yang sama). **Search** hanya menyaring tabel detail tasklist.

## Hasil validasi
KPI (PHP = SQL = soal):

| Karyawan | Sales Final | Report Final | Total KPI |
|---|---:|---:|---:|
| Adi | 0.43 | 0.50 | 0.93 |
| Budi | 0.50 | 0.45 | 0.95 |
| Doni | 0.18 | 0.25 | 0.43 |
| Rara | 0.43 | 0.20 | 0.63 |

Total karyawan 4, tasklist 13 (Sales 7, Report 6), Sales late 3, Report late 2. Rata-rata: KPI 0.735 (tampil 0.74), Sales 87.5%, Report 75.0%.

Ketepatan deadline: 13 tasklist, 8 ONTIME (61.54%), 5 LATE (38.46%). Adi 3/4 (75%), Budi 3/4 (75%), Doni 1/2 (50%), Rara 1/3 (33.33%).

## Pengujian manual
1. `/kpi`: ubah filter karyawan → kartu, 2 grafik, tabel, dan detail ikut berubah. Klik nama di tabel / tab detail untuk breakdown.
2. `/tasks`: coba filter employee, status (juga tab All/Ontime/Late), search (mis. `tasklist 13`), klik header Deadline untuk sort, pagination, klik baris untuk modal detail.
3. Ubah ukuran browser (desktop/tablet/mobile) → sidebar menjadi menu hamburger, tabel scroll horizontal.
4. `php artisan test` → seluruh pengujian harus hijau.

## Catatan pengumpulan
Soal meminta jawaban 1, 2, 5 berupa `.txt` (file `.sql` di `docs/sql` dapat diganti ekstensinya), soal 3 & 4 berupa screenshot + source, dan **prompt AI dilampirkan** bila memakai AI.
