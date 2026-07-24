<?php

use App\Models\KpiPegawaiTurunanModel;
use App\Models\KpiUnitModel;
use Tests\Support\KpiTestCase;

/**
 * @internal
 *
 * Regresi: "Perubahan Polarity" (pos/neg) tidak lagi diinput manual —
 * sebelumnya field ini bisa disetel bertentangan dengan Polarity (mis.
 * Polarity=Maximize tapi Perubahan=Negatif), yang membuat rumus capaian
 * di KpiCalculationService::hitungCapaian() diam-diam terbalik arah
 * (Target/Realisasi alih-alih Realisasi/Target) walau label KPI-nya
 * mengaku Maximize. Sekarang nilainya SELALU diturunkan dari Polarity
 * yang dipilih, mengabaikan apa pun yang dikirim lewat POST/import.
 */
final class KpiUnitPolarityAutoSyncFeatureTest extends KpiTestCase
{
    public function testKpiUnitStoreMengabaikanPerubahanPolarityDariPostUntukMax(): void
    {
        $direktoratId = $this->makeDirektorat();

        $this->withSession($this->sessionFor('admin', 1))
            ->post("master/kpi-unit/{$direktoratId}/store", [
                'perspektif'         => 'Financial',
                'nama_kpi'           => 'KPI Max Anti Mismatch',
                'satuan'             => '%',
                'urutan'             => 1,
                'polarity'           => 'max',
                // Sengaja dikirim bertentangan — harus diabaikan sepenuhnya.
                'perubahan_polarity' => 'neg',
            ]);

        $kpi = (new KpiUnitModel())->where('nama_kpi', 'KPI Max Anti Mismatch')->first();
        $this->assertNotNull($kpi);
        $this->assertSame('pos', $kpi['perubahan_polarity'], 'Polarity Maximize harus selalu tersimpan dengan Perubahan=pos, apa pun yang dikirim lewat POST.');
    }

    public function testKpiUnitUpdateMengabaikanPerubahanPolarityDariPostUntukMin(): void
    {
        $direktoratId = $this->makeDirektorat();
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'KU-MIN1', 'max', 100);

        $this->withSession($this->sessionFor('admin', 1))
            ->post("master/kpi-unit/update/{$kpiUnitId}", [
                'perspektif'         => 'Financial',
                'kode'               => 'KU-MIN1',
                'nama_kpi'           => 'KPI Min Anti Mismatch',
                'satuan'             => '%',
                'urutan'             => 1,
                'polarity'           => 'min',
                // Sengaja dikirim bertentangan — harus diabaikan sepenuhnya.
                'perubahan_polarity' => 'pos',
            ]);

        $kpi = (new KpiUnitModel())->find($kpiUnitId);
        $this->assertSame('min', $kpi['polarity']);
        $this->assertSame('neg', $kpi['perubahan_polarity'], 'Polarity Minimize harus selalu tersimpan dengan Perubahan=neg, apa pun yang dikirim lewat POST.');
    }

    public function testAddTurunanMengabaikanPerubahanPolarityDariPostUntukMin(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Turunan Anti Mismatch');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-AM1', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $this->withSession($this->sessionFor('admin', 1))
            ->post("kpi-pegawai/turunan/add/{$kpId}", [
                'nama_turunan'       => 'Sub Min Anti Mismatch',
                'polarity'           => 'min',
                'perubahan_polarity' => 'pos',
            ]);

        $turunan = (new KpiPegawaiTurunanModel())->where('kpi_pegawai_id', $kpId)->first();
        $this->assertNotNull($turunan);
        $this->assertSame('neg', $turunan['perubahan_polarity'], 'Turunan Polarity Minimize harus selalu tersimpan dengan Perubahan=neg.');
    }

    public function testUpdateTurunanMengabaikanPerubahanPolarityDariPostUntukMax(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Turunan Anti Mismatch 2');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-AM2', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $this->withSession($this->sessionFor('admin', 1))
            ->post("kpi-pegawai/turunan/add/{$kpId}", [
                'nama_turunan' => 'Sub Untuk Diupdate',
                'polarity'     => 'min',
            ]);
        $turunan = (new KpiPegawaiTurunanModel())->where('kpi_pegawai_id', $kpId)->first();

        $this->withSession($this->sessionFor('admin', 1))
            ->post("kpi-pegawai/turunan/update/{$turunan['id']}", [
                'nama_turunan'       => 'Sub Untuk Diupdate',
                'polarity'           => 'max',
                'perubahan_polarity' => 'neg',
            ]);

        $turunanAfter = (new KpiPegawaiTurunanModel())->find($turunan['id']);
        $this->assertSame('max', $turunanAfter['polarity']);
        $this->assertSame('pos', $turunanAfter['perubahan_polarity'], 'Turunan Polarity Maximize harus selalu tersimpan dengan Perubahan=pos.');
    }
}
