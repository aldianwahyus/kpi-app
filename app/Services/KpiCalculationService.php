<?php
namespace App\Services;

class KpiCalculationService
{
    /**
     * Hitung skor capaian KPI Pegawai berdasarkan kriteria pencapaian
     * (skema band diskrit, bukan skor kontinu):
     *   > 110% dari target  -> Skor 4
     *   100% s.d. 110%      -> Skor 3
     *   80% s.d. <100%      -> Skor 2
     *   < 80% dari target   -> Skor 1
     *
     * Formula pencapaian: (Realisasi/Target)*100% untuk polarity 'max',
     * (Target/Realisasi)*100% untuk polarity 'min' — arah rasio tidak
     * berubah dari skema sebelumnya, hanya hasil akhirnya kini dipetakan
     * ke salah satu dari 4 band di atas alih-alih dipakai langsung sebagai skor.
     */
    public function hitungSkorCapaian(
        float  $realisasi,
        float  $target,
        string $polarity = 'max',
        bool   $isCapped = true
    ): float {
        // Target belum diisi -> tidak bisa dihitung rasionya sama sekali,
        // diperlakukan sebagai band terendah (belum bisa dinilai).
        if ($target == 0) return 1;

        // Pemanggil (PenilaianController::store, ajaxHitung, ajaxHitungTurunan)
        // bertanggung jawab membedakan "belum diisi" (null/string kosong) dari
        // "realisasi = 0 yang genuinely valid" SEBELUM memanggil fungsi ini —
        // fungsi ini hanya menerima nilai yang memang sudah dipastikan terisi.
        if ($polarity !== 'max' && $realisasi == 0) {
            // minimize: realisasi 0 adalah capaian TERBAIK yang mungkin
            // (mis. 0 kasus fraud/komplain) -> rasio tak terhingga -> band
            // tertinggi langsung, tanpa membagi dengan nol.
            return 4;
        }

        $pencapaianPersen = $this->hitungPencapaianPersen($realisasi, $target, $polarity);

        return $this->petakanSkorDariPencapaian($pencapaianPersen);
    }

    /**
     * Hitung persentase Pencapaian mentah (Realisasi/Target atau
     * Target/Realisasi tergantung polarity) — dipakai baik untuk menampilkan
     * kolom "Pencapaian" di tabel penilaian maupun sebagai dasar pemetaan
     * Skor di hitungSkorCapaian(), supaya rumusnya hanya ada di SATU tempat
     * (menghindari duplikasi logika yang bisa saling menyimpang).
     *
     * Mengembalikan INF untuk KPI 'min' dengan realisasi 0 (capaian tak
     * terhingga secara matematis) — pemanggil yang menampilkan nilai ini ke
     * pengguna (view/JSON) WAJIB memeriksa is_infinite() dan menampilkan
     * representasi yang sesuai (mis. "∞"), karena json_encode() gagal/rusak
     * jika diberi nilai INF/NAN mentah.
     *
     * Pemanggil bertanggung jawab memeriksa $target == 0 sebelum memanggil
     * fungsi ini (target belum diisi berarti belum bisa dihitung sama sekali,
     * bukan kewenangan fungsi ini untuk memutuskan tampilannya).
     */
    public function hitungPencapaianPersen(float $realisasi, float $target, string $polarity = 'max'): float
    {
        if ($polarity === 'max') {
            return ($realisasi / $target) * 100;
        }

        if ($realisasi == 0) {
            return INF;
        }

        return ($target / $realisasi) * 100;
    }

    /**
     * Petakan persentase pencapaian ke skor band 1-4 sesuai kriteria
     * pencapaian yang berlaku (sama untuk polarity max maupun min, karena
     * arah "lebih baik/lebih buruk" sudah ditangani oleh formula pencapaian
     * itu sendiri sebelum sampai di sini).
     */
    private function petakanSkorDariPencapaian(float $pencapaianPersen): float
    {
        // Dibulatkan dulu ke 4 desimal supaya nilai yang secara matematis
        // pas di batas (mis. realisasi/target menghasilkan tepat 110%) tidak
        // salah masuk band karena galat pembulatan floating-point biner
        // (110/100*100 bisa jadi 110.00000000000001, bukan 110.0 persis).
        $pencapaianPersen = round($pencapaianPersen, 4);

        return match(true) {
            $pencapaianPersen > 110  => 4,
            $pencapaianPersen >= 100 => 3,
            $pencapaianPersen >= 80  => 2,
            default                  => 1,
        };
    }

