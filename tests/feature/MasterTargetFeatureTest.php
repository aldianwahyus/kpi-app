<?php

use App\Models\KpiPegawaiBobotTahunanModel;
use App\Models\KpiPegawaiModel;
use App\Models\KpiPegawaiTargetBulananModel;
use App\Models\KpiPegawaiTurunanBobotTahunanModel;
use App\Models\KpiPegawaiTurunanModel;
use App\Models\KpiPegawaiTurunanTargetBulananModel;
use App\Models\PenilaianModel;
use App\Models\PeriodeModel;
use Tests\Support\KpiTestCase;

/**
 * @internal
 *
 * Verifikasi skema Master Target: Target diisi PER BULAN (12 bulan/tahun),
 * Bobot diisi SATU nilai per tahun. Periode (Bulanan/Triwulan/Semester/
 * Tahunan) menentukan rentang bulan yang di-rata-rata otomatis untuk
 * menghasilkan Target efektif Penilaian. Ini mencakup CRUD Master Target,
 * gate blokir jika ada bulan yang belum lengkap, dan perhitungan rata-rata
 * per Jenis Periode (inti alasan skema ini dibuat).
 */
final class MasterTargetFeatureTest extends KpiTestCase
{
    // ── Halaman & CRUD dasar ──

    public function testHalamanDaftarMasterTargetHanyaMenampilkanPegawaiYangSudahPunyaKpi(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiSudah = $this->makePegawai($divisiId, 'Pegawai Sudah Setup');
        $pegawaiBelum = $this->makePegawai($divisiId, 'Pegawai Belum Setup');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-0', 'max', 100);
        $this->makeKpiPegawai($pegawaiSudah, $divisiId, $kpiUnitId, 1.0000, 100);

        $result = $this->withSession($this->sessionFor('admin', 1))->get('master-target');

        $result->assertOK();
        $result->assertSee('Pegawai Sudah Setup');
        $result->assertDontSee('Pegawai Belum Setup');
    }

