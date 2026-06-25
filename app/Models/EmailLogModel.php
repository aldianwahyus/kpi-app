<?php
namespace App\Models;

use CodeIgniter\Model;

class EmailLogModel extends Model
{
    protected $table         = 'email_log';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'jenis', 'to_email', 'to_nama', 'subject',
        'status', 'error_message', 'periode_id',
        'sent_by', 'sent_by_nama',
    ];
    
    // Tetap true agar created_at terisi otomatis
    protected $useTimestamps = true;
    
    // Kosongkan updatedField agar CI4 tidak mencari kolom updated_at
    protected $updatedField  = '';

    public function getAllOrdered(int $limit = 100): array
    {
        return $this->orderBy('created_at', 'DESC')
                    ->findAll($limit);
    }

    public function getByPeriode(int $periodeId): array
    {
        return $this->where('periode_id', $periodeId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    public function getStatistik(): array
    {
        $total     = $this->countAllResults(false);
        $terkirim  = $this->where('status', 'terkirim')->countAllResults(false);
        $gagal     = $this->where('status', 'gagal')->countAllResults();

        return [
            'total'    => $total,
            'terkirim' => $terkirim,
            'gagal'    => $gagal,
        ];
    }
}