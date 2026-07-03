<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-shield-lock me-1"></i> Hak Akses Role
    </h5>
    <small class="text-muted">
      Atur menu/fitur apa saja yang bisa diakses oleh setiap role
    </small>
  </div>
</div>

<div class="alert py-2 mb-3" style="background:#E6F1FB;border:1px solid #2E75B6;font-size:13px">
  <i class="ti ti-info-circle me-1" style="color:#1F4E79"></i>
  <span style="color:#1F4E79">
    Role <strong>Admin</strong> selalu memiliki akses penuh ke semua fitur
    dan tidak dapat diatur di sini.
  </span>
</div>

<!-- Tab pilih role -->
<div class="d-flex gap-2 mb-3">
  <?php
  $roleLabels = [
      'hr'       => 'HR Manager',
      'drafter'  => 'Drafter',
      'approver' => 'Approver',
      'pegawai'  => 'Pegawai',
  ];
  ?>
  <?php foreach ($roles as $r): ?>
  <a href="<?= base_url("master/role-permission?role=$r") ?>"
     class="btn btn-sm <?= $r === $selectedRole ? 'btn-primary' : 'btn-light border' ?>">
    <?= esc($roleLabels[$r] ?? $r) ?>
  </a>
  <?php endforeach; ?>
</div>

<form action="<?= base_url('master/role-permission/save') ?>" method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="role" value="<?= esc($selectedRole) ?>">

  <?php foreach ($grouped as $grup => $menus): ?>
  <div class="card mb-3 border-0 shadow-sm">
    <div class="card-header py-2" style="background:#E6F1FB">
      <span class="fw-semibold" style="color:#1F4E79;font-size:13px">
        <?= esc($grup) ?>
      </span>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm align-middle mb-0" style="font-size:13px">
        <thead style="background:#f8fafc">
          <tr>
            <th>Nama Menu</th>
            <th class="text-center" style="width:100px">Bisa Lihat</th>
            <th class="text-center" style="width:100px">Bisa Edit</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($menus as $m): ?>
          <tr>
            <td><?= esc($m['nama_menu']) ?></td>
            <td class="text-center">
              <input type="checkbox"
                     class="form-check-input view-check"
                     name="can_view[]"
                     value="<?= $m['menu_id'] ?>"
                     <?= $m['can_view'] ? 'checked' : '' ?>>
            </td>
            <td class="text-center">
              <input type="checkbox"
                     class="form-check-input edit-check"
                     name="can_edit[]"
                     value="<?= $m['menu_id'] ?>"
                     <?= $m['can_edit'] ? 'checked' : '' ?>>
            </td>
            <input type="hidden" name="menu_id[]" value="<?= $m['menu_id'] ?>">
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <button type="submit" class="btn btn-primary px-4">
    <i class="ti ti-device-floppy me-1"></i> Simpan Hak Akses
  </button>
</form>