    public function testHalamanIsiMasterTargetBerhasilDitampilkanDenganGrid12Bulan(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Grid');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-1', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 3, 150);
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 0.5);

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->get("master-target/edit/{$pegawaiId}?tahun=2026");

        $result->assertOK();
        $result->assertSee('Master Target');
        $result->assertSeeInField('target[' . $kpId . '][3]', '150');
        $result->assertSeeInField('bobot[' . $kpId . ']', '0.5');
        $result->assertSeeLink('Import Excel');
    }

    public function testSimpanMasterTargetBerhasilUntukIndukTanpaTurunan(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Simpan Master Target');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-2', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $target = [];
        for ($b = 1; $b <= 12; $b++) $target[$kpId][$b] = 100 + $b;

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/save/{$pegawaiId}", [
                'tahun'  => 2026,
                'target' => $target,
                'bobot'  => [$kpId => 1.0000],
            ]);

        $result->assertRedirect();
        $this->assertNull(session('error'));

        $targetModel = new KpiPegawaiTargetBulananModel();
        $this->assertEqualsWithDelta(101.0, (float)$targetModel->getByRefTahunBulan($kpId, 2026, 1)['target'], 0.001);
        $this->assertEqualsWithDelta(112.0, (float)$targetModel->getByRefTahunBulan($kpId, 2026, 12)['target'], 0.001);

        $bobotModel = new KpiPegawaiBobotTahunanModel();
        $this->assertEqualsWithDelta(1.0, (float)$bobotModel->getByRefTahun($kpId, 2026)['bobot'], 0.0001);
    }

    public function testSimpanMasterTargetDitolakJikaAdaBulanYangKosong(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Bulan Kosong');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-3', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $target = [];
        for ($b = 1; $b <= 12; $b++) $target[$kpId][$b] = 100;
        unset($target[$kpId][7]); // Juli sengaja dikosongkan

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/save/{$pegawaiId}", [
                'tahun'  => 2026,
                'target' => $target,
                'bobot'  => [$kpId => 1.0000],
            ]);

        $result->assertRedirect();
        $this->assertStringContainsString('Juli', session('error') ?? '');
        $this->assertNull((new KpiPegawaiTargetBulananModel())->getByRefTahunBulan($kpId, 2026, 1), 'Tidak boleh ada yang tersimpan (all-or-nothing).');
    }

    // ── Regresi UX: gagal validasi tidak boleh menghapus isian yang sudah
    // diketik, dan harus menandai field id mana yang bermasalah supaya
    // Admin langsung diarahkan fokus ke situ. ──

    public function testSimpanMasterTargetGagalMenandaiFieldIdYangBermasalah(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Highlight Field');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-3B', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $target = [];
        for ($b = 1; $b <= 12; $b++) $target[$kpId][$b] = 100;
        unset($target[$kpId][7]); // Juli sengaja dikosongkan

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/save/{$pegawaiId}", [
                'tahun'  => 2026,
                'target' => $target,
                'bobot'  => [$kpId => 1.0000],
            ]);

        $result->assertRedirect();
        $this->assertSame("target-{$kpId}-7", session('highlight_id'));
    }

    public function testSimpanMasterTargetGagalTidakMenghapusIsianYangSudahDiketik(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Isian Tidak Hilang');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-3C', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $target = [];
        for ($b = 1; $b <= 12; $b++) $target[$kpId][$b] = 111 + $b; // 112..123
        unset($target[$kpId][7]); // Juli sengaja dikosongkan -> gagal validasi

        $postData = [
            'tahun'  => 2026,
            'target' => $target,
            'bobot'  => [$kpId => 1.0000],
        ];

        // RedirectResponse::withInput() (dipanggil controller saat validasi
        // gagal) membaca $_POST superglobal MENTAH — bukan lewat lapisan
        // simulasi FeatureTestTrait — jadi perlu diisi manual di sini agar
        // perilakunya persis seperti request browser sungguhan.
        $_POST = $postData;

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/save/{$pegawaiId}", $postData);

        $result->assertRedirect();
        // withInput() menyimpan seluruh POST ke flashdata '_ci_old_input' —
        // inilah mekanisme yang dipakai old() di form untuk mengembalikan
        // isian Admin (bulan Januari=112) alih-alih menghapusnya begitu
        // saja saat form ditampilkan ulang setelah gagal validasi.
        $oldInput = session('_ci_old_input');
        $this->assertNotNull($oldInput, 'withInput() harus dipanggil supaya isian tidak hilang.');
        $this->assertEquals(112, $oldInput['post']['target'][$kpId][1] ?? null);
        $this->assertEquals(1.0000, $oldInput['post']['bobot'][$kpId] ?? null);

        $_POST = [];
    }

    public function testSimpanMasterTargetBerhasilUntukPolaritySpecialTanpaTarget(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Special Master Target');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-4', 'special', 100, ['sifat_khusus' => 'maximize']);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 0);

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/save/{$pegawaiId}", [
                'tahun'  => 2026,
                'bobot'  => [$kpId => 1.0000],
            ]);

        $result->assertRedirect();
        $this->assertNull(session('error'));
    }

    public function testSimpanMasterTargetDitolakJikaTotalBobotBukan100Persen(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Bobot Kurang Master Target');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-5', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $target = [];
        for ($b = 1; $b <= 12; $b++) $target[$kpId][$b] = 100;

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/save/{$pegawaiId}", [
                'tahun'  => 2026,
                'target' => $target,
                'bobot'  => [$kpId => 0.5], // bukan 100%
            ]);

        $result->assertRedirect();
        $this->assertStringContainsString('100%', session('error') ?? '');
        $this->assertNull((new KpiPegawaiBobotTahunanModel())->getByRefTahun($kpId, 2026));
    }

    // ── Induk dengan Turunan: Bobot Induk = SUM Bobot Turunan (dihitung
    // otomatis, tidak diinput langsung) ──

    public function testBobotIndukDenganTurunanDihitungOtomatisSebagaiSumBobotTurunan(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Induk Turunan Master Target');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-6', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 0, 0);

        $t1 = (new KpiPegawaiTurunanModel())->insert([
            'kpi_pegawai_id' => $kpId, 'nama_turunan' => 'Sub A', 'polarity' => 'max', 'is_active' => 1,
        ]);
        $t2 = (new KpiPegawaiTurunanModel())->insert([
            'kpi_pegawai_id' => $kpId, 'nama_turunan' => 'Sub B', 'polarity' => 'max', 'is_active' => 1,
        ]);

        $turunanTarget = [];
        for ($b = 1; $b <= 12; $b++) {
            $turunanTarget[$t1][$b] = 80;
            $turunanTarget[$t2][$b] = 70;
        }

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/save/{$pegawaiId}", [
                'tahun'          => 2026,
                'turunan_target' => $turunanTarget,
                'turunan_bobot'  => [$t1 => 0.6, $t2 => 0.4],
            ]);

        $result->assertRedirect();
        $this->assertNull(session('error'));

        $bobotIndukRow = (new KpiPegawaiBobotTahunanModel())->getByRefTahun($kpId, 2026);
        $this->assertNotNull($bobotIndukRow);
        $this->assertEqualsWithDelta(1.0, (float)$bobotIndukRow['bobot'], 0.0001, 'Bobot Induk harus otomatis = 0.6 + 0.4 = 1.0.');
    }

    // ── Perhitungan rata-rata otomatis sesuai Jenis Periode (inti fitur) ──

    public function testPeriodeBulananMengambilTargetBulanItuLangsungTanpaRataRata(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Periode Bulanan');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-7', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 3, 300); // Maret saja
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 1.0000);

        $periodeId = $this->makePeriode('MT-BLN-MAR', 'bulanan', '2026-03-01', '2026-03-31', 'aktif');

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", ['realisasi' => [$kpiUnitId => 300]]);

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('periode_id', $periodeId)->first();
        $this->assertNotNull($row);
        // Realisasi 300 / Target 300 (bulan Maret langsung, bukan rata-rata) = 100% -> Skor 3
        $this->assertEqualsWithDelta(3.0, (float)$row['skor'], 0.01);
    }

    public function testPeriodeTriwulanMenghitungRataRataTigaBulan(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Periode Triwulan');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-8', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        // Jan=100, Feb=200, Mar=300 -> rata-rata = 200
        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 1, 100);
        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 2, 200);
        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 3, 300);
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 1.0000);

        $periodeId = $this->makePeriode('MT-TW1', 'triwulan', '2026-01-01', '2026-03-31', 'aktif');

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", ['realisasi' => [$kpiUnitId => 200]]);

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('periode_id', $periodeId)->first();
        $this->assertNotNull($row);
        // Realisasi 200 / Target rata-rata (100+200+300)/3=200 = 100% -> Skor 3
        $this->assertEqualsWithDelta(3.0, (float)$row['skor'], 0.01);
    }

    public function testPeriodeSemesterMenghitungRataRataEnamBulan(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Periode Semester');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-9', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        // Jan..Jun = 100,120,140,160,180,200 -> rata-rata = 150
        $bulanValues = [1=>100, 2=>120, 3=>140, 4=>160, 5=>180, 6=>200];
        foreach ($bulanValues as $b => $v) {
            $this->makeKpiPegawaiTargetBulanan($kpId, 2026, $b, $v);
        }
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 1.0000);

        $periodeId = $this->makePeriode('MT-SM1', 'semester', '2026-01-01', '2026-06-30', 'aktif');

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", ['realisasi' => [$kpiUnitId => 150]]);

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('periode_id', $periodeId)->first();
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(3.0, (float)$row['skor'], 0.01);
    }

    public function testPeriodeTahunanMenghitungRataRataDuaBelasBulan(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Periode Tahunan');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-10', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $this->makeKpiPegawaiTargetTahunPenuh($kpId, 2026, 100); // semua bulan = 100
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 1.0000);

        $periodeId = $this->makePeriode('MT-TH1', 'tahunan', '2026-01-01', '2026-12-31', 'aktif');

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", ['realisasi' => [$kpiUnitId => 100]]);

        $row = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('periode_id', $periodeId)->first();
        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(3.0, (float)$row['skor'], 0.01);
    }

    // ── Gate: blokir jika ADA bulan yang belum lengkap dalam rentang ──

    public function testPeriodeTriwulanDiblokirJikaSalahSatuBulanBelumDiisi(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Triwulan Tidak Lengkap');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-11', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        // Hanya Jan & Feb diisi, Maret sengaja kosong.
        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 1, 100);
        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 2, 100);
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 1.0000);

        $this->makePeriode('MT-TW2', 'triwulan', '2026-01-01', '2026-03-31', 'aktif');

        $result = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->get("penilaian/form/{$pegawaiId}");

        $result->assertRedirect();
        $this->assertStringContainsString('belum disiapkan', session('error') ?? '');
    }

    // ── Independensi antar tahun ──

    public function testTargetTahunBerbedaTidakSalingBocor(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Multi Tahun');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-12', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 6, 100);
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 1.0000);
        $this->makeKpiPegawaiTargetBulanan($kpId, 2027, 6, 500);
        $this->makeKpiPegawaiBobotTahunan($kpId, 2027, 1.0000);

        $periode2026 = $this->makePeriode('MT-Y26', 'bulanan', '2026-06-01', '2026-06-30', 'tutup');
        $periode2027 = $this->makePeriode('MT-Y27', 'bulanan', '2027-06-01', '2027-06-30', 'aktif');

        $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", ['realisasi' => [$kpiUnitId => 500]]);

        $row2027 = (new PenilaianModel())->where('pegawai_id', $pegawaiId)->where('periode_id', $periode2027)->first();
        $this->assertNotNull($row2027);
        // Realisasi 500 / Target 500 (tahun 2027) = 100% -> Skor 3 (bukan tercampur dgn target 100 di 2026)
        $this->assertEqualsWithDelta(3.0, (float)$row2027['skor'], 0.01);
    }

    // ── KPI Per Pegawai: preview read-only saat Periode Aktif ada ──

    public function testKpiPerPegawaiMenampilkanPreviewReadonlySaatPeriodeAktifAda(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Preview KPI');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-13', 'max', 100);
        $this->makeKpiDivisi($divisiId, $kpiUnitId, 1.0000);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 6, 250);
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 1.0000);
        $this->makePeriode('MT-PREV', 'bulanan', '2026-06-01', '2026-06-30', 'aktif');

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->get("kpi-pegawai/edit/{$pegawaiId}");

        $result->assertStatus(200);
        $result->assertSee('Periode Aktif');
        $result->assertSee('250');
    }

    public function testKpiPerPegawaiTidakMenampilkanPreviewSaatTidakAdaPeriodeAktif(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Belum Ada Setup Periode');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-14', 'max', 100);
        $this->makeKpiDivisi($divisiId, $kpiUnitId, 1.0000);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->get("kpi-pegawai/edit/{$pegawaiId}");

        $result->assertStatus(200);
        $result->assertDontSee('Periode Aktif');
    }

    // ── Validasi Jenis Periode vs rentang tanggal (PeriodeController) ──

    public function testCreatePeriodeDitolakJikaRentangTanggalTidakSesuaiJenis(): void
    {
        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post('master/periode/store', [
                'nama'        => 'Periode Salah Rentang',
                'kode'        => 'MT-SALAH',
                'jenis'       => 'triwulan',
                'tgl_mulai'   => '2026-01-01',
                'tgl_selesai' => '2026-01-31', // cuma 1 bulan, seharusnya 3
                'status'      => 'draft',
            ]);

        $result->assertRedirect();
        $this->assertStringContainsString('tidak sesuai', session('error') ?? '');
        $this->assertNull((new PeriodeModel())->where('kode', 'MT-SALAH')->first());
    }

    public function testCreatePeriodeBerhasilJikaRentangTanggalSesuaiJenis(): void
    {
        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post('master/periode/store', [
                'nama'        => 'Periode Benar Rentang',
                'kode'        => 'MT-BENAR',
                'jenis'       => 'triwulan',
                'tgl_mulai'   => '2026-01-01',
                'tgl_selesai' => '2026-03-31',
                'status'      => 'draft',
            ]);

        $result->assertRedirect();
        $periode = (new PeriodeModel())->where('kode', 'MT-BENAR')->first();
        $this->assertNotNull($periode);
        $this->assertSame('triwulan', $periode['jenis']);
    }

    // ── Regresi: Rekap Detail & Laporan PDF/Excel per-pegawai HARUS
    // menampilkan Bobot dari Master Target, bukan kolom legacy
    // kpi_pegawai.bobot yang sudah tidak lagi dikelola sama sekali sejak
    // Bobot dipindah sepenuhnya ke Master Target (kolom itu sekarang selalu
    // 0, sehingga jika masih dipakai, laporan akan salah menampilkan 0%
    // untuk Bobot setiap KPI).

    public function testRekapDetailMenampilkanBobotDariMasterTargetBukanKolomLegacy(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Rekap Detail Bobot');
        $kpiUnitA     = $this->makeKpiUnit($direktoratId, 'MT-15A', 'max', 100);
        $kpiUnitB     = $this->makeKpiUnit($direktoratId, 'MT-15B', 'max', 100);
        // Bobot legacy (kolom kpi_pegawai.bobot) sengaja 0 untuk keduanya —
        // persis kondisi nyata pasca migrasi ke Master Target, di mana
        // kolom itu tidak pernah lagi diisi. Bobot Master Target-nya
        // justru 63% & 37% (sengaja bukan angka bulat sederhana seperti
        // capaian/skor lain di halaman, supaya tidak ada tabrakan teks).
        $kpA = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitA, 0, 100);
        $kpB = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitB, 0, 100);
        $this->makeKpiPegawaiTargetBulanan($kpA, 2026, 6, 100);
        $this->makeKpiPegawaiTargetBulanan($kpB, 2026, 6, 100);
        $this->makeKpiPegawaiBobotTahunan($kpA, 2026, 0.6300);
        $this->makeKpiPegawaiBobotTahunan($kpB, 2026, 0.3700);

        $this->makePeriode('MT-REKAP', 'bulanan', '2026-06-01', '2026-06-30', 'aktif');
        $periodeId = (new PeriodeModel())->where('kode', 'MT-REKAP')->first()['id'];

        $storeResult = $this->withSession($this->sessionFor('drafter', 1, $pegawaiId))
            ->post("penilaian/store/{$pegawaiId}", [
                'realisasi' => [$kpiUnitA => 100, $kpiUnitB => 100],
            ]);
        $storeResult->assertRedirect();
        $this->assertNull(session('error'), 'Penyimpanan Penilaian tidak boleh gagal — Total Bobot Master Target sudah 100%.');

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->get("rekap/detail/{$pegawaiId}?periode_id={$periodeId}");

        $result->assertStatus(200);
        $result->assertSee('63%');
        $result->assertSee('37%');
    }

    // ── Regresi: LaporanController::pdfPegawai()/excelPegawai() memakai
    // helper privat resolveBobotUntukDetail() yang sama untuk sumber Bobot.
    // pdfPegawai()/excelPegawai() sendiri TIDAK aman dipanggil lewat HTTP di
    // sini (diakhiri exit() setelah menulis output biner, akan menghentikan
    // proses PHPUnit) — helper privatnya diverifikasi langsung lewat
    // Reflection agar tetap teruji tanpa menyentuh exit().
    public function testResolveBobotUntukDetailLaporanMengambilDariMasterTarget(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Laporan Bobot');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'MT-16', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 0, 100); // legacy bobot = 0
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 0.4500);

        $periode = ['tgl_mulai' => '2026-06-01', 'tgl_selesai' => '2026-06-30'];
        $detail  = [['kpi_pegawai_id' => $kpId, 'nama_kpi' => 'Dummy']];

        $controller = new \App\Controllers\LaporanController();
        $method = new \ReflectionMethod($controller, 'resolveBobotUntukDetail');
        $method->setAccessible(true);
        $method->invokeArgs($controller, [&$detail, $periode]);

        $this->assertEqualsWithDelta(
            0.45, (float)$detail[0]['bobot'], 0.0001,
            'Bobot pada Laporan harus diambil dari Master Target (0.45), bukan kolom legacy kpi_pegawai.bobot (0).'
        );
    }

    // ── Import Master Target dari Excel ──
    // processImportRows() diuji langsung lewat Reflection dengan array baris
    // buatan (format identik dengan PhpSpreadsheet::toArray(null,true,true,true)
    // — baris 1 header, key kolom huruf A,B,C,...) — meng-upload file .xlsx
    // sungguhan lewat HTTP tidak didukung reliabel oleh FeatureTestTrait
    // (tidak mengisi $_FILES), jadi logika parsing/penyimpanannya dipisah
    // dari importProcess() supaya tetap bisa diuji tanpa upload sungguhan.

    private function panggilProcessImportRows(array $rows): array
    {
        // processImportRows() memanggil canAccessPegawai() per baris (via
        // BaseController), yang membaca role dari session() — perlu diisi
        // manual di sini karena controller dipanggil langsung lewat
        // Reflection, bukan lewat request HTTP sungguhan yang membawa sesi.
        session()->set('role', 'admin');

        $controller = new \App\Controllers\MasterTargetController();
        $method = new \ReflectionMethod($controller, 'processImportRows');
        $method->setAccessible(true);
        return $method->invoke($controller, $rows);
    }

    private function panggilBuildTemplateRowsUntukPegawai(int $pegawaiId, int $tahun): array
    {
        $controller = new \App\Controllers\MasterTargetController();
        $method = new \ReflectionMethod($controller, 'buildTemplateRowsUntukPegawai');
        $method->setAccessible(true);
        return $method->invoke($controller, $pegawaiId, $tahun);
    }

    public function testImportHalamanFormBerhasilDitampilkan(): void
    {
        $result = $this->withSession($this->sessionFor('admin', 1))->get('master-target/import');

        $result->assertOK();
        $result->assertSee('Import Master Target');
    }

    public function testImportHalamanFormMenampilkanKonteksPegawaiJikaAdaPegawaiId(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Import Konteks');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'IMP-CTX', 'max', 100);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->get("master-target/import?pegawai_id={$pegawaiId}&tahun=2026");

        $result->assertOK();
        $result->assertSee('Pegawai Import Konteks');
        $result->assertSee('parameter KPI');
    }

    // ── Regresi: Template Import personalisasi otomatis sesuai parameter
    // KPI/Turunan yang sudah di-assign ke pegawai — Admin tidak perlu lagi
    // mengetik ulang Tipe/NIP/Kode KPI/Nama Turunan. buildTemplateRowsUntukPegawai()
    // diuji langsung lewat Reflection (importTemplate() sendiri menulis
    // output biner + exit(), tidak aman dipanggil lewat HTTP di sini).

    public function testBuildTemplateRowsMengisiParameterIndukTanpaTurunanOtomatis(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Template Otomatis');
        (new \App\Models\PegawaiModel())->update($pegawaiId, ['nip' => '198501019999']);
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TPL-1', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 0.4);
        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 3, 150);

        $rows = $this->panggilBuildTemplateRowsUntukPegawai($pegawaiId, 2026);

        $this->assertCount(1, $rows);
        [$tipe, $nikEmail, $kodeKpi, $namaParameter, $namaTurunan, $tahunRow, $bobot, $jan, $feb, $mar] = $rows[0];
        $this->assertSame('INDUK', $tipe);
        $this->assertSame('198501019999', $nikEmail);
        $this->assertSame('TPL-1', $kodeKpi);
        $this->assertSame('KPI Unit TPL-1', $namaParameter, 'Kolom Nama Parameter KPI harus otomatis terisi.');
        $this->assertSame('', $namaTurunan);
        $this->assertSame(2026, $tahunRow);
        $this->assertSame('0.4', $bobot);
        $this->assertSame('', $jan, 'Bulan yang belum diisi harus tetap kosong, bukan 0.');
        $this->assertSame('150', $mar);
    }

    public function testBuildTemplateRowsMenyertakanBarisTurunanDenganNamaMengikutiInduk(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Template Turunan');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TPL-2', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 0, 0);
        $tId = (new KpiPegawaiTurunanModel())->insert(['kpi_pegawai_id' => $kpId, 'nama_turunan' => 'Sub Template A', 'polarity' => 'max', 'is_active' => 1]);
        $this->makeKpiPegawaiTurunanBobotTahunan($tId, 2026, 0.5);
        $this->makeKpiPegawaiTurunanTargetBulanan($tId, 2026, 1, 90);

        $rows = $this->panggilBuildTemplateRowsUntukPegawai($pegawaiId, 2026);

        $this->assertCount(2, $rows, 'Harus ada 1 baris INDUK + 1 baris TURUNAN.');
        $this->assertSame('INDUK', $rows[0][0]);
        // Bobot Induk yang punya Turunan tidak diisi di template (dihitung otomatis saat disimpan).
        $this->assertSame('', $rows[0][6]);

        $this->assertSame('TURUNAN', $rows[1][0]);
        $this->assertSame('KPI Unit TPL-2', $rows[1][3], 'Baris Turunan juga ikut diberi Nama Parameter KPI Induknya untuk konteks.');
        $this->assertSame('Sub Template A', $rows[1][4]);
        $this->assertSame('0.5', $rows[1][6]);
        $this->assertSame('90', $rows[1][7]); // kolom H = bulan Januari
    }

    public function testBuildTemplateRowsMengosongkanTargetUntukPolaritySpecial(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Template Special');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'TPL-3', 'special', 100, ['sifat_khusus' => 'maximize']);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 0);
        $this->makeKpiPegawaiBobotTahunan($kpId, 2026, 1.0);

        $rows = $this->panggilBuildTemplateRowsUntukPegawai($pegawaiId, 2026);

        $this->assertCount(1, $rows);
        for ($b = 7; $b <= 18; $b++) { // kolom H..S (index 7-18) = 12 bulan
            $this->assertSame('', $rows[0][$b], 'Target harus tetap kosong untuk polarity Special.');
        }
    }

    public function testBuildTemplateRowsKosongJikaPegawaiTidakDitemukan(): void
    {
        $rows = $this->panggilBuildTemplateRowsUntukPegawai(999999, 2026);
        $this->assertSame([], $rows);
    }

    public function testImportIndukTanpaTurunanBerhasilMenyimpanBobotDanTarget(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Import Induk');
        (new \App\Models\PegawaiModel())->update($pegawaiId, ['nip' => '198501012099']);
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'IMP-1', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $row = ['A' => 'INDUK', 'B' => '198501012099', 'C' => 'IMP-1', 'D' => '', 'E' => '', 'F' => '2026', 'G' => '0.5'];
        for ($b = 0; $b < 12; $b++) $row[chr(ord('H') + $b)] = 100 + $b;

        $result = $this->panggilProcessImportRows([1 => [], 2 => $row]);

        $this->assertSame(1, $result['berhasil']);
        $this->assertSame([], $result['errors']);
        $this->assertEqualsWithDelta(0.5, (float)(new KpiPegawaiBobotTahunanModel())->getByRefTahun($kpId, 2026)['bobot'], 0.0001);
        $this->assertEqualsWithDelta(100.0, (float)(new KpiPegawaiTargetBulananModel())->getByRefTahunBulan($kpId, 2026, 1)['target'], 0.0001);
        $this->assertEqualsWithDelta(111.0, (float)(new KpiPegawaiTargetBulananModel())->getByRefTahunBulan($kpId, 2026, 12)['target'], 0.0001);
    }

    public function testImportIndukDenganTurunanMenghitungBobotIndukOtomatis(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Import Turunan');
        (new \App\Models\PegawaiModel())->update($pegawaiId, ['nip' => '198501012098']);
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'IMP-2', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 0, 0);
        $t1 = (new KpiPegawaiTurunanModel())->insert(['kpi_pegawai_id' => $kpId, 'nama_turunan' => 'Sub Import A', 'polarity' => 'max', 'is_active' => 1]);
        $t2 = (new KpiPegawaiTurunanModel())->insert(['kpi_pegawai_id' => $kpId, 'nama_turunan' => 'Sub Import B', 'polarity' => 'max', 'is_active' => 1]);

        $rowInduk = ['A' => 'INDUK', 'B' => '198501012098', 'C' => 'IMP-2', 'D' => '', 'E' => '', 'F' => '2026', 'G' => ''];
        for ($b = 0; $b < 12; $b++) $rowInduk[chr(ord('H') + $b)] = '';

        $rowT1 = ['A' => 'TURUNAN', 'B' => '', 'C' => '', 'D' => '', 'E' => 'Sub Import A', 'F' => '2026', 'G' => '0.6'];
        $rowT2 = ['A' => 'TURUNAN', 'B' => '', 'C' => '', 'D' => '', 'E' => 'Sub Import B', 'F' => '2026', 'G' => '0.4'];
        for ($b = 0; $b < 12; $b++) {
            $rowT1[chr(ord('H') + $b)] = 80;
            $rowT2[chr(ord('H') + $b)] = 70;
        }

        $result = $this->panggilProcessImportRows([1 => [], 2 => $rowInduk, 3 => $rowT1, 4 => $rowT2]);

        $this->assertSame([], $result['errors']);
        $bobotInduk = (new KpiPegawaiBobotTahunanModel())->getByRefTahun($kpId, 2026);
        $this->assertNotNull($bobotInduk);
        $this->assertEqualsWithDelta(1.0, (float)$bobotInduk['bobot'], 0.0001, 'Bobot Induk harus otomatis = 0.6 + 0.4.');
        $this->assertEqualsWithDelta(80.0, (float)(new KpiPegawaiTurunanTargetBulananModel())->getByRefTahunBulan($t1, 2026, 1)['target'], 0.0001);
        $this->assertEqualsWithDelta(70.0, (float)(new KpiPegawaiTurunanTargetBulananModel())->getByRefTahunBulan($t2, 2026, 1)['target'], 0.0001);
    }

    public function testImportMengisiSebagianBulanSajaTidakMenimpaBulanLain(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Import Parsial');
        (new \App\Models\PegawaiModel())->update($pegawaiId, ['nip' => '198501012097']);
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'IMP-3', 'max', 100);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);
        // Bulan Februari sudah punya nilai sebelumnya (mis. diisi manual).
        $this->makeKpiPegawaiTargetBulanan($kpId, 2026, 2, 999);

        // File import hanya mengisi Januari — kolom bulan lain dikosongkan.
        $row = ['A' => 'INDUK', 'B' => '198501012097', 'C' => 'IMP-3', 'D' => '', 'E' => '', 'F' => '2026', 'G' => ''];
        $row['H'] = 150; // Januari
        foreach (['I','J','K','L','M','N','O','P','Q','R','S'] as $col) $row[$col] = '';

        $result = $this->panggilProcessImportRows([1 => [], 2 => $row]);

        $this->assertSame([], $result['errors']);
        $this->assertEqualsWithDelta(150.0, (float)(new KpiPegawaiTargetBulananModel())->getByRefTahunBulan($kpId, 2026, 1)['target'], 0.0001);
        $this->assertEqualsWithDelta(
            999.0, (float)(new KpiPegawaiTargetBulananModel())->getByRefTahunBulan($kpId, 2026, 2)['target'], 0.0001,
            'Bulan Februari yang tidak diisi di file import tidak boleh ikut tertimpa/terhapus.'
        );
    }

    public function testImportGagalJikaKpiBelumDiAssignKePegawai(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Import Belum Assign');
        (new \App\Models\PegawaiModel())->update($pegawaiId, ['nip' => '198501012096']);
        $this->makeKpiUnit($direktoratId, 'IMP-4', 'max', 100); // dibuat tapi TIDAK di-assign ke pegawai

        $row = ['A' => 'INDUK', 'B' => '198501012096', 'C' => 'IMP-4', 'D' => '', 'E' => '', 'F' => '2026', 'G' => '1.0'];
        for ($b = 0; $b < 12; $b++) $row[chr(ord('H') + $b)] = 100;

        $result = $this->panggilProcessImportRows([1 => [], 2 => $row]);

        $this->assertSame(0, $result['berhasil']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('belum di-assign', $result['errors'][0]);
    }

    public function testImportGagalJikaNamaTurunanTidakDitemukan(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Import Turunan Salah');
        (new \App\Models\PegawaiModel())->update($pegawaiId, ['nip' => '198501012095']);
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'IMP-5', 'max', 100);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $rowInduk = ['A' => 'INDUK', 'B' => '198501012095', 'C' => 'IMP-5', 'D' => '', 'E' => '', 'F' => '2026', 'G' => '1.0'];
        for ($b = 0; $b < 12; $b++) $rowInduk[chr(ord('H') + $b)] = 100;
        $rowTurunan = ['A' => 'TURUNAN', 'B' => '', 'C' => '', 'D' => '', 'E' => 'Nama Yang Tidak Ada', 'F' => '2026', 'G' => '0.5'];
        for ($b = 0; $b < 12; $b++) $rowTurunan[chr(ord('H') + $b)] = 80;

        $result = $this->panggilProcessImportRows([1 => [], 2 => $rowInduk, 3 => $rowTurunan]);

        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('tidak ditemukan', $result['errors'][0]);
    }

    public function testImportMengabaikanTargetUntukPolaritySpecial(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Import Special');
        (new \App\Models\PegawaiModel())->update($pegawaiId, ['nip' => '198501012094']);
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'IMP-6', 'special', 100, ['sifat_khusus' => 'maximize']);
        $kpId         = $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 0);

        $row = ['A' => 'INDUK', 'B' => '198501012094', 'C' => 'IMP-6', 'D' => '', 'E' => '', 'F' => '2026', 'G' => '1.0'];
        for ($b = 0; $b < 12; $b++) $row[chr(ord('H') + $b)] = ''; // sengaja kosong, tidak wajib untuk special

        $result = $this->panggilProcessImportRows([1 => [], 2 => $row]);

        $this->assertSame([], $result['errors']);
        $this->assertSame(1, $result['berhasil']);
        $this->assertEqualsWithDelta(1.0, (float)(new KpiPegawaiBobotTahunanModel())->getByRefTahun($kpId, 2026)['bobot'], 0.0001);
    }

    // ── Copy Target dari Pegawai Lain ──
    // Parameter dicocokkan berdasarkan kpi_id (KPI Unit) untuk Induk, dan
    // nama_turunan untuk Turunan — karena baris kpi_pegawai/kpi_pegawai_
    // turunan milik pegawai sumber & tujuan selalu berbeda id walau merujuk
    // ke KPI/Turunan yang secara konsep sama.

    public function testCopyDariPegawaiMengisiTargetDanBobotYangMasihKosong(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'CP-1', 'max', 100);

        $sourceId = $this->makePegawai($divisiId, 'Pegawai Sumber Copy');
        $kpSource = $this->makeKpiPegawai($sourceId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makeKpiPegawaiBobotTahunan($kpSource, 2026, 0.8000);
        $this->makeKpiPegawaiTargetTahunPenuh($kpSource, 2026, 200);

        $targetId = $this->makePegawai($divisiId, 'Pegawai Tujuan Copy');
        $kpTarget = $this->makeKpiPegawai($targetId, $divisiId, $kpiUnitId, 1.0000, 100);
        // Belum ada Bobot/Target sama sekali untuk pegawai tujuan.

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/copy/{$targetId}", [
                'source_pegawai_id' => $sourceId,
                'tahun'             => 2026,
            ]);

        $result->assertRedirect();
        $this->assertNull(session('error'));

        $bobotTarget = (new KpiPegawaiBobotTahunanModel())->getByRefTahun($kpTarget, 2026);
        $this->assertNotNull($bobotTarget);
        $this->assertEqualsWithDelta(0.8, (float)$bobotTarget['bobot'], 0.0001);

        $targetBulan1 = (new KpiPegawaiTargetBulananModel())->getByRefTahunBulan($kpTarget, 2026, 1);
        $this->assertNotNull($targetBulan1);
        $this->assertEqualsWithDelta(200.0, (float)$targetBulan1['target'], 0.0001);
    }

    public function testCopyDariPegawaiTidakMenimpaYangSudahTerisi(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'CP-2', 'max', 100);

        $sourceId = $this->makePegawai($divisiId, 'Pegawai Sumber Copy Timpa');
        $kpSource = $this->makeKpiPegawai($sourceId, $divisiId, $kpiUnitId, 1.0000, 100);
        $this->makeKpiPegawaiBobotTahunan($kpSource, 2026, 0.9000);
        $this->makeKpiPegawaiTargetTahunPenuh($kpSource, 2026, 500);

        $targetId = $this->makePegawai($divisiId, 'Pegawai Tujuan Copy Timpa');
        $kpTarget = $this->makeKpiPegawai($targetId, $divisiId, $kpiUnitId, 1.0000, 100);
        // Pegawai tujuan SUDAH mengisi Bobot & Target Januari sendiri.
        $this->makeKpiPegawaiBobotTahunan($kpTarget, 2026, 0.3000);
        $this->makeKpiPegawaiTargetBulanan($kpTarget, 2026, 1, 111);

        $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/copy/{$targetId}", [
                'source_pegawai_id' => $sourceId,
                'tahun'             => 2026,
            ]);

        // Bobot & Target Januari yang SUDAH diisi tidak boleh berubah.
        $bobotTarget = (new KpiPegawaiBobotTahunanModel())->getByRefTahun($kpTarget, 2026);
        $this->assertEqualsWithDelta(0.3, (float)$bobotTarget['bobot'], 0.0001, 'Bobot yang sudah diisi tidak boleh ditimpa.');
        $targetBulan1 = (new KpiPegawaiTargetBulananModel())->getByRefTahunBulan($kpTarget, 2026, 1);
        $this->assertEqualsWithDelta(111.0, (float)$targetBulan1['target'], 0.0001, 'Target Januari yang sudah diisi tidak boleh ditimpa.');

        // Bulan lain (Februari dst.) yang MASIH KOSONG boleh terisi dari sumber.
        $targetBulan2 = (new KpiPegawaiTargetBulananModel())->getByRefTahunBulan($kpTarget, 2026, 2);
        $this->assertNotNull($targetBulan2);
        $this->assertEqualsWithDelta(500.0, (float)$targetBulan2['target'], 0.0001);
    }

    public function testCopyDariPegawaiUntukTurunanCocokBerdasarkanNamaDanBobotIndukDihitungUlang(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'CP-3', 'max', 100);

        $sourceId  = $this->makePegawai($divisiId, 'Pegawai Sumber Turunan Copy');
        $kpSource  = $this->makeKpiPegawai($sourceId, $divisiId, $kpiUnitId, 0, 0);
        $stA = (new KpiPegawaiTurunanModel())->insert(['kpi_pegawai_id' => $kpSource, 'nama_turunan' => 'Sub Copy A', 'polarity' => 'max', 'is_active' => 1]);
        $stB = (new KpiPegawaiTurunanModel())->insert(['kpi_pegawai_id' => $kpSource, 'nama_turunan' => 'Sub Copy B', 'polarity' => 'max', 'is_active' => 1]);
        $this->makeKpiPegawaiTurunanBobotTahunan($stA, 2026, 0.65);
        $this->makeKpiPegawaiTurunanBobotTahunan($stB, 2026, 0.35);
        $this->makeKpiPegawaiTurunanTargetTahunPenuh($stA, 2026, 80);
        $this->makeKpiPegawaiTurunanTargetTahunPenuh($stB, 2026, 70);

        $targetId  = $this->makePegawai($divisiId, 'Pegawai Tujuan Turunan Copy');
        $kpTarget  = $this->makeKpiPegawai($targetId, $divisiId, $kpiUnitId, 0, 0);
        $ttA = (new KpiPegawaiTurunanModel())->insert(['kpi_pegawai_id' => $kpTarget, 'nama_turunan' => 'Sub Copy A', 'polarity' => 'max', 'is_active' => 1]);
        $ttB = (new KpiPegawaiTurunanModel())->insert(['kpi_pegawai_id' => $kpTarget, 'nama_turunan' => 'Sub Copy B', 'polarity' => 'max', 'is_active' => 1]);
        // Belum ada Bobot/Target sama sekali untuk kedua Turunan tujuan.

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/copy/{$targetId}", [
                'source_pegawai_id' => $sourceId,
                'tahun'             => 2026,
            ]);

        $result->assertRedirect();
        $this->assertNull(session('error'));

        $this->assertEqualsWithDelta(0.65, (float)(new KpiPegawaiTurunanBobotTahunanModel())->getByRefTahun($ttA, 2026)['bobot'], 0.0001);
        $this->assertEqualsWithDelta(0.35, (float)(new KpiPegawaiTurunanBobotTahunanModel())->getByRefTahun($ttB, 2026)['bobot'], 0.0001);
        $this->assertEqualsWithDelta(80.0, (float)(new KpiPegawaiTurunanTargetBulananModel())->getByRefTahunBulan($ttA, 2026, 1)['target'], 0.0001);

        // Bobot Induk tujuan harus otomatis = 0.65 + 0.35 = 1.0 (dihitung
        // ulang, bukan disalin langsung dari Induk sumber).
        $bobotIndukTarget = (new KpiPegawaiBobotTahunanModel())->getByRefTahun($kpTarget, 2026);
        $this->assertNotNull($bobotIndukTarget);
        $this->assertEqualsWithDelta(1.0, (float)$bobotIndukTarget['bobot'], 0.0001);
    }

    public function testCopyDariPegawaiMelewatiKpiYangTidakDimilikiSumber(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');

        $sourceId = $this->makePegawai($divisiId, 'Pegawai Sumber Tanpa KPI Cocok');
        $kpiUnitSumber = $this->makeKpiUnit($direktoratId, 'CP-4A', 'max', 100);
        $this->makeKpiPegawai($sourceId, $divisiId, $kpiUnitSumber, 1.0000, 100);

        $targetId = $this->makePegawai($divisiId, 'Pegawai Tujuan KPI Beda');
        $kpiUnitTujuan = $this->makeKpiUnit($direktoratId, 'CP-4B', 'max', 100);
        $kpTarget = $this->makeKpiPegawai($targetId, $divisiId, $kpiUnitTujuan, 1.0000, 100);

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/copy/{$targetId}", [
                'source_pegawai_id' => $sourceId,
                'tahun'             => 2026,
            ]);

        $result->assertRedirect();
        $this->assertNull(session('error'), 'KPI yang tidak dimiliki sumber cukup dilewati, bukan error.');
        $this->assertNull((new KpiPegawaiBobotTahunanModel())->getByRefTahun($kpTarget, 2026));
    }

    public function testCopyDariPegawaiDitolakJikaSumberSamaDenganTujuan(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Copy Diri Sendiri');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'CP-5', 'max', 100);
        $this->makeKpiPegawai($pegawaiId, $divisiId, $kpiUnitId, 1.0000, 100);

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->post("master-target/copy/{$pegawaiId}", [
                'source_pegawai_id' => $pegawaiId,
                'tahun'             => 2026,
            ]);

        $result->assertRedirect();
        $this->assertStringContainsString('valid', session('error') ?? '');
    }

    public function testHalamanEditMasterTargetMenampilkanTombolCopyDariPegawaiLain(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'CP-6', 'max', 100);

        $sourceId = $this->makePegawai($divisiId, 'Pegawai Lain Untuk Dropdown');
        $this->makeKpiPegawai($sourceId, $divisiId, $kpiUnitId, 1.0000, 100);

        $targetId = $this->makePegawai($divisiId, 'Pegawai Utama Dropdown');
        $this->makeKpiPegawai($targetId, $divisiId, $kpiUnitId, 1.0000, 100);

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->get("master-target/edit/{$targetId}?tahun=2026");

        $result->assertOK();
        $result->assertSee('Copy Target dari Pegawai Lain');
        $result->assertSee('Pegawai Lain Untuk Dropdown');
    }

    public function testHalamanEditMasterTargetTidakMenyertakanDiriSendiriDiDropdownSumber(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $kpiUnitId    = $this->makeKpiUnit($direktoratId, 'CP-7', 'max', 100);
        $targetId     = $this->makePegawai($divisiId, 'Pegawai Solo Dropdown');
        $this->makeKpiPegawai($targetId, $divisiId, $kpiUnitId, 1.0000, 100);

        $result = $this->withSession($this->sessionFor('admin', 1))
            ->get("master-target/edit/{$targetId}?tahun=2026");

        $result->assertOK();
        // Tidak ada pegawai LAIN yang punya KPI -> dropdown sumber kosong,
        // tombol Simpan pada modal harus disabled (bukan error, cukup
        // memberi tahu belum ada pilihan).
        $result->assertSee('Belum ada pegawai lain yang memiliki KPI.');
    }
}
