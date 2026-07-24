<?php

namespace Tests\Support\Traits;

use App\Models\DirektoratModel;
use App\Models\DivisiModel;
use App\Models\KpiDivisiModel;
use App\Models\KpiPegawaiModel;
use App\Models\KpiPegawaiTargetBulananModel;
use App\Models\KpiPegawaiBobotTahunanModel;
use App\Models\KpiPegawaiTurunanModel;
use App\Models\KpiPegawaiTurunanTargetBulananModel;
use App\Models\KpiPegawaiTurunanBobotTahunanModel;
use App\Models\KpiUnitModel;
use App\Models\PegawaiModel;
use App\Models\PeriodeModel;
use App\Models\UserModel;

/**
 * Helper untuk menyiapkan data fixture minimal (direktorat, divisi, pegawai,
 * user, periode, KPI) yang dipakai berulang di feature test — supaya setiap
 * test class tidak menduplikasi boilerplate setup data yang sama.
 */
trait KpiFixtures
{
    protected function makeDirektorat(string $kode = 'DIR1'): int
    {
        return (new DirektoratModel())->insert([
            'kode' => $kode,
            'nama' => "Direktorat $kode",
            'is_active' => 1,
        ]);
    }

    protected function makeDivisi(int $direktoratId, string $kode): int
    {
        return (new DivisiModel())->insert([
            'kode'          => $kode,
            'direktorat_id' => $direktoratId,
            'nama'          => "Divisi $kode",
            'is_active'     => 1,
        ]);
    }

    protected function makePegawai(int $divisiId, string $nama): int
    {
        return (new PegawaiModel())->insert([
            'nama'      => $nama,
            'divisi_id' => $divisiId,
            'is_active' => 1,
        ]);
    }

    protected function makeUser(string $email, string $password, string $role, ?int $pegawaiId = null): int
    {
        return (new UserModel())->insert([
            'nama'                 => ucfirst($role) . ' Test',
            'email'                => $email,
            'password'             => password_hash($password, PASSWORD_DEFAULT),
            'role'                 => $role,
            'pegawai_id'           => $pegawaiId,
            'is_active'            => 1,
            'must_change_password' => 0,
        ]);
    }

    /**
     * Buat Periode generik dengan Jenis & rentang tanggal bebas — dipakai
     * test yang butuh mengendalikan sendiri Jenis/rentang bulan (mis. untuk
     * menguji rata-rata Triwulan/Semester/Tahunan).
     */
    protected function makePeriode(
        string $kode,
        string $jenis,
        string $tglMulai,
        string $tglSelesai,
        string $status = 'aktif'
    ): int {
        return (new PeriodeModel())->insert([
            'nama'        => "Periode $kode",
            'kode'        => $kode,
            'jenis'       => $jenis,
            'tgl_mulai'   => $tglMulai,
            'tgl_selesai' => $tglSelesai,
            'status'      => $status,
        ]);
    }

    /**
     * Buat Periode Bulanan berstatus 'aktif' untuk Juni 2026. Sekaligus
     * auto-seed Master Target (kpi_pegawai_bobot_tahunan +
     * kpi_pegawai_target_bulanan, & versi Turunannya) dari kolom `bobot`/
     * `target` (legacy) milik seluruh baris kpi_pegawai/kpi_pegawai_turunan
     * yang SUDAH ADA saat ini — supaya test lama yang memanggil
     * makeKpiPegawai($bobot, $target) SEBELUM makePeriodeAktif() (pola
     * paling umum di seluruh test suite) tetap otomatis punya Bobot/Target
     * untuk Periode ini, tanpa perlu mengubah setiap test satu-per-satu.
     */
    protected function makePeriodeAktif(string $kode = 'PA-2026'): int
    {
        $tahun = 2026;
        $bulan = 6;

        $periodeId = $this->makePeriode($kode, 'bulanan', '2026-06-01', '2026-06-30', 'aktif');

        $bobotModel  = new KpiPegawaiBobotTahunanModel();
        $targetModel = new KpiPegawaiTargetBulananModel();
        foreach ((new KpiPegawaiModel())->findAll() as $kp) {
            $bobotModel->upsert((int)$kp['id'], $tahun, $kp['bobot'] !== null ? (float)$kp['bobot'] : null);
            $targetModel->upsert((int)$kp['id'], $tahun, $bulan, $kp['target'] !== null ? (float)$kp['target'] : null);
        }

        $turunanBobotModel  = new KpiPegawaiTurunanBobotTahunanModel();
        $turunanTargetModel = new KpiPegawaiTurunanTargetBulananModel();
        foreach ((new KpiPegawaiTurunanModel())->findAll() as $t) {
            $turunanBobotModel->upsert((int)$t['id'], $tahun, $t['bobot'] !== null ? (float)$t['bobot'] : null);
            $turunanTargetModel->upsert((int)$t['id'], $tahun, $bulan, $t['target'] !== null ? (float)$t['target'] : null);
        }

        return $periodeId;
    }

