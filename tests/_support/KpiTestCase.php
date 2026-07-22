<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Traits\KpiFixtures;

/**
 * Base class untuk feature test aplikasi KPI.
 *
 * CIUnitTestCase secara default hanya migrate namespace 'Tests\Support'
 * (contoh migrasi bawaan framework) — bukan migrasi aplikasi kita sendiri
 * di app/Database/Migrations. Override $namespace ke 'App' di sini supaya
 * seluruh skema tabel sungguhan ikut dibuat di database SQLite in-memory
 * setiap kali feature test berjalan.
 */
abstract class KpiTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use KpiFixtures;

    protected $namespace = 'App';
    protected $refresh   = true;
    protected $seed      = \App\Database\Seeds\MenuListSeeder::class;

    protected function assertRedirectEndsWith($result, string $path): void
    {
        $result->assertRedirect();
        $this->assertStringEndsWith('/' . ltrim($path, '/'), $result->getRedirectUrl());
    }

    protected function setUp(): void
    {
        // MenuListSeeder meng-echo "Menu & Permission seeded." langsung
        // (bukan lewat $this->call(), yang dibungkam setSilent()) — bungkam
        // manual di sini agar tidak dianggap "risky test" oleh PHPUnit.
        ob_start();
        parent::setUp();
        ob_end_clean();
    }
}
