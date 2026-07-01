<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ── Public routes (tidak perlu login) ────────────────────
$routes->get('/',           'AuthController::login');
$routes->get('auth/login',  'AuthController::login');
$routes->post('auth/login', 'AuthController::doLogin');
$routes->get('auth/logout', 'AuthController::logout');

// ── Protected routes (perlu login) ───────────────────────
$routes->group('', ['filter' => 'auth'], function($routes) {

    // Dashboard
    $routes->get('dashboard', 'DashboardController::index');

    // ── Penilaian ─────────────────────────────────────────
    $routes->get('penilaian',               'PenilaianController::index');
    $routes->get('penilaian/form/(:num)',    'PenilaianController::form/$1');
    $routes->post('penilaian/store/(:num)', 'PenilaianController::store/$1');
    $routes->post('penilaian/ajaxHitung',        'PenilaianController::ajaxHitung');
    $routes->post('penilaian/ajaxHitungTurunan', 'PenilaianController::ajaxHitungTurunan');

    // ── Penilaian KPI Unit ────────────────────────────────
    $routes->get('penilaian-unit',                'PenilaianUnitController::index');
    $routes->get('penilaian-unit/form/(:num)',    'PenilaianUnitController::form/$1');
    $routes->post('penilaian-unit/store/(:num)', 'PenilaianUnitController::store/$1');
    $routes->post('penilaian-unit/ajax-hitung',  'PenilaianUnitController::ajaxHitung');

    // ── Data Pegawai ──────────────────────────────────────
    $routes->get('pegawai',                'PegawaiController::index');
    $routes->get('pegawai/create',         'PegawaiController::create');
    $routes->post('pegawai/store',         'PegawaiController::store');
    $routes->get('pegawai/edit/(:num)',    'PegawaiController::edit/$1');
    $routes->post('pegawai/update/(:num)', 'PegawaiController::update/$1');
    $routes->get('pegawai/toggle/(:num)',  'PegawaiController::toggle/$1');
    $routes->get('pegawai/delete/(:num)',  'PegawaiController::delete/$1');

    // ── Master KPI ────────────────────────────────────────
    $routes->get('master/kpi',                 'MasterController::kpi');
    $routes->get('master/kpi/create',          'MasterController::kpiCreate');
    $routes->post('master/kpi/store',          'MasterController::kpiStore');
    $routes->get('master/kpi/edit/(:num)',     'MasterController::kpiEdit/$1');
    $routes->post('master/kpi/update/(:num)',  'MasterController::kpiUpdate/$1');
    $routes->get('master/kpi/toggle/(:num)',   'MasterController::kpiToggle/$1');
    $routes->get('master/kpi/delete/(:num)',   'MasterController::kpiDelete/$1');

    // ── KPI per Divisi ────────────────────────────────────
    $routes->get('master/kpi-divisi',                'MasterController::kpiDivisi');
    $routes->get('master/kpi-divisi/edit/(:num)',    'MasterController::kpiDivisiEdit/$1');
    $routes->post('master/kpi-divisi/store/(:num)',  'MasterController::kpiDivisiStore/$1');
    $routes->post('master/kpi-divisi/add/(:num)',    'MasterController::kpiDivisiAdd/$1');
    $routes->get('master/kpi-divisi/delete/(:num)',  'MasterController::kpiDivisiDelete/$1');

    // ── Direktorat ────────────────────────────────────────
    $routes->get('master/direktorat',                'MasterController::direktorat');
    $routes->get('master/direktorat/create',         'MasterController::direktoratCreate');
    $routes->post('master/direktorat/store',         'MasterController::direktoratStore');
    $routes->get('master/direktorat/edit/(:num)',    'MasterController::direktoratEdit/$1');
    $routes->post('master/direktorat/update/(:num)', 'MasterController::direktoratUpdate/$1');

    // ── KPI Unit per Direktorat ───────────────────────────
    // KPI Unit — urutan HARUS seperti ini
    $routes->get('master/kpi-unit/edit/(:num)',       'MasterController::kpiUnitEdit/$1');
    $routes->post('master/kpi-unit/update/(:num)',    'MasterController::kpiUnitUpdate/$1');
    $routes->get('master/kpi-unit/delete/(:num)',     'MasterController::kpiUnitDelete/$1');
    $routes->get('master/kpi-unit/(:num)/create',     'MasterController::kpiUnitCreate/$1');
    $routes->post('master/kpi-unit/(:num)/store',     'MasterController::kpiUnitStore/$1');
    $routes->get('master/kpi-unit/(:num)',            'MasterController::kpiUnit/$1');
    $routes->get('master/kpi-unit/generate-kode',    'MasterController::kpiUnitGenerateKode');
    $routes->get('master/kpi-unit/(:num)/import',         'MasterController::kpiUnitImportForm/$1');
    $routes->get('master/kpi-unit/(:num)/import-template','MasterController::kpiUnitImportTemplate/$1');
    $routes->post('master/kpi-unit/(:num)/import',         'MasterController::kpiUnitImportProcess/$1');
    // ↑ Route dengan (:num) paling akhir agar tidak menimpa route spesifik

    // ── Data Unit Kerja ───────────────────────────────────
    $routes->get('master/unit-kerja',                'MasterController::unitKerja');
    $routes->get('master/unit-kerja/create',         'MasterController::unitKerjaCreate');
    $routes->post('master/unit-kerja/store',         'MasterController::unitKerjaStore');
    $routes->get('master/unit-kerja/edit/(:num)',    'MasterController::unitKerjaEdit/$1');
    $routes->post('master/unit-kerja/update/(:num)', 'MasterController::unitKerjaUpdate/$1');
    $routes->get('master/unit-kerja/toggle/(:num)',  'MasterController::unitKerjaToggle/$1');
    $routes->get('master/unit-kerja/delete/(:num)',  'MasterController::unitKerjaDelete/$1');

    // ── Periode Penilaian ─────────────────────────────────
    $routes->get('master/periode',                         'PeriodeController::index');
    $routes->get('master/periode/create',                  'PeriodeController::create');
    $routes->post('master/periode/store',                  'PeriodeController::store');
    $routes->get('master/periode/edit/(:num)',             'PeriodeController::edit/$1');
    $routes->post('master/periode/update/(:num)',          'PeriodeController::update/$1');
    $routes->get('master/periode/status/(:num)/(:alpha)',  'PeriodeController::setStatus/$1/$2');
    $routes->get('master/periode/delete/(:num)',           'PeriodeController::delete/$1');

   // Rekap & Ranking
    $routes->get('rekap',                    'RekapController::index');
    $routes->get('rekap/detail/(:num)',      'RekapController::detail/$1');

    // Manajemen User
    $routes->get('master/users',                 'UserController::index');
    $routes->get('master/users/create',          'UserController::create');
    $routes->post('master/users/store',          'UserController::store');
    $routes->get('master/users/edit/(:num)',     'UserController::edit/$1');
    $routes->post('master/users/update/(:num)',  'UserController::update/$1');
    $routes->get('master/users/toggle/(:num)',   'UserController::toggle/$1');
    $routes->get('master/users/reset/(:num)',    'UserController::resetPassword/$1');
    $routes->get('master/users/delete/(:num)',   'UserController::delete/$1');
    
    // Role Permission
    $routes->get('master/role-permission',      'RolePermissionController::index');
    $routes->post('master/role-permission/save','RolePermissionController::save');

    // Profil
    $routes->get('profil',        'UserController::profil');
    $routes->post('profil/update','UserController::profilUpdate');

    // Laporan Export
    $routes->get('laporan/pdf',                  'LaporanController::pdf');
    $routes->get('laporan/pdf-pegawai/(:num)',   'LaporanController::pdfPegawai/$1');
    $routes->get('laporan/excel',                'LaporanController::excel');
    $routes->get('laporan/excel-pegawai/(:num)', 'LaporanController::excelPegawai/$1');

    $routes->get('master/direktorat/delete/(:num)', 'MasterController::direktoratDelete/$1');
    // Import Pegawai
    $routes->get('pegawai/import',          'PegawaiController::importForm');
    $routes->post('pegawai/import-process', 'PegawaiController::importProcess');
    $routes->get('pegawai/template-import', 'PegawaiController::templateImport');

    // KPI Per Pegawai
    $routes->get('kpi-pegawai',                    'KpiPegawaiController::index');
    $routes->get('kpi-pegawai/edit/(:num)',        'KpiPegawaiController::edit/$1');
    $routes->post('kpi-pegawai/add/(:num)',        'KpiPegawaiController::add/$1');
    $routes->post('kpi-pegawai/save-bobot/(:num)', 'KpiPegawaiController::saveBobot/$1');
    $routes->get('kpi-pegawai/delete/(:num)',      'KpiPegawaiController::delete/$1');
    $routes->post('kpi-pegawai/copy/(:num)',       'KpiPegawaiController::copy/$1'); // <-- UBAH copyFrom MENJADI copy DI SINI
    $routes->post('kpi-pegawai/turunan/add/(:num)',    'KpiPegawaiController::addTurunan/$1');
    $routes->get('kpi-pegawai/turunan/delete/(:num)',  'KpiPegawaiController::deleteTurunan/$1');
    $routes->post('kpi-pegawai/turunan/update/(:num)', 'KpiPegawaiController::updateTurunan/$1');

    // Approval
    $routes->post('penilaian/submit/(:num)',  'PenilaianController::submit/$1');
    $routes->post('penilaian/approve/(:num)', 'PenilaianController::approve/$1');
    $routes->post('penilaian/reject/(:num)',  'PenilaianController::reject/$1');

    // Notifikasi
    $routes->get('notifikasi',              'NotifikasiController::index');
    $routes->post('notifikasi/send/(:num)', 'NotifikasiController::sendReminder/$1');
    $routes->post('notifikasi/send-all',    'NotifikasiController::sendReminderAll');
    $routes->get('notifikasi/histori', 'NotifikasiController::histori');

    // AI Asisten
    $routes->get('ai',                          'AiController::index');
    $routes->post('ai/chat',                    'AiController::chat');
    $routes->get('ai/analisis/(:num)',          'AiController::analisisPegawai/$1');

    // Draft Ulang
    $routes->get('draft-ulang',                          'DraftUlangController::index');
    $routes->post('draft-ulang/request-pegawai/(:num)',  'DraftUlangController::requestPegawai/$1');
    $routes->post('draft-ulang/request-periode',         'DraftUlangController::requestPeriode');
    $routes->post('draft-ulang/confirm/(:num)',          'DraftUlangController::confirm/$1');
    $routes->post('draft-ulang/decline/(:num)',          'DraftUlangController::decline/$1');
});