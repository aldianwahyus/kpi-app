<?php
namespace App\Services;

class AuditService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function log(
        string $tableName,
        int    $recordId,
        string $action,
        ?array $oldValue  = null,
        ?array $newValue  = null,
        string $keterangan = ''
    ): void {
        // Ambil info user dari session
        $userId      = session()->get('user_id');
        $userNama    = session()->get('nama') ?? 'System';
        $userEmail   = session()->get('email') ?? '';

        // Ambil jabatan dari tabel pegawai
        $userJabatan = '';
        $pegawaiId   = session()->get('pegawai_id');
        if ($pegawaiId) {
            $pegawai = $this->db->table('pegawai')
                ->where('id', $pegawaiId)
                ->get()->getRowArray();
            $userJabatan = $pegawai['jabatan'] ?? '';
        }

        // Generate keterangan otomatis jika kosong
        if (empty($keterangan) && $oldValue && $newValue) {
            $keterangan = $this->generateKeterangan($oldValue, $newValue);
        }

        $this->db->table('audit_log')->insert([
            'table_name'   => $tableName,
            'record_id'    => $recordId,
            'action'       => $action,
            'user_id'      => $userId,
            'user_nama'    => $userNama,
            'user_jabatan' => $userJabatan,
            'user_email'   => $userEmail,
            'old_value'    => $oldValue ? json_encode($oldValue) : null,
            'new_value'    => $newValue ? json_encode($newValue) : null,
            'keterangan'   => $keterangan,
            'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    private function generateKeterangan(array $old, array $new): string
    {
        $changes = [];
        $fieldLabels = [
            'target'      => 'Target',
            'realisasi'   => 'Realisasi',
            'capaian'     => 'Capaian',
            'catatan'     => 'Catatan',
            'status'      => 'Status',
            'reject_note' => 'Catatan Reject',
        ];

        foreach ($new as $key => $val) {
            if (isset($old[$key]) && $old[$key] != $val) {
                $label = $fieldLabels[$key] ?? $key;
                $oldVal = is_numeric($old[$key])
                    ? number_format((float)$old[$key], 2)
                    : $old[$key];
                $newVal = is_numeric($val)
                    ? number_format((float)$val, 2)
                    : $val;
                $changes[] = "$label: $oldVal → $newVal";
            }
        }

        return empty($changes)
            ? 'Tidak ada perubahan'
            : implode(', ', $changes);
    }

    public function logDraftUlang(
    int $penilaianId,
    string $alasan,
    string $requestedByNama,
    string $confirmedByNama
    ): void {
        $this->log(
            'penilaian',
            $penilaianId,
            'draft_ulang',
            ['status' => 'approved'],
            ['status' => 'draft'],
            "Draft ulang dikonfirmasi oleh $confirmedByNama (diminta oleh $requestedByNama). Alasan: $alasan"
        );
    }
}