<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    /**
     * Default URL. Will be dynamically overridden in __construct().
     */
    public string $baseURL = 'http://localhost:8080/';

    public array $allowedHostnames = [];

    /**
     * Empty string for clean URLs without index.php
     */
    public string $indexPage = '';

    public string $uriProtocol = 'REQUEST_URI';

    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    public string $defaultLocale = 'id';

    public bool $negotiateLocale = false;

    public array $supportedLocales = ['en'];

    /**
     * Timezone set for WITA (NTB)
     */
    public string $appTimezone = 'Asia/Makassar';

    public string $charset = 'UTF-8';

    public bool $forceGlobalSecureRequests = false;

    /**
     * Map subnet/IP to the proxy header name (Fixes CodeIgniter 4 Proxy IP Error)
     */
    public array $proxyIPs = [
        '0.0.0.0/0' => 'X-Forwarded-For',
    ];

    public bool $CSPEnabled = false;

    // --- Session Configurations ---
    public string $sessionDriver = 'CodeIgniter\Session\Handlers\FileHandler';
    public int    $sessionExpiration = 7200; // 2 hours
    public bool   $sessionRegenerateDestroy = false;
    public bool   $sessionHTTPOnly = true; 

    /**
     * Constructor to dynamically compute baseURL based on incoming Host & Scheme
     */
    public function __construct()
    {
        parent::__construct();

        if (isset($_SERVER['HTTP_HOST'])) {
            // Check if connection is HTTPS (direct or forwarded via Cloudflare/Reverse Proxy)
            $isSecure = (
                (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
                (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
            );

            $protocol = $isSecure ? 'https://' : 'http://';

            // Sertakan folder tempat index.php sungguhan berjalan (mis.
            // "/kpi-app/public") — SCRIPT_NAME selalu mengikuti lokasi file
            // fisik yang dieksekusi, tidak terpengaruh rewrite URL cantik,
            // jadi ini tetap benar baik saat aplikasi ditaruh di root domain
            // maupun di subfolder seperti pada deployment ini.
            $basePath = isset($_SERVER['SCRIPT_NAME'])
                ? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/')
                : '';
            if ($basePath === '.' || $basePath === '/') {
                $basePath = '';
            }

            // Dynamically set baseURL matching current browser request
            $this->baseURL = $protocol . $_SERVER['HTTP_HOST'] . $basePath . '/';
        }
    }
}