    /**
     * Skor untuk polarity 'precise' (Precise is Better) — semakin dekat
     * realisasi ke target (100%), semakin tinggi skor. Band ditentukan oleh
     * toleransi deviasi (%) simetris di atas & di bawah target, dikonfigurasi
     * per KPI oleh Admin (bukan band tetap seperti max/min):
     *   deviasi <= toleransi Skor 4 -> Skor 4
     *   deviasi <= toleransi Skor 3 -> Skor 3
     *   deviasi <= toleransi Skor 2 -> Skor 2
     *   selainnya                   -> Skor 1
     * dengan deviasi = |(-realisasi/target*100) - 100|.
     */
    public function hitungSkorPrecise(
        float $realisasi,
        float $target,
        float $toleransiSkor4,
        float $toleransiSkor3,
        float $toleransiSkor2
    ): float {
        if ($target == 0) return 1;

        $deviasi = abs(round($this->hitungPencapaianPrecise($realisasi, $target), 4) - 100);

        return match(true) {
            $deviasi <= $toleransiSkor4 => 4,
            $deviasi <= $toleransiSkor3 => 3,
            $deviasi <= $toleransiSkor2 => 2,
            default                     => 1,
        };
    }

    /**
     * Persentase pencapaian mentah untuk polarity 'precise' — identik
     * dengan rumus 'max' (Realisasi/Target x 100%), dipisah sebagai fungsi
     * sendiri supaya pemanggil tidak perlu tahu bahwa rumusnya kebetulan sama.
     */
    public function hitungPencapaianPrecise(float $realisasi, float $target): float
    {
        if ($target == 0) return 0;
        return ($realisasi / $target) * 100;
    }

    /**
     * Skor untuk polarity 'special' (Special Scoring) — penilaian biner
     * "Ada"/"Tidak Ada" (bukan angka realisasi), arah skor tergantung Sifat:
     *   Maximize: Ada -> Skor 4, Tidak Ada -> Skor 1 (mis. Inisiasi Program)
     *   Minimize: Ada -> Skor 1, Tidak Ada -> Skor 4 (mis. Zero Fraud/Denda)
     */
    public function hitungSkorSpecial(bool $ada, string $sifat = 'maximize'): float
    {
        if ($sifat === 'minimize') {
            return $ada ? 1 : 4;
        }
        return $ada ? 4 : 1;
    }

    /**
     * Pengkali (diskon) dari Indikator 2 (Rata-rata Harian) untuk polarity
     * 'tertimbang' — tabel tetap, TIDAK dikonfigurasi per KPI (beda dari
     * toleransi 'precise'). $rataRataHarianPersen adalah PERSENTASE
     * rata-rata pencapaian harian selama periode penilaian, dimasukkan
     * langsung apa adanya (BUKAN rasio realisasi/target — tidak ada
     * "Target Harian" terpisah, rata-ratanya sudah dihitung di luar sistem):
     *   > 95%           -> 100%
     *   90% s.d. <95%   -> 95%
     *   85% s.d. <90%   -> 90%
     *   < 85%           -> 85%
     */
    public function hitungPengkaliHarian(float $rataRataHarianPersen): float
    {
        $persen = round($rataRataHarianPersen, 4);

        return match(true) {
            $persen > 95  => 1.00,
            $persen >= 90 => 0.95,
            $persen >= 85 => 0.90,
            default       => 0.85,
        };
    }

    /**
     * Skor akhir untuk polarity 'tertimbang' (Scoring Tertimbang) — 3 tahap:
     *   Tahap 1: Persentase Capaian = (Realisasi/Target) x 100%
     *   Tahap 2: Skor Indikator dari band standar (>110%=4, 100-110%=3,
     *            80-<100%=2, <80%=1 — sama seperti petakanSkorDariPencapaian())
     *   Tahap 3: Pengkali dari Rata-rata Harian (persentase langsung, lihat
     *            hitungPengkaliHarian())
     *   Skor Akhir = Skor Indikator x Pengkali
     * Skor Akhir bisa berupa desimal (mis. 3 x 0,95 = 2,85), berbeda dari
     * polarity lain yang selalu bilangan bulat 1-4 — tetap valid karena
     * Nilai/Total sudah dirancang menerima float.
     */
    public function hitungSkorTertimbang(
        float $realisasi,
        float $target,
        float $rataRataHarianPersen
    ): float {
        $skorIndikator = $this->hitungSkorCapaian($realisasi, $target, 'max', true);
        $pengkali      = $this->hitungPengkaliHarian($rataRataHarianPersen);

        return $skorIndikator * $pengkali;
    }

