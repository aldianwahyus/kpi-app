<?php
namespace App\Controllers;

use App\Models\RolePermissionModel;

class RolePermissionController extends BaseController
{
    protected RolePermissionModel $permissionModel;

    protected array $roles = ['hr','drafter','approver','pegawai'];
    // admin tidak ditampilkan karena selalu full access

    public function __construct()
    {
        $this->permissionModel = new RolePermissionModel();
    }

    public function index()
    {
        if (session()->get('role') !== 'admin') {
            return $this->forbidden('Hanya Administrator yang dapat mengakses Hak Akses Role.');
        }

        $selectedRole = $this->request->getGet('role') ?? 'hr';

        $permissions = $this->permissionModel->getByRole($selectedRole);

        // Kelompokkan per grup
        $grouped = [];
        foreach ($permissions as $p) {
            $grouped[$p['grup']][] = $p;
        }

        return view('layouts/main', [
            'title'   => 'Hak Akses Role',
            'content' => view('role_permission/_content', [
                'grouped'      => $grouped,
                'selectedRole' => $selectedRole,
                'roles'        => $this->roles,
            ]),
        ]);
    }

    public function save()
    {
        if (session()->get('role') !== 'admin') {
            return $this->forbidden('Hanya Administrator yang dapat mengubah Hak Akses Role.');
        }

        $role     = $this->request->getPost('role');
        $menuIds  = $this->request->getPost('menu_id')  ?? [];
        $canView  = $this->request->getPost('can_view') ?? [];
        $canEdit  = $this->request->getPost('can_edit') ?? [];

        if (!in_array($role, $this->roles)) {
            return redirect()->back()->with('error', 'Role tidak valid.');
        }

        foreach ($menuIds as $menuId) {
            $this->permissionModel->updatePermission(
                $role,
                (int)$menuId,
                in_array($menuId, $canView),
                in_array($menuId, $canEdit)
            );
        }

        return redirect()->to(base_url("master/role-permission?role=$role"))
                         ->with('success', "Hak akses role '$role' berhasil diupdate.");
    }
}