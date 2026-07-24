<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Sama seperti KpiPegawaiBobotTahunanModel, untuk Parameter Turunan.
 */
class KpiPegawaiTurunanBobotTahunanModel extends Model
{
    protected $table         = 'kpi_pegawai_turunan_bobot_tahunan';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['kpi_pegawai_turunan_id', 'tahun', 'bobot'];
    protected $useTimestamps = true;

    public function getByRefTahun(int $turunanId, int $tahun): ?array
    {
        return $this->where('kpi_pegawai_turunan_id', $turunanId)
                    ->where('tahun', $tahun)
                    ->first();
    }

    public function upsert(int $turunanId, int $tahun, ?float $bobot): void
    {
        $existing = $this->getByRefTahun($turunanId, $tahun);
        if ($existing) {
            $this->update($existing['id'], ['bobot' => $bobot]);
        } else {
            $this->insert([
                'kpi_pegawai_turunan_id' => $turunanId,
                'tahun'                  => $tahun,
                'bobot'                  => $bobot,
            ]);
        }
    }

    public function getIndexedByRefAndTahun(array $turunanIds, int $tahun): array
    {
        if (empty($turunanIds)) {
            return [];
        }

        $rows = $this->whereIn('kpi_pegawai_turunan_id', $turunanIds)
                     ->where('tahun', $tahun)
                     ->findAll();

        $indexed = [];
        foreach ($rows as $r) {
            $indexed[$r['kpi_pegawai_turunan_id']] = $r['bobot'];
        }
        return $indexed;
    }
}
