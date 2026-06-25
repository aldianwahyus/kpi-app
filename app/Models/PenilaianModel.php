<?php

namespace App\Models;

use CodeIgniter\Model;

class PenilaianModel extends Model
{
    protected $table         = 'penilaian';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'pegawai_id', 'kpi_id', 'periode_id',
        'target', 'realisasi', 'skor', 'capaian',
        'nilai_kontribusi', 'catatan',
        'status', 'submitted_at',
        'approved_by', 'approved_at', 'reject_note',
        'input_by', 'verified_by', 'verified_at',
    ];
    protected $useTimestamps = true;

    public function getIndexedByKpi(int $pegawaiId, int $periodeId): array
    {
        $data = $this->where('pegawai_id', $pegawaiId)
                     ->where('periode_id', $periodeId)
                     ->findAll();

        $indexed = [];
        foreach ($data as $row) {
            $indexed[$row['kpi_id']] = $row;
        }
        return $indexed;
    }

    public function upsert(int $pegawaiId, int $kpiId, int $periodeId, array $data)
    {
        $existing = $this->where('pegawai_id', $pegawaiId)
                         ->where('kpi_id', $kpiId)
                         ->where('periode_id', $periodeId)
                         ->first();

        if ($existing) {
            return $this->update($existing['id'], $data);
        } else {
            $data['pegawai_id'] = $pegawaiId;
            $data['kpi_id']     = $kpiId;
            $data['periode_id'] = $periodeId;
            return $this->insert($data);
        }
    }

    public function getNilaiAkhir(int $pegawaiId, int $periodeId): float
    {
        $result = $this->db->table('penilaian p')
            // HAPUS * 100: Karena nilai_kontribusi sudah dalam skala 0-100 (desimal)
            ->select('SUM(p.nilai_kontribusi) as total')
            ->join('kpi_pegawai kp', 'kp.kpi_id = p.kpi_id AND kp.pegawai_id = p.pegawai_id')
            ->where('p.pegawai_id', $pegawaiId)
            ->where('p.periode_id', $periodeId)
            ->get()->getRowArray();

        return round((float)($result['total'] ?? 0), 2);
    }

    public function getRekapKombinasi(int $periodeId): array
    {
        $rows = $this->db->table('penilaian p')
            ->select('p.pegawai_id, pg.nama, pg.jabatan, pg.unit, pg.divisi_id, d.nama as divisi, dir.nama as direktorat,
                      SUM(p.nilai_kontribusi) as nilai_akhir, COUNT(p.id) as jumlah_kpi,
                      CASE 
                        WHEN SUM(CASE WHEN p.status = \'draft\' THEN 1 ELSE 0 END) > 0 THEN \'draft\'
                        WHEN SUM(CASE WHEN p.status = \'rejected\' THEN 1 ELSE 0 END) > 0 THEN \'rejected\'
                        WHEN SUM(CASE WHEN p.status = \'submitted\' THEN 1 ELSE 0 END) > 0 THEN \'submitted\'
                        ELSE \'approved\'
                      END as status')
            ->join('pegawai pg', 'pg.id = p.pegawai_id')
            ->join('divisi d', 'd.id = pg.divisi_id', 'left')
            ->join('direktorat dir', 'dir.id = d.direktorat_id', 'left')
            ->where('p.periode_id', $periodeId)
            ->groupBy('p.pegawai_id')
            ->orderBy('nilai_akhir', 'DESC')
            ->get()->getResultArray();

        $calculator = new \App\Services\KpiCalculationService();
        foreach ($rows as &$row) {
            $nilai = (float)$row['nilai_akhir'];
            $grade = $nilai > 0 ? $calculator->getGrade($nilai) : '—';
            $row['grade'] = $grade;
            $row['grade_label'] = $nilai > 0 ? $calculator->getGradeLabel($grade) : '—';
        }
        return $rows;
    }
}