<?php
$role       = session()->get('role');
$nama       = session()->get('nama');
$initial    = strtoupper(substr($nama ?? 'U', 0, 1));
$role_label = ['admin'=>'Admin','hr'=>'HR Manager','manajer'=>'Manajer','pegawai'=>'Pegawai'];
?>
<div id="header">
  <button id="sidebarToggle" class="btn btn-sm btn-light d-md-none border-0">
    <i class="ti ti-menu-2 fs-5"></i>
  </button>

  <div class="page-title">
    <?= esc($title ?? 'Dashboard') ?>
  </div>

  <span class="role-badge role-<?= $role ?>">
    <?= $role_label[$role] ?? $role ?>
  </span>

  <div class="user-badge">
    <div class="user-avatar"><?= $initial ?></div>
    <span class="d-none d-md-inline"><?= esc($nama) ?></span>
  </div>
</div>