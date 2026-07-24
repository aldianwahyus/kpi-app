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
        // Dibuat SETELAH makePeriodeAktif(), jadi tidak ikut ter-auto-seed —
        // Bobot Tahunan & Target Bulanan untuk Periode ini (Juni 2026) perlu
        // di-set eksplisit di sini.
        $kpId = $this->makeKpiPegawai($targetSatuDivisi, $divisiA, $kpiUnitId);
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 1.0000);
        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 6, 100);

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

    // ── Regresi: "Bisa Lihat" vs "Bisa Edit" — sebelumnya can_edit=0 tidak
    // pernah dicek di manapun, sehingga role dengan akses lihat saja tetap
    // bisa menyimpan/mengubah data lewat menu tersebut.
    public function testRoleDenganCanViewSajaTidakBisaMenyimpanData(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');

        // HR punya can_view=1 & can_edit=1 untuk 'pegawai' secara default —
        // cabut can_edit-nya saja, pertahankan can_view.
        $this->setPermission('hr', 'pegawai', true, false);

        $viewResult = $this->withSession($this->sessionFor('hr', 1))
            ->get('pegawai');
        $viewResult->assertOK();

        $storeResult = $this->withSession($this->sessionFor('hr', 1))
            ->post('pegawai/store', [
                'nama'      => 'Pegawai Baru',
                'divisi_id' => $divisiId,
            ]);

        $this->assertRedirectEndsWith($storeResult, 'dashboard');
        $this->assertSame(
            0,
            (new \App\Models\PegawaiModel())->where('nama', 'Pegawai Baru')->countAllResults(),
            'can_edit=0 harus mencegah penyimpanan meski can_view=1.'
        );
    }

    // ── Regresi: MasterController sebelumnya hardcode admin/hr di
    // constructor, mengabaikan sama sekali tabel role_permission untuk
    // Direktorat & KPI Unit.
    public function testAksesDirektoratMengikutiRolePermissionBukanHardcode(): void
    {
        $blocked = $this->withSession($this->sessionFor('drafter', 1))
            ->get('master/direktorat');
        $this->assertRedirectEndsWith($blocked, 'dashboard');

        $this->setPermission('drafter', 'master_direktorat', true, false);

        $allowed = $this->withSession($this->sessionFor('drafter', 1))
            ->get('master/direktorat');
        $allowed->assertOK();
    }

    // ── Regresi: RekapController sebelumnya memeriksa checkMenuAccess('penilaian')
    // alih-alih 'rekap' — drafter yang tidak diberi akses Rekap tetap bisa
    // membuka halamannya lewat URL langsung.
    public function testAksesRekapMemeriksaKodeMenuRekapBukanPenilaian(): void
    {
        $blocked = $this->withSession($this->sessionFor('drafter', 1))
            ->get('rekap');
        $this->assertRedirectEndsWith($blocked, 'dashboard');

        $this->setPermission('drafter', 'rekap', true, true);

        $allowed = $this->withSession($this->sessionFor('drafter', 1))
            ->get('rekap');
        $allowed->assertOK();
    }

    // ── Regresi: approve()/reject() sebelumnya tidak memanggil
    // canAccessPegawai(), sehingga Approver bisa approve/reject pegawai di
    // divisi manapun, bukan hanya divisinya sendiri.
    public function testApproverTidakBisaMengApprovePegawaiDiLuarDivisinya(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiA      = $this->makeDivisi($direktoratId, 'DVA');
        $divisiB      = $this->makeDivisi($direktoratId, 'DVB');

        $approverPegawaiId = $this->makePegawai($divisiA, 'Approver Satu');
        $targetLuarDivisi   = $this->makePegawai($divisiB, 'Pegawai Divisi Lain');

        $result = $this->withSession($this->sessionFor('approver', 1, $approverPegawaiId))
            ->post("penilaian/approve/{$targetLuarDivisi}");

        $this->assertRedirectEndsWith($result, 'dashboard');
    }

    // ── Regresi: menu "Approval Penilaian" bisa ditoggle di layar Hak Akses
    // Role tapi sebelumnya tidak pernah dicek di approve()/reject() —
    // mencabutnya seharusnya benar-benar memblokir approve, bukan hanya
    // kosmetik.
    public function testCanEditApprovalDicabutMemblokirApproveWalauSatuDivisi(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiA      = $this->makeDivisi($direktoratId, 'DVA');
        $approverPegawaiId = $this->makePegawai($divisiA, 'Approver Satu');

        $this->setPermission('approver', 'approval', true, false);

        $result = $this->withSession($this->sessionFor('approver', 1, $approverPegawaiId))
            ->post("penilaian/approve/{$approverPegawaiId}");

        $this->assertRedirectEndsWith($result, 'dashboard');
    }

    // ── Regresi: PenilaianUnitController sebelumnya sama sekali tidak
    // memeriksa role_permission (hanya hardcode role list di
    // checkDivisiAccess()) untuk menu 'penilaian_unit'.
    public function testAksesPenilaianUnitMengikutiRolePermission(): void
    {
        $blocked = $this->withSession($this->sessionFor('drafter', 1))
            ->get('penilaian-unit');
        $this->assertRedirectEndsWith($blocked, 'dashboard');

        $this->setPermission('drafter', 'penilaian_unit', true, true);

        $allowed = $this->withSession($this->sessionFor('drafter', 1))
            ->get('penilaian-unit');
        $allowed->assertOK();
    }

    // ── Regresi: sidebar Master Data/Tools/Laporan sebelumnya menggantungkan
    // tampilnya header section pada kode grup terpisah ('master_data',
    // 'tools', 'laporan') yang independen dari izin per-item — akibatnya
    // menu yang SUDAH diberi izin sendiri (mis. 'kpi_pegawai') tetap
    // tersembunyi selama toggle grup itu sendiri belum ikut dicentang,
    // padahal controller-nya (checkMenuAccess('kpi_pegawai')) sudah
    // benar-benar mengizinkan akses lewat URL langsung.
    public function testMenuYangDiberiIzinSendiriMunculDiSidebarWalauGrupBelumDicentang(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Drafter Satu');

        // Hanya beri izin item spesifik 'kpi_pegawai' — 'master_data' (grup)
        // sengaja TIDAK disentuh (tetap 0, sesuai default drafter).
        $this->setPermission('drafter', 'kpi_pegawai', true, true);

        $result = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->get('dashboard');

        $result->assertOK();
        $result->assertSeeLink('KPI Per Pegawai');
    }

    public function testMenuTanpaIzinSamaSekaliTetapTidakMunculDiSidebar(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Drafter Satu');

        $result = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->get('dashboard');

        $result->assertOK();
        $result->assertDontSee('KPI Per Pegawai');
        $result->assertDontSee('Manajemen User');
    }

    // ── Regresi: "Profil" (ganti password sendiri) sebelumnya ikut
    // tersembunyi jika role tidak punya izin Laporan sama sekali —
    // padahal profilUpdate() tidak memeriksa izin menu apa pun dan wajib
    // bisa dijangkau siapa pun yang login.
    public function testProfilSelaluMunculDiSidebarWalauTanpaIzinLaporan(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Drafter Satu');

        $result = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->get('dashboard');

        $result->assertOK();
        $result->assertSeeLink('Profil');
    }

    // ── Regresi: sesi user yang dinonaktifkan Admin lewat Manajemen User
    // sebelumnya tetap bisa dipakai beroperasi sampai sesi kedaluwarsa
    // sendiri — AuthFilter sekarang memverifikasi is_active dari DB di
    // setiap request.
    public function testUserYangDinonaktifkanSesiLangsungDitolak(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Drafter Nonaktif');
        $userId       = $this->makeUser('nonaktif@test.local', 'rahasia123', 'drafter', $pegawaiId);

        // Admin menonaktifkan user ini SETELAH sesi drafter tersebut aktif.
        (new \App\Models\UserModel())->update($userId, ['is_active' => 0]);

        $result = $this->withSession($this->sessionFor('drafter', $userId, $pegawaiId))
            ->get('penilaian');

        $this->assertRedirectEndsWith($result, 'auth/login');
    }

    // ── Regresi: role yang diganti Admin lewat Manajemen User sebelumnya
    // tidak ikut ter-refresh di sesi yang sedang berjalan.
    public function testRoleYangDiubahAdminTersinkronKeSesiBerjalan(): void
    {
        $userId = $this->makeUser('naik@test.local', 'rahasia123', 'drafter');

        // Admin menaikkan role user ini menjadi admin SETELAH sesi (lama,
        // masih menyimpan role 'drafter') sedang berjalan.
        (new \App\Models\UserModel())->update($userId, ['role' => 'admin']);

        $result = $this->withSession($this->sessionFor('drafter', $userId))
            ->get('master/users');

        // AuthFilter men-sinkronkan role dari DB sebelum checkMenuAccess()
        // dievaluasi, sehingga akses admin langsung berlaku di request ini.
        $result->assertOK();
    }
}
