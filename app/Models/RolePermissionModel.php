<?php
namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table         = 'role_permission';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['role', 'menu_id', 'can_view', 'can_edit'];

    /**
     * 1. Menampilkan daftar menu dan hak akses di halaman "Hak Akses Role" (Controller index)
     */
    public function getByRole(string $role): array
    {
        // Menggabungkan tabel menu_list dengan role_permission
        return $this->db->table('menu_list m')
            ->select('m.id as menu_id, m.kode_menu, m.nama_menu, m.grup, 
                      COALESCE(rp.can_view, 0) as can_view, 
                      COALESCE(rp.can_edit, 0) as can_edit')
            ->join('role_permission rp', "rp.menu_id = m.id AND rp.role = " . $this->escape($role), 'left')
            ->orderBy('m.grup', 'ASC')
            ->orderBy('m.urutan', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * 2. Menyimpan centang checkbox saat tombol "Simpan" ditekan (Controller save)
     */
    public function updatePermission(string $role, int $menuId, bool $canView, bool $canEdit)
    {
        $existing = $this->where('role', $role)->where('menu_id', $menuId)->first();

        if ($existing) {
            // Jika sudah ada pengaturannya, update nilainya
            return $this->update($existing['id'], [
                'can_view' => $canView ? 1 : 0,
                'can_edit' => $canEdit ? 1 : 0
            ]);
        } else {
            // Jika belum ada pengaturannya, insert baru
            return $this->insert([
                'role'     => $role,
                'menu_id'  => $menuId,
                'can_view' => $canView ? 1 : 0,
                'can_edit' => $canEdit ? 1 : 0
            ]);
        }
    }

    /**
     * 3. Digunakan oleh Sidebar untuk mengecek apakah menu disembunyikan atau tidak
     */
    public function canAccess(string $role, string $kodeMenu): bool
    {
        // Admin otomatis bisa mengakses segalanya
        if ($role === 'admin') {
            return true;
        }

        // Cari pengaturan berdasarkan Role dan Kode Menu ('penilaian', 'rubrik', dll)
        $permission = $this->db->table('role_permission rp')
            ->join('menu_list m', 'm.id = rp.menu_id')
            ->where('rp.role', $role)
            ->where('m.kode_menu', $kodeMenu)
            ->get()->getRowArray();

        // Mengembalikan True jika can_view = 1
        return ($permission && $permission['can_view'] == 1);
    }

    /**
     * 4. Digunakan oleh controller untuk membatasi aksi tulis (simpan/ubah/
     * hapus/import/copy) — terpisah dari canAccess() yang hanya membatasi
     * akses lihat. Sebuah role bisa saja punya can_view=1 tapi can_edit=0
     * (akses lihat saja, tanpa bisa mengubah data).
     */
    public function canEdit(string $role, string $kodeMenu): bool
    {
        // Admin otomatis bisa mengakses segalanya
        if ($role === 'admin') {
            return true;
        }

        $permission = $this->db->table('role_permission rp')
            ->join('menu_list m', 'm.id = rp.menu_id')
            ->where('rp.role', $role)
            ->where('m.kode_menu', $kodeMenu)
            ->get()->getRowArray();

        return ($permission && $permission['can_edit'] == 1);
    }
}