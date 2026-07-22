<?php

namespace Tests\Support\Traits;

use App\Models\DirektoratModel;
use App\Models\DivisiModel;
use App\Models\KpiDivisiModel;
use App\Models\KpiPegawaiModel;
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

    protected function makePeriodeAktif(string $kode = 'PA-2026'): int
    {
        return (new PeriodeModel())->insert([
            'nama'        => "Periode $kode",
            'kode'        => $kode,
            'tgl_mulai'   => '2026-01-01',
            'tgl_selesai' => '2026-12-31',
            'status'      => 'aktif',
        ]);
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