    /** Atur Target Bulanan satu KPI Induk untuk satu bulan tertentu. */
    protected function makeKpiPegawaiTargetBulanan(int $kpiPegawaiId, int $tahun, int $bulan, ?float $target): void
    {
        (new KpiPegawaiTargetBulananModel())->upsert($kpiPegawaiId, $tahun, $bulan, $target);
    }

    /** Atur Target Bulanan satu KPI Induk untuk 12 bulan sekaligus (nilai sama). */
    protected function makeKpiPegawaiTargetTahunPenuh(int $kpiPegawaiId, int $tahun, float $target): void
    {
        $model = new KpiPegawaiTargetBulananModel();
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $model->upsert($kpiPegawaiId, $tahun, $bulan, $target);
        }
    }

    /** Atur Bobot Tahunan satu KPI Induk. */
    protected function makeKpiPegawaiBobotTahunan(int $kpiPegawaiId, int $tahun, ?float $bobot): void
    {
        (new KpiPegawaiBobotTahunanModel())->upsert($kpiPegawaiId, $tahun, $bobot);
    }

    /** Atur Target Bulanan satu Parameter Turunan untuk satu bulan tertentu. */
    protected function makeKpiPegawaiTurunanTargetBulanan(int $turunanId, int $tahun, int $bulan, ?float $target): void
    {
        (new KpiPegawaiTurunanTargetBulananModel())->upsert($turunanId, $tahun, $bulan, $target);
    }

    /** Atur Target Bulanan satu Parameter Turunan untuk 12 bulan sekaligus (nilai sama). */
    protected function makeKpiPegawaiTurunanTargetTahunPenuh(int $turunanId, int $tahun, float $target): void
    {
        $model = new KpiPegawaiTurunanTargetBulananModel();
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $model->upsert($turunanId, $tahun, $bulan, $target);
        }
    }

    /** Atur Bobot Tahunan satu Parameter Turunan. */
    protected function makeKpiPegawaiTurunanBobotTahunan(int $turunanId, int $tahun, ?float $bobot): void
    {
        (new KpiPegawaiTurunanBobotTahunanModel())->upsert($turunanId, $tahun, $bobot);
    }

    protected function makeKpiUnit(int $direktoratId, string $kode, string $polarity = 'max', float $target = 100, array $extra = []): int
    {
        return (new KpiUnitModel())->insert(array_merge([
            'direktorat_id'      => $direktoratId,
            'perspektif'         => 'Financial',
            'nama_kpi'           => "KPI Unit $kode",
            'kode'               => $kode,
            'satuan'             => 'Jt',
            'target'             => $target,
            'bobot'              => 1.0000,
            'polarity'           => $polarity,
            'perubahan_polarity' => 'pos',
            'is_capped'          => 1,
            'is_active'          => 1,
        ], $extra));
    }

    protected function makeKpiDivisi(int $divisiId, int $kpiUnitId, float $bobot = 1.0000): int
    {
        return (new KpiDivisiModel())->insert([
            'divisi_id' => $divisiId,
            'kpi_id'    => $kpiUnitId,
            'bobot'     => $bobot,
            'is_active' => 1,
        ]);
    }

    protected function makeKpiPegawai(int $pegawaiId, int $divisiId, int $kpiUnitId, float $bobot = 1.0000, float $target = 100): int
    {
        return (new KpiPegawaiModel())->insert([
            'pegawai_id' => $pegawaiId,
            'kpi_id'     => $kpiUnitId,
            'divisi_id'  => $divisiId,
            'bobot'      => $bobot,
            'target'     => $target,
            'is_active'  => 1,
        ]);
    }

    /**
     * Login lewat sesi langsung (tanpa POST /auth/login) — dipakai saat
     * fokus test bukan pada alur login itu sendiri (sudah dicakup terpisah
     * di AuthFeatureTest).
     */
    protected function sessionFor(string $role, ?int $userId = null, ?int $pegawaiId = null, string $nama = 'Tester'): array
    {
        return [
            'logged_in'            => true,
            'user_id'              => $userId ?? 1,
            'nama'                 => $nama,
            'email'                => strtolower($role) . '@test.local',
            'role'                 => $role,
            'pegawai_id'           => $pegawaiId,
            'must_change_password' => 0,
            'login_time'           => time(),
            'last_activity'        => time(),
        ];
    }
}
