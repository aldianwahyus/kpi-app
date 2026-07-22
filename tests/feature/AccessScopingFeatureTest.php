<?php

use Tests\Support\KpiTestCase;

/**
 * @internal
 */
final class AccessScopingFeatureTest extends KpiTestCase
{
    public function testDrafterTidakBisaMengaksesManajemenUser(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Drafter Satu');

        $result = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->get('master/users');

        $this->assertRedirectEndsWith($result, 'dashboard');
    }

    public function testAdminBisaMengaksesManajemenUser(): void
    {
        $result = $this->withSession($this->sessionFor('admin', 1))
            ->get('master/users');

        $result->assertOK();
    }

    public function testDrafterTidakBisaMengaksesPegawaiDiLuarDivisinya(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiA      = $this->makeDivisi($direktoratId, 'DVA');
        $divisiB      = $this->makeDivisi($direktoratId, 'DVB');

        $drafterPegawaiId = $this->makePegawai($divisiA, 'Drafter Satu');
        $targetLuarDivisi = $this->makePegawai($divisiB, 'Pegawai Divisi Lain');

        $periodeId = $this->makePeriodeAktif();

        $result = $this->withSession($this->sessionFor('drafter', 1, $drafterPegawaiId))
            ->get("penilaian/form/{$targetLuarDivisi}");

        // canAccessPegawai() menolak lintas-divisi -> forbidden() -> redirect ke dashboard
        $this->assertRedirectEndsWith($result, 'dashboard');
    }

    public function testDrafterBisaMengaksesPegawaiDiDivisinyaSendiri(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiA      = $this->makeDivisi($direktoratId, 'DVA');

        $drafterPegawaiId = $this->makePegawai($divisiA, 'Drafter Satu');
        $targetSatuDivisi = $this->makePegawai($divisiA, 'Rekan Sedivisi');

        $this->makePeriodeAktif();
        $kpiUnitId = $this->makeKpiUnit($direktoratId, 'TK-F1');
        $this->makeKpiPegawai($targetSatuDivisi, $divisiA, $kpiUnitId);

        $result = $this->withSession($this->sessionFor('drafter', 1, $drafterPegawaiId))
            ->get("penilaian/form/{$targetSatuDivisi}");

        $result->assertOK();
    }

    public function testApproverTidakBisaMengaksesApprovalPegawaiDiLuarDivisinya(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiA      = $this->makeDivisi($direktoratId, 'DVA');
        $divisiB      = $this->makeDivisi($direktoratId, 'DVB');

        $approverPegawaiId = $this->makePegawai($divisiA, 'Approver Satu');
        $targetLuarDivisi   = $this->makePegawai($divisiB, 'Pegawai Divisi Lain');

        $this->makePeriodeAktif();

        // approve() sendiri tidak memanggil canAccessPegawai secara eksplisit,
        // tapi form() (tempat approver biasanya masuk) yang membatasi akses —
        // di sini kita uji endpoint form sebagai representasi akses approver.
        $result = $this->withSession($this->sessionFor('approver', 1, $approverPegawaiId))
            ->get("penilaian/form/{$targetLuarDivisi}");

        $this->assertRedirectEndsWith($result, 'dashboard');
    }

    public function testPegawaiRoleTidakBisaAksesInputPenilaian(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Biasa');

        $result = $this->withSession($this->sessionFor('pegawai', 1, $pegawaiId))
            ->get('penilaian');

        // checkMenuAccess('penilaian') menolak role 'pegawai' sesuai matriks
        // role_permission saat ini -> forbidden() -> redirect ke dashboard.
        $this->assertRedirectEndsWith($result, 'dashboard');
    }
}
