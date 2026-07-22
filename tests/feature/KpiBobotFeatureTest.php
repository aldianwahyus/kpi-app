<?php

use App\Models\KpiDivisiModel;
use App\Models\KpiPegawaiModel;
use App\Models\UserModel;
use Tests\Support\KpiTestCase;

/**
 * @internal
 */
final class KpiBobotFeatureTest extends KpiTestCase
{
    public function testKpiDivisiStoreDitolakJikaTotalBobotBukan100Persen(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-F1');

        $_SERVER['HTTP_REFERER'] = site_url("master/kpi-divisi/edit/{$divisiId}");

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master/kpi-divisi/store/{$divisiId}", [
                'kpi_id'  => [$kpiUnitId],
                'bobot'   => [0.5], // 50%, bukan 100%
                'urutan'  => [1],
            ]);

        $result->assertRedirect();
        $this->assertSame(0, (new KpiDivisiModel())->where('divisi_id', $divisiId)->countAllResults());
    }

    public function testKpiDivisiStoreBerhasilJikaTotalBobotTepat100Persen(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-F1');

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master/kpi-divisi/store/{$divisiId}", [
                'kpi_id'  => [$kpiUnitId],
                'bobot'   => [1.0],
                'urutan'  => [1],
            ]);

        $result->assertRedirect();
        $this->assertSame(1, (new KpiDivisiModel())->where('divisi_id', $divisiId)->countAllResults());
    }

    public function testSaveBobotKpiPegawaiDitolakJikaTotalBukan100Persen(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Satu');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-F1');
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 0.3);

        $_SERVER['HTTP_REFERER'] = site_url("kpi-pegawai/edit/{$pegawaiId}");

        // Kirim bobot BERBEDA dari nilai awal (0.3 -> 0.5), tapi totalnya
        // tetap bukan 100% -> harus ditolak dan nilai lama TIDAK berubah.
        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("kpi-pegawai/save-bobot/{$pegawaiId}", [
                'kp_id'  => [$kpId],
                'bobot'  => [0.5],
                'target' => [100],
            ]);

        $result->assertRedirect();
        $this->assertEqualsWithDelta(0.3, (new KpiPegawaiModel())->find($kpId)['bobot'], 0.0001);
    }

    public function testSaveBobotKpiPegawaiBerhasilJikaTotalTepat100Persen(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Satu');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-F1');
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 0.3);

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("kpi-pegawai/save-bobot/{$pegawaiId}", [
                'kp_id'  => [$kpId],
                'bobot'  => [1.0],
                'target' => [100],
            ]);

        $this->assertRedirectEndsWith($result, "kpi-pegawai/edit/{$pegawaiId}");
        $this->assertEqualsWithDelta(1.0, (new KpiPegawaiModel())->find($kpId)['bobot'], 0.0001);
    }

    // ── Regresi: hanya Admin yang boleh kelola KPI Per Pegawai ──
    // (KpiPegawaiController sebelumnya salah memeriksa menu 'penilaian'
    // alih-alih 'kpi_pegawai', sehingga Drafter/Approver/HR bisa ikut
    // mengubah struktur bobot KPI pegawai — bukan cuma mengisi skor.)
    public function testHrTidakBisaMengaksesKpiPerPegawai(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Satu');

        $result = $this->withSession($this->sessionFor('hr', 1))
            ->get("kpi-pegawai/edit/{$pegawaiId}");

        $this->assertRedirectEndsWith($result, 'dashboard');
    }

    public function testDrafterTidakBisaMengaksesKpiPerPegawai(): void
    {
        $direktoratId     = $this->makeDirektorat();
        $divisiId         = $this->makeDivisi($direktoratId, 'DVA');
        $drafterPegawaiId = $this->makePegawai($divisiId, 'Drafter Satu');

        $result = $this->withSession($this->sessionFor('drafter', 1, $drafterPegawaiId))
            ->get('kpi-pegawai');

        $this->assertRedirectEndsWith($result, 'dashboard');
    }

    public function testAdminBisaMengaksesKpiPerPegawai(): void
    {
        $result = $this->withSession($this->sessionFor('admin', 1))->get('kpi-pegawai');

        $result->assertOK();
    }

    // ── Regresi: whitelist role saat PegawaiController membuat akun ──
    // (sebelumnya 'role' dari POST diteruskan langsung tanpa validasi,
    // memungkinkan nilai sembarang/tidak sah tersimpan di kolom role.)
    public function testStorePegawaiDenganRoleTidakDikenalJatuhKeDefaultPegawai(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post('pegawai/store', [
                'nama'      => 'Pegawai Baru',
                'divisi_id' => $divisiId,
                'email'     => 'pegawai-baru@test.local',
                'role'      => 'superadmin', // nilai tidak valid, bukan salah satu role yang diizinkan
            ]);

        $result->assertRedirect();

        $user = (new UserModel())->where('email', 'pegawai-baru@test.local')->first();
        $this->assertNotNull($user);
        $this->assertSame('pegawai', $user['role']);
    }

    public function testStorePegawaiDenganRoleValidTersimpanSesuaiInput(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post('pegawai/store', [
                'nama'      => 'Pegawai HR Baru',
                'divisi_id' => $divisiId,
                'email'     => 'pegawai-hr-baru@test.local',
                'role'      => 'hr',
            ]);

        $result->assertRedirect();

        $user = (new UserModel())->where('email', 'pegawai-hr-baru@test.local')->first();
        $this->assertNotNull($user);
        $this->assertSame('hr', $user['role']);
    }

    // ── Regresi: Target default saat assign KPI ke pegawai ──
    // Target 100 hanya bermakna untuk polarity yang benar-benar memakainya.
    // Untuk 'special' (Ada/Tidak Ada, tidak dibandingkan ke angka), Target
    // harus nihil (0) di form setup, bukan otomatis terisi 100 yang
    // menyesatkan Admin.

    public function testAddKpiPegawaiPolaritySpecialTargetDefaultNihil(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Special Target');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-ST1', 'special', 100, ['sifat_khusus' => 'maximize']);

        $this->withSession($this->sessionFor('admin', 1))
            ->post("kpi-pegawai/add/{$pegawaiId}", ['kpi_id' => $kpiUnitId]);

        $row = (new KpiPegawaiModel())->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiUnitId)->first();
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(0.0, (float)$row['target'], 0.001);
    }

    public function testAddKpiPegawaiPolarityMaxTargetTetapDefaultSeratus(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Max Target');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-ST2', 'max', 100);

        $this->withSession($this->sessionFor('admin', 1))
            ->post("kpi-pegawai/add/{$pegawaiId}", ['kpi_id' => $kpiUnitId]);

        $row = (new KpiPegawaiModel())->where('pegawai_id', $pegawaiId)->where('kpi_id', $kpiUnitId)->first();
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(100.0, (float)$row['target'], 0.001);
    }

    // ── Regresi: Parameter Turunan ber-polarity 'special' tidak boleh
    // diwajibkan Target > 0 (Target tidak dipakai sama sekali oleh
    // hitungSkorSpecial()) — beda dari polarity lain yang tetap mewajibkannya.

    public function testAddTurunanSpecialBerhasilWalauTargetNol(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Turunan Special');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-ST3', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $this->withSession($this->sessionFor('admin', 1))
            ->post("kpi-pegawai/turunan/add/{$kpId}", [
                'nama_turunan' => 'Sub Special',
                'bobot'        => 1.0,
                'target'       => 0,
                'polarity'     => 'special',
                'sifat_khusus' => 'maximize',
            ]);

        $turunan = (new \App\Models\KpiPegawaiTurunanModel())->where('kpi_pegawai_id', $kpId)->first();
        $this->assertNotNull($turunan, 'Turunan special harus tetap tersimpan walau Target=0.');
        $this->assertSame('special', $turunan['polarity']);
    }

    public function testAddTurunanMaxDitolakJikaTargetNol(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Turunan Max');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-ST4', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $this->withSession($this->sessionFor('admin', 1))
            ->post("kpi-pegawai/turunan/add/{$kpId}", [
                'nama_turunan' => 'Sub Max',
                'bobot'        => 1.0,
                'target'       => 0,
                'polarity'     => 'max',
            ]);

        $this->assertSame(
            0,
            (new \App\Models\KpiPegawaiTurunanModel())->where('kpi_pegawai_id', $kpId)->countAllResults(),
            'Turunan max/min/precise/tertimbang tetap harus mewajibkan Target > 0.'
        );
    }
}
