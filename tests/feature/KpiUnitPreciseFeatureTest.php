<?php

use App\Models\KpiUnitModel;
use App\Models\PenilaianModel;
use Tests\Support\KpiTestCase;

/**
 * @internal
 *
 * Verifikasi end-to-end bahwa form "Tambah KPI Unit" pada Modul Direktorat
 * benar-benar mendukung setup polarity 'Precise is Better' sesuai mekanisme
 * perhitungannya — dari pengisian form Admin, tersimpan ke database, sampai
 * benar-benar dipakai saat penginputan Penilaian menghasilkan Skor yang
 * sesuai. Sebelumnya endpoint MasterController::kpiUnitStore()/Update()
 * sama sekali belum punya cakupan test.
 */
final class KpiUnitPreciseFeatureTest extends KpiTestCase
{
    public function testAdminBisaMembuatKpiUnitPreciseLewatFormDenganToleransiValid(): void
    {
        $direktoratId = $this->makeDirektorat();

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master/kpi-unit/{$direktoratId}/store", [
                'perspektif'      => 'Financial',
                'nama_kpi'        => 'KPI Precise Test',
                'satuan'          => '%',
                'urutan'          => 1,
                'polarity'        => 'precise',
                'toleransi_skor4' => 2.5,
                'toleransi_skor3' => 7.5,
                'toleransi_skor2' => 12.5,
            ]);

        $result->assertRedirect();

        $kpi = (new KpiUnitModel())->where('nama_kpi', 'KPI Precise Test')->first();
        $this->assertNotNull($kpi, 'KPI Unit precise harus tersimpan lewat form Tambah.');
        $this->assertSame('precise', $kpi['polarity']);
        $this->assertEqualsWithDelta(2.5, (float)$kpi['toleransi_skor4'], 0.001);
        $this->assertEqualsWithDelta(7.5, (float)$kpi['toleransi_skor3'], 0.001);
        $this->assertEqualsWithDelta(12.5, (float)$kpi['toleransi_skor2'], 0.001);
    }

    public function testAdminGagalMembuatKpiUnitPreciseTanpaMengisiToleransi(): void
    {
        $direktoratId = $this->makeDirektorat();

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master/kpi-unit/{$direktoratId}/store", [
                'perspektif' => 'Financial',
                'nama_kpi'   => 'KPI Precise Tanpa Toleransi',
                'satuan'     => '%',
                'urutan'     => 1,
                'polarity'   => 'precise',
                // toleransi_skor4/3/2 sengaja tidak dikirim
            ]);

        $result->assertRedirect();
        $this->assertNull(
            (new KpiUnitModel())->where('nama_kpi', 'KPI Precise Tanpa Toleransi')->first(),
            'Toleransi wajib diisi — KPI tidak boleh tersimpan tanpanya.'
        );
    }

    public function testAdminGagalMembuatKpiUnitPreciseJikaToleransiTidakMenaik(): void
    {
        $direktoratId = $this->makeDirektorat();

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master/kpi-unit/{$direktoratId}/store", [
                'perspektif'      => 'Financial',
                'nama_kpi'        => 'KPI Precise Toleransi Salah Urutan',
                'satuan'          => '%',
                'urutan'          => 1,
                'polarity'        => 'precise',
                'toleransi_skor4' => 10,  // seharusnya paling kecil
                'toleransi_skor3' => 5,
                'toleransi_skor2' => 12.5,
            ]);

        $result->assertRedirect();
        $this->assertNull(
            (new KpiUnitModel())->where('nama_kpi', 'KPI Precise Toleransi Salah Urutan')->first(),
            'Toleransi harus menaik (Skor4 < Skor3 < Skor2) — urutan salah harus ditolak.'
        );
    }

    public function testAdminBisaMengubahToleransiKpiUnitPreciseLewatFormEdit(): void
    {
        $direktoratId = $this->makeDirektorat();
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'KU-PR1', 'precise', 100, [
            'toleransi_skor4' => 2.5, 'toleransi_skor3' => 7.5, 'toleransi_skor2' => 12.5,
        ]);

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master/kpi-unit/update/{$kpiUnitId}", [
                'perspektif'      => 'Financial',
                'kode'            => 'KU-PR1',
                'nama_kpi'        => 'KPI Precise Updated',
                'satuan'          => '%',
                'urutan'          => 1,
                'polarity'        => 'precise',
                'toleransi_skor4' => 1.0,
                'toleransi_skor3' => 5.0,
                'toleransi_skor2' => 10.0,
            ]);

        $result->assertRedirect();
        $kpi = (new KpiUnitModel())->find($kpiUnitId);
        $this->assertEqualsWithDelta(1.0, (float)$kpi['toleransi_skor4'], 0.001);
        $this->assertEqualsWithDelta(5.0, (float)$kpi['toleransi_skor3'], 0.001);
        $this->assertEqualsWithDelta(10.0, (float)$kpi['toleransi_skor2'], 0.001);
    }

    // ── End-to-end: KPI Unit dibuat lewat form Admin -> di-assign ke
    // pegawai -> dinilai lewat form Penilaian sungguhan -> Skor yang
    // tersimpan harus sesuai Contoh Perhitungan pada spesifikasi. Ini
    // membuktikan seluruh pipa (form Direktorat -> database -> kalkulasi
    // Penilaian) benar-benar tersambung, bukan cuma fungsi kalkulator
    // yang diuji terisolasi. ──

    public function testKpiUnitPreciseBuatanFormMenghasilkanSkorBenarSaatDinilai(): void
    {
        $direktoratId = $this->makeDirektorat();

        // 1. Admin membuat KPI Unit precise lewat form sungguhan.
        $this->withSession($this->sessionFor('admin', 1))
            ->post("master/kpi-unit/{$direktoratId}/store", [
                'perspektif'      => 'Financial',
                'nama_kpi'        => 'KPI Precise E2E',
                'satuan'          => '%',
                'urutan'          => 1,
                'polarity'        => 'precise',
                'toleransi_skor4' => 2.5,
                'toleransi_skor3' => 7.5,
                'toleransi_skor2' => 12.5,
            ]);
        $kpiUnit = (new KpiUnitModel())->where('nama_kpi', 'KPI Precise E2E')->first();
        $this->assertNotNull($kpiUnit);

        // 2. Assign ke pegawai dengan Target=100, bobot 100%.
        $divisiId  = $this->makeDivisi($direktoratId, 'DVE');
        $pegawaiId = $this->makePegawai($divisiId, 'Pegawai Precise E2E');
        $this->makeKpiPegawai($pegawaiId, $divisiId, (int)$kpiUnit['id'], 1.0000, 100);
        $this->makePeriodeAktif();

        // 3. Nilai lewat form Penilaian sungguhan — Realisasi=104 (Contoh 3
        // pada spesifikasi) -> Persentase 104% -> Skor 3.
        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", [
                'realisasi' => [$kpiUnit['id'] => 104],
            ]);

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiUnit['id'])->first();
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(3.0, (float)$row['skor'], 0.01);
    }
}
