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

    // ── hitungSkorCapaian() — KPI pegawai (skala skor 10-100/150) ──

    public function testSkorCapaianTargetKosongSelaluSepuluh(): void
    {
        $this->assertSame(10.0, $this->calculator->hitungSkorCapaian(50, 0, 'max'));
        $this->assertSame(10.0, $this->calculator->hitungSkorCapaian(0, 0, 'min'));
    }

    public function testSkorCapaianMaxRealisasiNolAdalahSkorTerendah(): void
    {
        // Polarity 'max': realisasi 0 = belum ada progres nyata -> skor lantai.
        $this->assertSame(10.0, $this->calculator->hitungSkorCapaian(0, 100, 'max'));
    }

    public function testSkorCapaianMaxRealisasiSamaDenganTarget(): void
    {
        $this->assertSame(100.0, $this->calculator->hitungSkorCapaian(100, 100, 'max'));
    }

    public function testSkorCapaianMaxMelebihiTargetDibatasiSaatCapped(): void
    {
        $this->assertSame(100.0, $this->calculator->hitungSkorCapaian(200, 100, 'max', true));
    }

    public function testSkorCapaianMaxMelebihiTargetTidakDibatasiSaatUncapped(): void
    {
        // 150/100 = 150%, tapi tetap dibatasi plafon keras 150 pada baris clamp akhir.
        $this->assertSame(150.0, $this->calculator->hitungSkorCapaian(150, 100, 'max', false));
    }

    public function testSkorCapaianMinRealisasiNolAdalahCapaianTerbaikSaatCapped(): void
    {
        // Realisasi 0 untuk KPI 'min' (mis. 0 kasus fraud) = capaian terbaik,
        // bukan "belum diisi" -> skor maksimum, bukan hasil bagi-nol.
        $this->assertSame(100.0, $this->calculator->hitungSkorCapaian(0, 5, 'min', true));
    }

    public function testSkorCapaianMinRealisasiNolTanpaCapDibatasiPlafonAtas(): void
    {
        $this->assertSame(150.0, $this->calculator->hitungSkorCapaian(0, 5, 'min', false));
    }

    public function testSkorCapaianMinRealisasiSamaDenganTarget(): void
    {
        $this->assertSame(100.0, $this->calculator->hitungSkorCapaian(5, 5, 'min'));
    }

    public function testSkorCapaianMinRealisasiLebihBurukDariTarget(): void
    {
        // Realisasi 10 pada target 5 (min-polarity, makin kecil makin baik) = capaian buruk.
        $skor = $this->calculator->hitungSkorCapaian(10, 5, 'min');
        $this->assertSame(50.0, $skor);
    }

    public function testSkorCapaianMinRealisasiLebihBaikDariTargetDibatasiSaatCapped(): void
    {
        // Realisasi 2 pada target 5 (min-polarity) = rasio 2.5x, dibatasi ke 100 saat capped.
        $this->assertSame(100.0, $this->calculator->hitungSkorCapaian(2, 5, 'min', true));
    }

    public function testSkorCapaianSelaluDalamRentangSepuluhSampaiSeratusLimaPuluh(): void
    {
        $skor = $this->calculator->hitungSkorCapaian(1000, 1, 'min', false);
        $this->assertGreaterThanOrEqual(10.0, $skor);
        $this->assertLessThanOrEqual(150.0, $skor);
    }

    // ── hitungCapaian() — KPI unit/divisi (skala rasio 0-1.5) ──

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
        // Polarity 'min' (perubahan apa pun): realisasi 0 = capaian terbaik -> 1.5 (plafon).
        $this->assertSame(1.5, $this->calculator->hitungCapaian(5, 0, 'min', 'pos'));
        $this->assertSame(1.5, $this->calculator->hitungCapaian(5, 0, 'min', 'neg'));
    }

    public function testCapaianMaxNegRealisasiNolJugaDianggapCapaianTerbaik(): void
    {
        // polarity='max' dengan perubahan='neg' masuk cabang yang sama dengan 'min'.
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

    // ── hitungKontribusi() ──

    public function testHitungKontribusi(): void
    {
        $this->assertSame(50.0, $this->calculator->hitungKontribusi(100, 0.5));
    }

    public function testHitungKontribusiMembatasiSkorKeRentangSepuluhSampaiSeratus(): void
    {
        // Skor di luar 10-100 tetap di-clamp sebelum dikalikan bobot.
        $this->assertSame(10.0, $this->calculator->hitungKontribusi(0, 1));
        $this->assertSame(100.0, $this->calculator->hitungKontribusi(999, 1));
    }

    // ── isValidSkor() ──

    public function testIsValidSkorBatasBawahDanAtas(): void
    {
        $this->assertTrue($this->calculator->isValidSkor(10));
        $this->assertTrue($this->calculator->isValidSkor(100));
        $this->assertFalse($this->calculator->isValidSkor(9.99));
        $this->assertFalse($this->calculator->isValidSkor(100.01));
    }

    // ── getGrade() — batas antar grade ──

    public function testGetGradeBatasAmbang(): void
    {
        $this->assertSame('M', $this->calculator->getGrade(91.00));
        $this->assertSame('SB', $this->calculator->getGrade(90.99));
        $this->assertSame('SB', $this->calculator->getGrade(81.00));
        $this->assertSame('B', $this->calculator->getGrade(80.99));
        $this->assertSame('B', $this->calculator->getGrade(71.00));
        $this->assertSame('C', $this->calculator->getGrade(70.99));
        $this->assertSame('C', $this->calculator->getGrade(0));
    }

    public function testGetGradeLabelUntukSetiapGrade(): void
    {
        $this->assertSame('Memuaskan', $this->calculator->getGradeLabel('M'));
        $this->assertSame('Sangat Baik', $this->calculator->getGradeLabel('SB'));
        $this->assertSame('Baik', $this->calculator->getGradeLabel('B'));
        $this->assertSame('Cukup', $this->calculator->getGradeLabel('C'));
        $this->assertSame('—', $this->calculator->getGradeLabel('X'));
    }
}
