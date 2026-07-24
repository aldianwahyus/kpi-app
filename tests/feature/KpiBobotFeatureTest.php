<?php

use App\Models\KpiDivisiModel;
use App\Models\KpiPegawaiModel;
use App\Models\KpiPegawaiTurunanModel;
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

    // ── Regresi: Bobot & Target TIDAK LAGI dikelola di KPI Per Pegawai —
    // sudah dipindah sepenuhnya ke menu "Master Target". Layar KPI Per
    // Pegawai kini murni assignment; satu-satunya yang masih bisa disimpan
    // dari sana adalah Deskripsi Target (teks panduan pengisian Realisasi).

    public function testSaveDeskripsiMenyimpanTeksTanpaMenyentuhBobotAtauTarget(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Deskripsi');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-F1');
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 0.3, 100);

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("kpi-pegawai/save-deskripsi/{$pegawaiId}", [
                'kp_id'            => [$kpId],
                'deskripsi_target' => ['Minimal 80 nasabah baru'],
            ]);

        $this->assertRedirectEndsWith($result, "kpi-pegawai/edit/{$pegawaiId}");
        $row = (new KpiPegawaiModel())->find($kpId);
        $this->assertSame('Minimal 80 nasabah baru', $row['deskripsi_target']);
        $this->assertEqualsWithDelta(0.3, (float)$row['bobot'], 0.0001, 'Bobot (kolom legacy) tidak boleh ikut berubah.');
        $this->assertEqualsWithDelta(100.0, (float)$row['target'], 0.0001, 'Target (kolom legacy) tidak boleh ikut berubah.');
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

    // ── Regresi: Bobot & Target TIDAK LAGI divalidasi/diisi saat membuat
    // Parameter Turunan (addTurunan()) — untuk polarity APA PUN. Keduanya
    // kini sepenuhnya dikelola di menu "Master Target" setelah Turunan dibuat.

    public function testAddTurunanBerhasilUntukSemuaPolarityTanpaBobotAtauTarget(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Turunan Bobot Target Diabaikan');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TK-ST4', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $this->withSession($this->sessionFor('admin', 1))
            ->post("kpi-pegawai/turunan/add/{$kpId}", [
                'nama_turunan' => 'Sub Max',
                'polarity'     => 'max',
            ]);

        $turunan = (new KpiPegawaiTurunanModel())->where('kpi_pegawai_id', $kpId)->first();
        $this->assertNotNull(
            $turunan,
            'Turunan harus tetap tersimpan walau Bobot/Target tidak dikirim — keduanya dikelola di Master Target.'
        );
        $this->assertSame('max', $turunan['polarity']);
    }
}
