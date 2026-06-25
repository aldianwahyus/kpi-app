<?php
namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table         = 'role_permission';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['role', 'menu_id', 'can_view', 'can_edit'];

    public function getByRole(string $role): array
    {
        return $this->db->table('role_permission rp')
            ->select('rp.*, m.kode_menu, m.nama_menu, m.grup, m.urutan')
            ->join('menu_list m', 'm.id = rp.menu_id')
            ->where('rp.role', $role)
            ->where('m.is_active', 1)
            ->orderBy('m.grup', 'ASC')
            ->orderBy('m.urutan', 'ASC')
            ->get()->getResultArray();
    }

    public function canAccess(string $role, string $kodeMenu): bool
    {
        if ($role === 'admin') return true; // admin selalu full access

        $result = $this->db->table('role_permission rp')
            ->join('menu_list m', 'm.id = rp.menu_id')
            ->where('rp.role', $role)
            ->where('m.kode_menu', $kodeMenu)
            ->where('rp.can_view', 1)
            ->get()->getRowArray();

        return (bool) $result;
    }

    public function updatePermission(string $role, int $menuId, bool $canView, bool $canEdit): void
    {
        $existing = $this->where('role', $role)
                         ->where('menu_id', $menuId)
                         ->first();
        $data = ['can_view' => $canView ? 1 : 0, 'can_edit' => $canEdit ? 1 : 0];

        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert(array_merge($data, ['role' => $role, 'menu_id' => $menuId]));
        }
    }
}