<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Master Target — Bobot KPI Induk, satu nilai untuk satu tahun penuh
 * (berbeda dari Target yang per bulan).
 */
class KpiPegawaiBobotTahunanModel extends Model
{
    protected $table         = 'kpi_pegawai_bobot_tahunan';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['kpi_pegawai_id', 'tahun', 'bobot'];
    protected $useTimestamps = true;

    public function getByRefTahun(int $kpiPegawaiId, int $tahun): ?array
    {
        return $this->where('kpi_pegawai_id', $kpiPegawaiId)
                    ->where('tahun', $tahun)
                    ->first();
    }

    public function upsert(int $kpiPegawaiId, int $tahun, ?float $bobot): void
    {
        $existing = $this->getByRefTahun($kpiPegawaiId, $tahun);
        if ($existing) {
            $this->update($existing['id'], ['bobot' => $bobot]);
        } else {
            $this->insert([
                'kpi_pegawai_id' => $kpiPegawaiId,
                'tahun'          => $tahun,
                'bobot'          => $bobot,
            ]);
        }
    }

    public function getIndexedByRefAndTahun(array $kpiPegawaiIds, int $tahun): array
    {
        if (empty($kpiPegawaiIds)) {
            return [];
        }

        $rows = $this->whereIn('kpi_pegawai_id', $kpiPegawaiIds)
                     ->where('tahun', $tahun)
                     ->findAll();

        $indexed = [];
        foreach ($rows as $r) {
            $indexed[$r['kpi_pegawai_id']] = $r['bobot'];
        }
        return $indexed;
    }
}