    /**
     * Dispatcher tunggal: hitung Skor untuk KPI apa pun berdasarkan
     * polarity-nya, supaya logika percabangan 5-arah (max/min/precise/
     * special/tertimbang) hanya ada di SATU tempat, bukan diduplikasi di
     * setiap pemanggil (PenilaianController::store/ajaxHitung/ajaxHitungTurunan,
     * masing-masing untuk Induk maupun Turunan — total 6 titik pemanggilan).
     *
     * $kpiConfig: baris konfigurasi KPI (kpi_unit atau kpi_pegawai_turunan)
     *   berisi minimal: polarity, target, is_capped, toleransi_skor4/3/2,
     *   sifat_khusus.
     * $input: nilai yang diinput user, berisi minimal:
     *   realisasi        (float|bool — bool khusus 'special': true='Ada')
     *   realisasi_harian (float, persentase Rata-rata Harian langsung —
     *                     hanya dipakai untuk 'tertimbang')
     */
    public function hitungSkor(array $kpiConfig, array $input): float
    {
        $polarity = $kpiConfig['polarity'] ?? 'max';
        $target   = (float)($kpiConfig['target'] ?? 0);

        return match ($polarity) {
            'precise' => $this->hitungSkorPrecise(
                (float)($input['realisasi'] ?? 0),
                $target,
                (float)($kpiConfig['toleransi_skor4'] ?? 0),
                (float)($kpiConfig['toleransi_skor3'] ?? 0),
                (float)($kpiConfig['toleransi_skor2'] ?? 0)
            ),
            'special' => $this->hitungSkorSpecial(
                (bool)($input['realisasi'] ?? false),
                $kpiConfig['sifat_khusus'] ?? 'maximize'
            ),
            'tertimbang' => $this->hitungSkorTertimbang(
                (float)($input['realisasi'] ?? 0),
                $target,
                (float)($input['realisasi_harian'] ?? 0)
            ),
            default => $this->hitungSkorCapaian(
                (float)($input['realisasi'] ?? 0),
                $target,
                $polarity,
                (bool)($kpiConfig['is_capped'] ?? true)
            ),
        };
    }

    public function isValidPolarity(string $polarity): bool
    {
        return in_array($polarity, ['max', 'min', 'precise', 'special', 'tertimbang'], true);
    }

    /**
     * Hitung capaian KPI Unit (realisasi vs target, mempertimbangkan polarity).
     * Berbeda dari hitungSkorCapaian() yang dipakai untuk KPI individu pegawai
     * (skema band 1-4) — KPI Unit murni mencatat rasio capaian divisi,
     * tanpa bobot per-pegawai, dengan hasil di-cap maksimal 150% (1.5).
     * TIDAK terpengaruh perubahan skema skor pegawai di atas.
     */
    public function hitungCapaian(
        float  $target,
        float  $realisasi,
        string $polarity   = 'max',
        string $perubahan  = 'pos'
    ): float {
        if ($target == 0) return 0;

        // Polarity baru (precise/special/tertimbang) tidak mengenal konsep
        // perubahan_polarity (pos/neg) — field itu direpurpose untuk hal
        // lain pada tiap polarity tersebut (toleransi/sifat/target harian),
        // jadi tidak relevan di sini. Modul Penilaian Unit (terpisah dari
        // Penilaian Pegawai, tidak mendukung skema Skor 1-4 khusus tipe ini)
        // diberi fallback rasio "semakin besar semakin baik" generik, supaya
        // tidak diam-diam salah arah ke rumus minimize (yang akan terjadi
        // jika dibiarkan lolos ke pengecekan $isMaxLike di bawah, karena
        // $perubahan tidak akan pernah cocok 'pos' secara bermakna).
        if (!$this->isValidPolarity($polarity) || !in_array($polarity, ['max', 'min'], true)) {
            if ($realisasi == 0) return 0;
            return min(1.5, max(0, $realisasi / $target));
        }

        $isMaxLike = ($polarity === 'max' && $perubahan === 'pos');

        if ($isMaxLike) {
            // realisasi 0 = belum ada progres nyata untuk KPI 'max' -> capaian 0.
            if ($realisasi == 0) return 0;
            $capaian = $realisasi / $target;
        } else {
            // minimize: realisasi 0 adalah capaian TERBAIK yang mungkin
            // (mis. 0 kasus fraud/komplain) -> dibatasi ke plafon 150%,
            // bukan dihitung sebagai pembagian dengan nol.
            if ($realisasi == 0) return 1.5;
            $capaian = $target / $realisasi;
        }

        return min(1.5, max(0, $capaian));
    }

