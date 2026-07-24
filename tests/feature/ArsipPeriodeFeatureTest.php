<?php

use App\Models\KpiPegawaiModel;
use App\Models\KpiPegawaiTurunanModel;
use App\Models\PenilaianArsipModel;
use App\Models\PenilaianTurunanArsipModel;
use App\Models\PeriodeModel;
use App\Services\PenilaianArsipService;
use Tests\Support\KpiTestCase;

/**
 * @internal
 */
final class ArsipPeriodeFeatureTest extends KpiTestCase
{
    private function setupPegawaiDenganPenilaianLengkap(): array
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Arsip');

        // KPI Induk tanpa Turunan (polarity max)
        $kpiUnitA  = $this->makeKpiUnit($direktoratId, 'AR-A1', 'max', 100);
        $kpA       = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitA, 0.5000, 100);

        // KPI Induk DENGAN Turunan
        $kpiUnitB  = $this->makeKpiUnit($direktoratId, 'AR-B1', 'max', 100);
        $kpB       = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitB, 0.5000, 100);
        $turunanId = (new KpiPegawaiTurunanModel())->insert([
            'kpi_pegawai_id' => $kpB,
            'nama_turunan'   => 'Sub Arsip 1',
            'bobot'          => 0.5000,
            'target'         => 50,
            'polarity'       => 'max',
            'is_active'      => 1,
        ]);

        $periodeId = $this->makePeriodeAktif('AR-P1');

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", [
                'realisasi'         => [$kpiUnitA => 110],
                'realisasi_turunan' => [$kpB => [$turunanId => 55]],
            ]);

        return compact('direktoratId', 'divisiId', 'pegawaiId', 'kpiUnitA', 'kpA', 'kpiUnitB', 'kpB', 'turunanId', 'periodeId');
    }

    public function testTutupPeriodeMengarsipkanSeluruhPenilaianTermasukTurunan(): void
    {
        $ctx = $this->setupPegawaiDenganPenilaianLengkap();

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->get("master/periode/status/{$ctx['periodeId']}/tutup");

        $result->assertRedirect();

        $arsip = (new PenilaianArsipModel())->where('periode_id', $ctx['periodeId'])->findAll();
        $this->assertCount(2, $arsip, 'Kedua baris KPI Induk (dengan & tanpa Turunan) harus diarsipkan.');

        $baris = null;
        foreach ($arsip as $a) {
            if ($a['kpi_id'] == $ctx['kpiUnitB']) { $baris = $a; break; }
        }
        $this->assertNotNull($baris, 'Baris arsip untuk KPI Induk ber-Turunan harus ada.');

        $turunanArsip = (new PenilaianTurunanArsipModel())
            ->where('penilaian_arsip_id', $baris['id'])->findAll();
        $this->assertCount(1, $turunanArsip, 'Parameter Turunan harus ikut diarsipkan.');
        $this->assertSame('Sub Arsip 1', $turunanArsip[0]['nama_turunan']);
        $this->assertEqualsWithDelta(55.0, (float)$turunanArsip[0]['realisasi'], 0.01);
    }

    public function testArsipTetapBekuWalauKpiPegawaiDiubahSetelahDitutup(): void
    {
        $ctx = $this->setupPegawaiDenganPenilaianLengkap();

        (new PenilaianArsipService())->arsipkanPeriode($ctx['periodeId']);
        (new PeriodeModel())->update($ctx['periodeId'], ['status' => 'tutup']);

        $arsipSebelum = (new PenilaianArsipModel())
            ->where('periode_id', $ctx['periodeId'])
            ->where('kpi_id', $ctx['kpiUnitA'])
            ->first();
        $this->assertEqualsWithDelta(0.5, (float)$arsipSebelum['bobot'], 0.001);

        // Ubah Bobot Tahunan LIVE di Master Target (mensimulasikan admin
        // mengubah bobot untuk periode berikutnya) — arsip TIDAK boleh ikut
        // berubah.
        $this->makeKpiPegawaiBobotTahunan($ctx['kpA'], 2026, 0.9999);

        $arsipSesudah = (new PenilaianArsipModel())
            ->where('periode_id', $ctx['periodeId'])
            ->where('kpi_id', $ctx['kpiUnitA'])
            ->first();
        $this->assertEqualsWithDelta(
            0.5, (float)$arsipSesudah['bobot'], 0.001,
            'Bobot pada arsip harus tetap 0.5 (nilai saat ditutup), tidak boleh ikut berubah mengikuti Master Target terkini.'
        );
    }

    public function testArsipTetapUtuhWalauKpiPegawaiDihapusSetelahDitutup(): void
    {
        $ctx = $this->setupPegawaiDenganPenilaianLengkap();
        (new PenilaianArsipService())->arsipkanPeriode($ctx['periodeId']);

        // Hapus KPI dari pegawai (mensimulasikan restrukturisasi KPI untuk
        // periode berikutnya) — data arsip periode LAMA harus tetap utuh.
        (new KpiPegawaiTurunanModel())->where('kpi_pegawai_id', $ctx['kpB'])->delete();
        (new KpiPegawaiModel())->delete($ctx['kpA']);
        (new KpiPegawaiModel())->delete($ctx['kpB']);

        $jumlahArsip = (new PenilaianArsipModel())->where('periode_id', $ctx['periodeId'])->countAllResults();
        $this->assertSame(2, $jumlahArsip, 'Baris arsip tidak boleh hilang walau kpi_pegawai sumbernya sudah dihapus.');
    }

    public function testArsipkanUlangBersifatIdempotenTidakMenduplikasi(): void
    {
        $ctx = $this->setupPegawaiDenganPenilaianLengkap();
        $service = new PenilaianArsipService();

        $service->arsipkanPeriode($ctx['periodeId']);
        $jumlahPertama = (new PenilaianArsipModel())->where('periode_id', $ctx['periodeId'])->countAllResults();

        // Ubah Bobot Tahunan live lalu arsipkan ULANG (mensimulasikan
        // buka-kembali lalu tutup-lagi) — arsip harus MENCERMINKAN kondisi
        // terbaru, bukan menumpuk duplikat.
        $this->makeKpiPegawaiBobotTahunan($ctx['kpA'], 2026, 0.75);
        $service->arsipkanPeriode($ctx['periodeId']);

        $jumlahKedua = (new PenilaianArsipModel())->where('periode_id', $ctx['periodeId'])->countAllResults();
        $this->assertSame($jumlahPertama, $jumlahKedua, 'Arsip ulang tidak boleh menduplikasi baris.');

        $arsipTerbaru = (new PenilaianArsipModel())
            ->where('periode_id', $ctx['periodeId'])
            ->where('kpi_id', $ctx['kpiUnitA'])
            ->first();
        $this->assertEqualsWithDelta(0.75, (float)$arsipTerbaru['bobot'], 0.001, 'Arsip ulang harus mencerminkan bobot TERBARU saat pengarsipan dilakukan.');
    }

    public function testNonAdminTidakBisaMengaksesArsipPeriode(): void
    {
        $ctx = $this->setupPegawaiDenganPenilaianLengkap();
        (new PenilaianArsipService())->arsipkanPeriode($ctx['periodeId']);
        (new PeriodeModel())->update($ctx['periodeId'], ['status' => 'tutup']);

        $result = $this->withSession($this->sessionFor('hr', 2))
            ->get('arsip-periode');

        $result->assertRedirectTo(base_url('dashboard'));
    }

    public function testAdminBisaMengaksesDaftarDanDetailArsipPeriode(): void
    {
        $ctx = $this->setupPegawaiDenganPenilaianLengkap();
        (new PenilaianArsipService())->arsipkanPeriode($ctx['periodeId']);
        (new PeriodeModel())->update($ctx['periodeId'], ['status' => 'tutup']);

        $resultIndex  = $this->withSession($this->sessionFor('admin', 1))->get('arsip-periode');
        $resultIndex->assertOK();

        $resultDetail = $this->withSession($this->sessionFor('admin', 1))
            ->get("arsip-periode/detail/{$ctx['periodeId']}");
        $resultDetail->assertOK();

        $resultPegawai = $this->withSession($this->sessionFor('admin', 1))
            ->get("arsip-periode/detail/{$ctx['periodeId']}/pegawai/{$ctx['pegawaiId']}");
        $resultPegawai->assertOK();
    }

    public function testDetailArsipDitolakJikaPeriodeBelumDitutup(): void
    {
        $ctx = $this->setupPegawaiDenganPenilaianLengkap(); // periode masih 'aktif'

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->get("arsip-periode/detail/{$ctx['periodeId']}");

        $result->assertRedirect();
    }

    public function testRekapPeriodeArsipMenghitungNilaiAkhirDenganBenar(): void
    {
        $ctx = $this->setupPegawaiDenganPenilaianLengkap();
        (new PenilaianArsipService())->arsipkanPeriode($ctx['periodeId']);

        $rekap = (new PenilaianArsipModel())->getRekapPeriode($ctx['periodeId']);
        $this->assertCount(1, $rekap);
        $this->assertSame($ctx['pegawaiId'], (int)$rekap[0]['pegawai_id']);

        // KPI A: realisasi 110/target 100 -> 110% -> Skor 3, bobot 0.5 -> kontribusi 1.5
        // KPI B (Turunan): realisasi 55/target 50 -> 110% -> Skor 3, bobot Turunan 0.5,
        // Skor Induk (Cara B) = 3, bobot Induk 0.5 -> kontribusi 1.5
        // Total Nilai Akhir = 1.5 + 1.5 = 3.0
        $this->assertEqualsWithDelta(3.0, (float)$rekap[0]['nilai_akhir'], 0.05);
    }

    // ── exportExcel()/exportPdf() diakhiri exit() setelah menulis file
    // biner ke output — TIDAK aman dipanggil sungguhan di sini (akan
    // menghentikan seluruh proses PHPUnit, bukan cuma test ini), sama
    // seperti pola yang sudah ada untuk LaporanController::pdf()/excel()
    // di RekapDivisiScopeTest. Yang diverifikasi di sini HANYA guard
    // "periode belum ditutup" yang me-redirect SEBELUM mencapai exit().
    public function testExportDitolakJikaPeriodeBelumDitutup(): void
    {
        $ctx = $this->setupPegawaiDenganPenilaianLengkap(); // periode masih 'aktif'

        $resultExcel = $this->withSession($this->sessionFor('admin', 1))
            ->get("arsip-periode/export-excel/{$ctx['periodeId']}");
        $resultExcel->assertRedirect();

        $resultPdf = $this->withSession($this->sessionFor('admin', 1))
            ->get("arsip-periode/export-pdf/{$ctx['periodeId']}");
        $resultPdf->assertRedirect();
    }

    public function testExportDitolakUntukNonAdminSebelumMencapaiGenerasiFile(): void
    {
        $ctx = $this->setupPegawaiDenganPenilaianLengkap();
        (new PenilaianArsipService())->arsipkanPeriode($ctx['periodeId']);
        (new PeriodeModel())->update($ctx['periodeId'], ['status' => 'tutup']);

        $result = $this->withSession($this->sessionFor('hr', 2))
            ->get("arsip-periode/export-excel/{$ctx['periodeId']}");
        $result->assertRedirectTo(base_url('dashboard'));
    }
}
