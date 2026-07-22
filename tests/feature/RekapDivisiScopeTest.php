<?php

use App\Models\PenilaianModel;
use Tests\Support\KpiTestCase;

/**
 * Regresi untuk kebocoran data lintas-divisi yang pernah ditemukan di
 * LaporanController::pdf()/excel() dan AiController::buildContext() — baik
 * keduanya mendelegasikan filter divisi ke PenilaianModel::getRekapKombinasi().
 *
 * pdf()/excel() sendiri tidak dites lewat HTTP di sini karena keduanya
 * memanggil exit() langsung (lewat Dompdf::stream()/Xlsx->save('php://output')),
 * yang akan menghentikan proses PHPUnit itu sendiri jika dipanggil dalam test.
 * Fungsi query yang menjadi sumber scoping-nya diuji langsung di sini.
 *
 * @internal
 */
final class RekapDivisiScopeTest extends KpiTestCase
{
    public function testGetRekapKombinasiTanpaScopeMengembalikanSemuaDivisi(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiA      = $this->makeDivisi($direktoratId, 'DVA');
        $divisiB      = $this->makeDivisi($direktoratId, 'DVB');
        $pegawaiA     = $this->makePegawai($divisiA, 'Pegawai A');
        $pegawaiB     = $this->makePegawai($divisiB, 'Pegawai B');
        $periodeId    = $this->makePeriodeAktif();
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-F1');

        $model = new PenilaianModel();
        $model->insert(['pegawai_id' => $pegawaiA, 'kpi_id' => $kpiUnitId, 'periode_id' => $periodeId, 'realisasi' => 100, 'skor' => 100, 'nilai_kontribusi' => 100, 'status' => 'approved']);
        $model->insert(['pegawai_id' => $pegawaiB, 'kpi_id' => $kpiUnitId, 'periode_id' => $periodeId, 'realisasi' => 100, 'skor' => 100, 'nilai_kontribusi' => 100, 'status' => 'approved']);

        $rekap = $model->getRekapKombinasi($periodeId, null);

        $this->assertCount(2, $rekap);
    }

    public function testGetRekapKombinasiDenganScopeHanyaMengembalikanDivisiTerkait(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiA      = $this->makeDivisi($direktoratId, 'DVA');
        $divisiB      = $this->makeDivisi($direktoratId, 'DVB');
        $pegawaiA     = $this->makePegawai($divisiA, 'Pegawai A');
        $pegawaiB     = $this->makePegawai($divisiB, 'Pegawai B');
        $periodeId    = $this->makePeriodeAktif();
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-F1');

        $model = new PenilaianModel();
        $model->insert(['pegawai_id' => $pegawaiA, 'kpi_id' => $kpiUnitId, 'periode_id' => $periodeId, 'realisasi' => 100, 'skor' => 100, 'nilai_kontribusi' => 100, 'status' => 'approved']);
        $model->insert(['pegawai_id' => $pegawaiB, 'kpi_id' => $kpiUnitId, 'periode_id' => $periodeId, 'realisasi' => 100, 'skor' => 100, 'nilai_kontribusi' => 100, 'status' => 'approved']);

        // Simulasikan scope milik Approver di Divisi A — baris Divisi B
        // tidak boleh pernah ikut termuat, persis seperti yang seharusnya
        // diterapkan LaporanController::pdf()/excel() dan AiController.
        $rekap = $model->getRekapKombinasi($periodeId, $divisiA);

        $this->assertCount(1, $rekap);
        $this->assertSame($pegawaiA, (int) $rekap[0]['pegawai_id']);
    }

    public function testLaporanPdfMenghitungDivisiScopeUntukApprover(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiA      = $this->makeDivisi($direktoratId, 'DVA');
        $approverPegawaiId = $this->makePegawai($divisiA, 'Approver Satu');
        $this->makePeriodeAktif();

        // laporan_pdf tidak diberikan ke role approver secara default pada
        // MenuListSeeder -> checkMenuAccess akan menolak lebih dulu sebelum
        // sempat mencapai generatePdf()/exit(), sehingga aman dipanggil di
        // sini untuk memverifikasi guard akses menunya.
        $result = $this->withSession($this->sessionFor('approver', 1, $approverPegawaiId))
            ->get('laporan/pdf');

        $this->assertRedirectEndsWith($result, 'dashboard');
    }
}