    /**
     * Kontribusi = Nilai (identik dengan Skor band 1-4) x Bobot.
     * Tidak ada lagi clamp 10-100 — skor sudah dijamin berada di 1-4
     * oleh petakanSkorDariPencapaian().
     */
    public function hitungKontribusi(float $skor, float $bobot): float
    {
        return $skor * $bobot;
    }

    public function isValidSkor(float $skor): bool
    {
        return $skor >= 1 && $skor <= 4;
    }

    /**
     * Grade akhir (Yudisium) dari Nilai Akhir — Kriteria Bobot Tertimbang (α):
     *   Istimewa    : 3,5 < α ≤ 4,0
     *   Sangat Baik : 2,5 < α ≤ 3,5
     *   Baik        : 1,5 < α ≤ 2,5
     *   Cukup       : α ≤ 1,5
     * Batas bawah tiap pita EKSKLUSIF, batas atas INKLUSIF — persis di
     * ambang (mis. 3,5) masuk pita di BAWAHNYA, bukan pita di atasnya.
     */
    public function getGrade(float $nilai): string
    {
        // Dibulatkan dulu ke 4 desimal supaya SUM Skor x Bobot yang secara
        // matematis pas di ambang (mis. 3,5) tidak salah pita karena galat
        // pembulatan floating-point biner — sama seperti pada
        // petakanSkorDariPencapaian().
        $nilai = round($nilai, 4);

        return match(true) {
            $nilai > 3.5 => 'IS',
            $nilai > 2.5 => 'SB',
            $nilai > 1.5 => 'B',
            default      => 'C',
        };
    }

    public function getGradeLabel(string $grade): string
    {
        return match($grade) {
            'IS'    => 'Istimewa',
            'SB'    => 'Sangat Baik',
            'B'     => 'Baik',
            'C'     => 'Cukup',
            default => '—',
        };
    }

    public function getGradeColor(string $grade): array
    {
        return match($grade) {
            'IS'    => ['bg'=>'#1E7A55','color'=>'#FFFFFF'],
            'SB'    => ['bg'=>'#A9D18E','color'=>'#1E4620'],
            'B'     => ['bg'=>'#FFC000','color'=>'#7F6000'],
            'C'     => ['bg'=>'#FCE4D6','color'=>'#C00000'],
            default => ['bg'=>'#f0f0f0','color'=>'#888'],
        };
    }

    /**
     * Warna badge berdasarkan SKOR band (1-4), bukan grade huruf.
     * Dipakai khusus untuk AJAX real-time feedback per-KPI.
     */
    public function getColorBySkor(float $skor): string
    {
        return match(true) {
            $skor >= 4 => 'success',
            $skor >= 3 => 'primary',
            $skor >= 2 => 'warning',
            default    => 'danger',
        };
    }

    public function getGradeInfo(): array
    {
        return [
            'IS' => ['label'=>'Istimewa',    'range'=>'3,5 < α ≤ 4,0', 'min'=>3.50,'max'=>4.00,'bg'=>'#1E7A55','color'=>'#FFFFFF','desc'=>'Kinerja melampaui ekspektasi secara konsisten'],
            'SB' => ['label'=>'Sangat Baik', 'range'=>'2,5 < α ≤ 3,5', 'min'=>2.50,'max'=>3.50,'bg'=>'#A9D18E','color'=>'#1E4620','desc'=>'Kinerja melampaui target yang ditetapkan'],
            'B'  => ['label'=>'Baik',        'range'=>'1,5 < α ≤ 2,5', 'min'=>1.50,'max'=>2.50,'bg'=>'#FFC000','color'=>'#7F6000','desc'=>'Kinerja memenuhi target yang ditetapkan'],
            'C'  => ['label'=>'Cukup',       'range'=>'α ≤ 1,5',       'min'=>0,   'max'=>1.50,'bg'=>'#FCE4D6','color'=>'#C00000','desc'=>'Kinerja perlu ditingkatkan'],
        ];
    }
}
