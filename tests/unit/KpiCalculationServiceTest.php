<?php

use App\Services\KpiCalculationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class KpiCalculationServiceTest extends CIUnitTestCase
{
    private KpiCalculationService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new KpiCalculationService();
    }

    // ── hitungSkorCapaian() — KPI pegawai, skema band diskrit 1-4 ──
    // Kriteria: >110%=4, 100-110%=3, 80-<100%=2, <80%=1 (sama untuk
    // polarity max & min, arah rasio ditangani oleh formula pencapaian).

    public function testSkorCapaianTargetKosongSelaluBandTerendah(): void
    {
        $this->assertSame(1.0, $this->calculator->hitungSkorCapaian(50, 0, 'max'));
        $this->assertSame(1.0, $this->calculator->hitungSkorCapaian(0, 0, 'min'));
    }

    public function testSkorCapaianMaxDiBawah80PersenBandSatu(): void
    {
        $this->assertSame(1.0, $this->calculator->hitungSkorCapaian(79, 100, 'max'));
        $this->assertSame(1.0, $this->calculator->hitungSkorCapaian(0, 100, 'max'));
    }

    public function testSkorCapaianMaxAntara80Dan100PersenBandDua(): void
    {
        $this->assertSame(2.0, $this->calculator->hitungSkorCapaian(80, 100, 'max'));
        $this->assertSame(2.0, $this->calculator->hitungSkorCapaian(99.9, 100, 'max'));
    }

    public function testSkorCapaianMaxAntara100Dan110PersenBandTiga(): void
    {
        $this->assertSame(3.0, $this->calculator->hitungSkorCapaian(100, 100, 'max'));
        $this->assertSame(3.0, $this->calculator->hitungSkorCapaian(110, 100, 'max'));
    }

    public function testSkorCapaianMaxDiAtas110PersenBandEmpat(): void
    {
        $this->assertSame(4.0, $this->calculator->hitungSkorCapaian(110.01, 100, 'max'));
        $this->assertSame(4.0, $this->calculator->hitungSkorCapaian(4000, 3000, 'max')); // contoh nyata: 133%
    }

    public function testSkorCapaianMinRealisasiNolAdalahBandTerbaik(): void
    {
        // Realisasi 0 untuk KPI 'min' (mis. 0 kasus fraud) = capaian terbaik,
        // bukan "belum diisi" -> langsung band 4, tanpa pembagian dengan nol.
        $this->assertSame(4.0, $this->calculator->hitungSkorCapaian(0, 5, 'min'));
    }

    public function testSkorCapaianMinContohNyataFinancingAtRisk(): void
    {
        // Dari tabel kriteria: realisasi 510, target 500 (min-polarity)
        // -> pencapaian 500/510 = 98.04% -> band 2.
        $this->assertSame(2.0, $this->calculator->hitungSkorCapaian(510, 500, 'min'));
    }

    public function testSkorCapaianMinDiAtas110PersenBandEmpat(): void
    {
        // Realisasi jauh di bawah target (min-polarity, lebih kecil lebih baik).
        $this->assertSame(4.0, $this->calculator->hitungSkorCapaian(50, 100, 'min'));
    }

    public function testSkorCapaianMinDiBawah80PersenBandSatu(): void
    {
        // Realisasi jauh di atas target (min-polarity, buruk).
        $this->assertSame(1.0, $this->calculator->hitungSkorCapaian(200, 100, 'min'));
    }

    public function testSkorCapaianSelaluDalamRentangSatuSampaiEmpat(): void
    {
        $skor = $this->calculator->hitungSkorCapaian(1000, 1, 'min');
        $this->assertGreaterThanOrEqual(1.0, $skor);
        $this->assertLessThanOrEqual(4.0, $skor);
    }

    // ── hitungPencapaianPersen() — dipakai untuk kolom "Pencapaian" di tabel
    // penilaian, dan sebagai basis perhitungan hitungSkorCapaian() di atas ──

    public function testPencapaianPersenMax(): void
    {
        $this->assertEqualsWithDelta(120.0, $this->calculator->hitungPencapaianPersen(120, 100, 'max'), 0.001);
        $this->assertEqualsWithDelta(0.0, $this->calculator->hitungPencapaianPersen(0, 100, 'max'), 0.001);
    }

    public function testPencapaianPersenMin(): void
    {
        // Contoh nyata dari tabel kriteria: realisasi 510, target 500.
        $this->assertEqualsWithDelta(98.039, $this->calculator->hitungPencapaianPersen(510, 500, 'min'), 0.01);
    }

    public function testPencapaianPersenMinRealisasiNolMengembalikanInf(): void
    {
        $hasil = $this->calculator->hitungPencapaianPersen(0, 5, 'min');
        $this->assertTrue(is_infinite($hasil), 'Realisasi 0 pada KPI min harus menghasilkan rasio tak terhingga (INF), bukan pembagian dengan nol yang salah.');
    }

    public function testHitungSkorCapaianDanPencapaianPersenKonsisten(): void
    {
        // hitungSkorCapaian() sekarang membangun hasilnya di atas
        // hitungPencapaianPersen() — pastikan keduanya tetap sinkron
        // (band skor yang dihasilkan cocok dengan persentase mentahnya).
        $pencapaian = $this->calculator->hitungPencapaianPersen(97, 100, 'max');
        $skor       = $this->calculator->hitungSkorCapaian(97, 100, 'max');
        $this->assertEqualsWithDelta(97.0, $pencapaian, 0.001);
        $this->assertSame(2.0, $skor); // 80% <= 97% < 100% -> band 2
    }

    // ── hitungCapaian() — KPI unit/divisi, TIDAK berubah (skala rasio 0-1.5) ──

    public function testCapaianTargetKosongSelaluNol(): void
    {
        $this->assertSame(0.0, $this->calculator->hitungCapaian(0, 50, 'max', 'pos'));
    }

    public function testCapaianMaxPosRealisasiNolAdalahNol(): void
    {
        $this->assertSame(0.0, $this->calculator->hitungCapaian(100, 0, 'max', 'pos'));
    }

    public function testCapaianMaxPosRealisasiSamaDenganTarget(): void
    {
        $this->assertSame(1.0, $this->calculator->hitungCapaian(100, 100, 'max', 'pos'));
    }

    public function testCapaianMaxPosMelebihiTargetDibatasiSeratusLimaPuluhPersen(): void
    {
        $this->assertSame(1.5, $this->calculator->hitungCapaian(100, 500, 'max', 'pos'));
    }

    public function testCapaianMinRealisasiNolAdalahCapaianTerbaik(): void
    {
        $this->assertSame(1.5, $this->calculator->hitungCapaian(5, 0, 'min', 'pos'));
        $this->assertSame(1.5, $this->calculator->hitungCapaian(5, 0, 'min', 'neg'));
    }

    public function testCapaianMaxNegRealisasiNolJugaDianggapCapaianTerbaik(): void
    {
        $this->assertSame(1.5, $this->calculator->hitungCapaian(5, 0, 'max', 'neg'));
    }

    public function testCapaianMinRealisasiSamaDenganTarget(): void
    {
        $this->assertSame(1.0, $this->calculator->hitungCapaian(5, 5, 'min', 'pos'));
    }

    public function testCapaianMinRealisasiLebihBurukDariTarget(): void
    {
        $this->assertSame(0.5, $this->calculator->hitungCapaian(5, 10, 'min', 'pos'));
    }

    // ── hitungKontribusi() — Nilai (=Skor band 1-4) x Bobot ──

    public function testHitungKontribusi(): void
    {
        $this->assertSame(2.0, $this->calculator->hitungKontribusi(4, 0.5));
    }

    public function testHitungKontribusiContohNyataDariTabelKriteria(): void
    {
        // Pertumbuhan Outstanding: skor 4, bobot 35% -> kontribusi 1,40
        $this->assertEqualsWithDelta(1.40, $this->calculator->hitungKontribusi(4, 0.35), 0.0001);
        // Kualitas Financing at Risk: skor 2, bobot 25% -> kontribusi 0,50
        $this->assertEqualsWithDelta(0.50, $this->calculator->hitungKontribusi(2, 0.25), 0.0001);
    }

    // ── isValidSkor() ──

    public function testIsValidSkorBatasBawahDanAtas(): void
    {
        $this->assertTrue($this->calculator->isValidSkor(1));
        $this->assertTrue($this->calculator->isValidSkor(4));
        $this->assertFalse($this->calculator->isValidSkor(0.99));
        $this->assertFalse($this->calculator->isValidSkor(4.01));
    }

    // ── getGrade() — Yudisium, Kriteria Bobot Tertimbang (α) ──
    // Istimewa: 3,5 < α ≤ 4,0 | Sangat Baik: 2,5 < α ≤ 3,5
    // Baik: 1,5 < α ≤ 2,5 | Cukup: α ≤ 1,5
    // Batas bawah EKSKLUSIF, batas atas INKLUSIF — tepat di ambang masuk
    // pita di BAWAHNYA, bukan pita di atasnya.

    public function testGetGradeIstimewa(): void
    {
        $this->assertSame('IS', $this->calculator->getGrade(4.00));
        $this->assertSame('IS', $this->calculator->getGrade(3.51));
    }

    public function testGetGradeTepatDiAmbang35MasukSangatBaikBukanIstimewa(): void
    {
        // Batas bawah Istimewa EKSKLUSIF (>3,5) -> tepat 3,5 masuk pita
        // di bawahnya (Sangat Baik, batas atasnya INKLUSIF <=3,5).
        $this->assertSame('SB', $this->calculator->getGrade(3.50));
    }

    public function testGetGradeSangatBaik(): void
    {
        $this->assertSame('SB', $this->calculator->getGrade(3.49));
        $this->assertSame('SB', $this->calculator->getGrade(2.51));
    }

    public function testGetGradeTepatDiAmbang25MasukBaikBukanSangatBaik(): void
    {
        $this->assertSame('B', $this->calculator->getGrade(2.50));
    }

    public function testGetGradeBaik(): void
    {
        $this->assertSame('B', $this->calculator->getGrade(2.49));
        $this->assertSame('B', $this->calculator->getGrade(1.51));
    }

    public function testGetGradeTepatDiAmbang15MasukCukupBukanBaik(): void
    {
        $this->assertSame('C', $this->calculator->getGrade(1.50));
    }

    public function testGetGradeCukup(): void
    {
        $this->assertSame('C', $this->calculator->getGrade(1.49));
        $this->assertSame('C', $this->calculator->getGrade(1.00));
    }

    public function testGetGradeContohNyataDariTabelKriteria(): void
    {
        // Grand Total contoh nyata skema Kriteria Pencapaian (2,70) -> Sangat Baik
        $this->assertSame('SB', $this->calculator->getGrade(2.70));
    }

    public function testGetGradeLabelUntukSetiapGrade(): void
    {
        $this->assertSame('Istimewa', $this->calculator->getGradeLabel('IS'));
        $this->assertSame('Sangat Baik', $this->calculator->getGradeLabel('SB'));
        $this->assertSame('Baik', $this->calculator->getGradeLabel('B'));
        $this->assertSame('Cukup', $this->calculator->getGradeLabel('C'));
        $this->assertSame('—', $this->calculator->getGradeLabel('X'));
    }

    // ── hitungSkorPrecise() — polarity 'Precise is Better' ──
    // Toleransi contoh dari tabel: Skor4=2,5% Skor3=7,5% Skor2=12,5%.

    public function testSkorPreciseTepatDiTargetBandEmpat(): void
    {
        $this->assertSame(4.0, $this->calculator->hitungSkorPrecise(100, 100, 2.5, 7.5, 12.5));
    }

    public function testSkorPreciseDalamToleransiBandEmpatKeduaArah(): void
    {
        // 102,5% (batas atas Skor 4) dan 97,5% (batas bawah Skor 4)
        $this->assertSame(4.0, $this->calculator->hitungSkorPrecise(102.5, 100, 2.5, 7.5, 12.5));
        $this->assertSame(4.0, $this->calculator->hitungSkorPrecise(97.5, 100, 2.5, 7.5, 12.5));
    }

    public function testSkorPreciseSedikitDiLuarBandEmpatJadiBandTiga(): void
    {
        // 102,51% -> deviasi 2,51% -> sedikit di luar toleransi Skor 4
        $this->assertSame(3.0, $this->calculator->hitungSkorPrecise(102.51, 100, 2.5, 7.5, 12.5));
        $this->assertSame(3.0, $this->calculator->hitungSkorPrecise(92.5, 100, 2.5, 7.5, 12.5));
    }

    public function testSkorPreciseJauhDariTargetBandSatu(): void
    {
        $this->assertSame(1.0, $this->calculator->hitungSkorPrecise(130, 100, 2.5, 7.5, 12.5));
        $this->assertSame(1.0, $this->calculator->hitungSkorPrecise(50, 100, 2.5, 7.5, 12.5));
    }

    public function testSkorPreciseTargetKosongBandTerendah(): void
    {
        $this->assertSame(1.0, $this->calculator->hitungSkorPrecise(100, 0, 2.5, 7.5, 12.5));
    }

    // ── hitungSkorSpecial() — polarity 'Special Scoring' ──

    public function testSkorSpecialMaximizeAdaSkorEmpat(): void
    {
        $this->assertSame(4.0, $this->calculator->hitungSkorSpecial(true, 'maximize'));
    }

    public function testSkorSpecialMaximizeTidakAdaSkorSatu(): void
    {
        $this->assertSame(1.0, $this->calculator->hitungSkorSpecial(false, 'maximize'));
    }

    public function testSkorSpecialMinimizeAdaSkorSatu(): void
    {
        $this->assertSame(1.0, $this->calculator->hitungSkorSpecial(true, 'minimize'));
    }

    public function testSkorSpecialMinimizeTidakAdaSkorEmpat(): void
    {
        $this->assertSame(4.0, $this->calculator->hitungSkorSpecial(false, 'minimize'));
    }

    // ── hitungPengkaliHarian() & hitungSkorTertimbang() — polarity 'Scoring
    // Tertimbang'. Rata-rata Harian adalah PERSENTASE langsung (rata-rata
    // pencapaian harian selama periode, dihitung di luar sistem) — bukan
    // rasio realisasi/target seperti Indikator 1. Tidak ada "Target Harian".

    public function testPengkaliHarianDiAtas95PersenSeratusPersen(): void
    {
        $this->assertSame(1.00, $this->calculator->hitungPengkaliHarian(98));
    }

    public function testPengkaliHarianAntara90Dan95PersenSembilanPuluhLima(): void
    {
        $this->assertSame(0.95, $this->calculator->hitungPengkaliHarian(90));
        $this->assertSame(0.95, $this->calculator->hitungPengkaliHarian(92));
        $this->assertSame(0.95, $this->calculator->hitungPengkaliHarian(95));
    }

    public function testPengkaliHarianAntara85DanKurang90PersenSembilanPuluh(): void
    {
        $this->assertSame(0.90, $this->calculator->hitungPengkaliHarian(85));
        $this->assertSame(0.90, $this->calculator->hitungPengkaliHarian(87));
        $this->assertSame(0.90, $this->calculator->hitungPengkaliHarian(89.9));
    }

    public function testPengkaliHarianKurang85PersenDelapanPuluhLima(): void
    {
        $this->assertSame(0.85, $this->calculator->hitungPengkaliHarian(80));
    }

    // ── Contoh Perhitungan (persis sesuai spesifikasi 3-tahap) ──

    public function testSkorTertimbangContoh1(): void
    {
        // Target=100, Realisasi=120 -> Persentase 120% -> Skor 4
        // Rata-rata Harian=98% -> Pengkali 100% -> Skor Akhir=4
        $this->assertEqualsWithDelta(4.0, $this->calculator->hitungSkorTertimbang(120, 100, 98), 0.001);
    }

    public function testSkorTertimbangContoh2(): void
    {
        // Target=100, Realisasi=105 -> Persentase 105% -> Skor 3
        // Rata-rata Harian=92% -> Pengkali 95% -> Skor Akhir=3x0.95=2.85
        $this->assertEqualsWithDelta(2.85, $this->calculator->hitungSkorTertimbang(105, 100, 92), 0.001);
    }

    public function testSkorTertimbangContoh3(): void
    {
        // Target=100, Realisasi=82 -> Persentase 82% -> Skor 2
        // Rata-rata Harian=86% -> Pengkali 90% -> Skor Akhir=2x0.90=1.80
        $this->assertEqualsWithDelta(1.80, $this->calculator->hitungSkorTertimbang(82, 100, 86), 0.001);
    }

    // ── hitungSkor() — dispatcher tunggal berdasarkan polarity ──

    public function testHitungSkorDispatchKeMaxMinSepertiSebelumnya(): void
    {
        $skor = $this->calculator->hitungSkor(
            ['polarity' => 'max', 'target' => 100, 'is_capped' => true],
            ['realisasi' => 120]
        );
        $this->assertSame(4.0, $skor);
    }

    public function testHitungSkorDispatchKePrecise(): void
    {
        $skor = $this->calculator->hitungSkor(
            [
                'polarity'        => 'precise',
                'target'          => 100,
                'toleransi_skor4' => 2.5,
                'toleransi_skor3' => 7.5,
                'toleransi_skor2' => 12.5,
            ],
            ['realisasi' => 100]
        );
        $this->assertSame(4.0, $skor);
    }

    public function testHitungSkorDispatchKeSpecial(): void
    {
        $skor = $this->calculator->hitungSkor(
            ['polarity' => 'special', 'sifat_khusus' => 'maximize'],
            ['realisasi' => true]
        );
        $this->assertSame(4.0, $skor);
    }

    public function testHitungSkorDispatchKeTertimbang(): void
    {
        $skor = $this->calculator->hitungSkor(
            ['polarity' => 'tertimbang', 'target' => 100],
            ['realisasi' => 120, 'realisasi_harian' => 96]
        );
        $this->assertEqualsWithDelta(4.0, $skor, 0.001);
    }

    public function testIsValidPolarityUntukSemuaLimaJenis(): void
    {
        foreach (['max', 'min', 'precise', 'special', 'tertimbang'] as $p) {
            $this->assertTrue($this->calculator->isValidPolarity($p));
        }
        $this->assertFalse($this->calculator->isValidPolarity('unknown'));
    }

    // ── hitungCapaian() — regresi: polarity baru tidak boleh diam-diam
    // salah arah ke rumus minimize di modul Penilaian Unit (terpisah) ──

    public function testHitungCapaianPolarityBaruFallbackRasioMaxLike(): void
    {
        // realisasi 120 / target 100 -> 1.2, bukan dihitung sebagai minimize
        // (yang akan menghasilkan target/realisasi = 0.833 jika salah arah).
        $this->assertEqualsWithDelta(1.2, $this->calculator->hitungCapaian(100, 120, 'precise', 'pos'), 0.001);
        $this->assertEqualsWithDelta(1.2, $this->calculator->hitungCapaian(100, 120, 'special', 'pos'), 0.001);
        $this->assertEqualsWithDelta(1.2, $this->calculator->hitungCapaian(100, 120, 'tertimbang', 'pos'), 0.001);
    }
}
