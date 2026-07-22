<?php

use Config\Services;
use Tests\Support\KpiTestCase;

/**
 * @internal
 */
final class AuthFeatureTest extends KpiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Services::cache()->clean();
        // redirect()->back() pada AuthController membaca $_SERVER['HTTP_REFERER']
        // langsung (bukan lewat withHeaders()) — di browser sungguhan header ini
        // otomatis terisi karena user memang submit dari halaman login.
        $_SERVER['HTTP_REFERER'] = site_url('auth/login');
    }

    /**
     * doLogin() memvalidasi captcha matematika terhadap session['captcha_answer']
     * SEBELUM memeriksa kredensial — set jawaban yang sudah diketahui di sesi,
     * lalu sertakan jawaban yang sama di field 'captcha' pada POST.
     */
    private function postLogin(string $email, string $password)
    {
        return $this->withSession(['captcha_answer' => 8])
            ->post('auth/login', [
                'email'    => $email,
                'password' => $password,
                'captcha'  => 8,
            ]);
    }

    public function testHalamanTerproteksiRedirectKeLoginJikaBelumLogin(): void
    {
        $result = $this->withSession([])->get('dashboard');

        $this->assertRedirectEndsWith($result, 'auth/login');
    }

    public function testCaptchaSalahMenolakLoginWalauKredensialBenar(): void
    {
        $this->makeUser('captcha-salah@test.local', 'rahasia123', 'admin');

        $result = $this->withSession(['captcha_answer' => 8])
            ->post('auth/login', [
                'email'    => 'captcha-salah@test.local',
                'password' => 'rahasia123',
                'captcha'  => 999, // jawaban captcha salah
            ]);

        $this->assertRedirectEndsWith($result, 'auth/login');
        $this->assertNotTrue(session()->get('logged_in'));
    }

    public function testLoginBerhasilMembuatSesiDanRedirectKeDashboard(): void
    {
        $this->makeUser('login-ok@test.local', 'rahasia123', 'admin');

        $result = $this->postLogin('login-ok@test.local', 'rahasia123');

        $this->assertRedirectEndsWith($result, 'dashboard');
        $this->assertSame(true, session()->get('logged_in'));
        $this->assertSame('admin', session()->get('role'));
    }

    public function testLoginGagalKarenaPasswordSalahTidakMembuatSesi(): void
    {
        $this->makeUser('login-gagal@test.local', 'passwordbenar', 'admin');

        $result = $this->postLogin('login-gagal@test.local', 'passwordsalah');

        $this->assertRedirectEndsWith($result, 'auth/login');
        $this->assertNotTrue(session()->get('logged_in'));
    }

    public function testRateLimitMemblokirSetelahLimaKaliGagal(): void
    {
        $this->makeUser('rate-limit@test.local', 'passwordbenar', 'admin');

        for ($i = 0; $i < 5; $i++) {
            $this->postLogin('rate-limit@test.local', 'salah');
        }

        // Percobaan ke-6, walau passwordnya BENAR, harus tetap ditolak
        // karena sudah melewati batas 5 percobaan dalam 15 menit.
        $result = $this->postLogin('rate-limit@test.local', 'passwordbenar');

        $this->assertRedirectEndsWith($result, 'auth/login');
        $this->assertNotTrue(session()->get('logged_in'));
    }

    public function testLoginBerhasilMereseTitikRateLimit(): void
    {
        $this->makeUser('reset-limit@test.local', 'passwordbenar', 'admin');

        // Dua kali gagal (masih di bawah batas 5x)
        $this->postLogin('reset-limit@test.local', 'salah');
        $this->postLogin('reset-limit@test.local', 'salah');

        // Login benar harus tetap berhasil (belum mencapai limit)
        $result = $this->postLogin('reset-limit@test.local', 'passwordbenar');

        $this->assertRedirectEndsWith($result, 'dashboard');
        $this->assertSame(true, session()->get('logged_in'));
    }

    public function testLogoutMenghancurkanSesi(): void
    {
        $this->makeUser('logout-test@test.local', 'passwordbenar', 'admin');
        $loginResult = $this->postLogin('logout-test@test.local', 'passwordbenar');
        $this->assertSame(true, session()->get('logged_in'), 'Prasyarat: login harus berhasil dulu sebelum menguji logout.');

        $result = $this->call('GET', 'auth/logout');

        $this->assertRedirectEndsWith($result, 'auth/login');
        $this->assertNotTrue(session()->get('logged_in'));
    }
}
