<?php
namespace App\Services;

class KpiCalculationService
{
    /**
     * Hitung skor capaian dari realisasi vs target
     * Hasil skor di-clamp ke range 10-100
     */
    public function hitungSkorCapaian(
        float  $realisasi,
        float  $target,
        string $polarity = 'max',
        bool   $isCapped = true
    ): float {
        if ($target == 0) return 10;

        // Pemanggil (PenilaianController::store, ajaxHitung, ajaxHitungTurunan)
        // bertanggung jawab membedakan "belum diisi" (null/string kosong) dari
        // "realisasi = 0 yang genuinely valid" SEBELUM memanggil fungsi ini —
        // fungsi ini hanya menerima nilai yang memang sudah dipastikan terisi.
        if ($polarity === 'max') {
            // realisasi 0 di sini berarti capaian 0 (belum ada progres nyata),
            // hasil akhir dibatasi ke skor lantai (10) oleh clamp di bawah.
            $rasio = $realisasi / $target;
        } else {
            // minimize: realisasi lebih kecil = lebih baik. Realisasi 0 adalah
            // capaian TERBAIK yang mungkin (mis. 0 kasus fraud/komplain) —
            // secara matematis itu rasio tak terhingga, jadi langsung
            // dikembalikan sebagai skor maksimum alih-alih membagi dengan nol.
            if ($realisasi == 0) {
                return $isCapped ? 100 : 150;
            }
            $rasio = $target / $realisasi;
        }

        $skor = $rasio * 100;

        if ($isCapped) {
            $skor = min(100, $skor);
        }

        return max(10, min(150, $skor)); // batas atas longgar untuk capped=false
    }

    /**
     * Hitung capaian KPI Unit (realisasi vs target, mempertimbangkan polarity).
     * Berbeda dari hitungSkorCapaian() yang dipakai untuk KPI individu pegawai
     * (skala skor 10-100) — KPI Unit murni mencatat rasio capaian divisi,
     * tanpa bobot per-pegawai, dengan hasil di-cap maksimal 150% (1.5).
     */
    public function hitungCapaian(
        float  $target,
        float  $realisasi,
        string $polarity   = 'max',
        string $perubahan  = 'pos'
    ): float {
        if ($target == 0) return 0;

        $isMaxLike = ($polarity === 'max' && $perubahan === 'pos');

        if ($isMaxLike) {
            // realisasi 0 = belum ada progres nyata untuk KPI 'max' -> capaian 0.
            if ($realisasi == 0) return 0;
            $capaian = $realisasi / $target;
        } else {
            // minimize: realisasi 0 adalah capaian TERBAIK yang mungkin
            // (mis. 0 kasus fraud/komplain) -> dibatasi ke plafon 150%,
            // bukan dihitung sebagai pembagian dengan nol.
            if ($realisasi == 0) return 1.5;
            $capaian = $target / $realisasi;
        }

        return min(1.5, max(0, $capaian));
    }

    public function hitungKontribusi(float $skor, float $bobot): float
    {
        $skor = max(10, min(100, $skor));
        return $skor * $bobot;
    }

    public function isValidSkor(float $skor): bool
    {
        return $skor >= 10 && $skor <= 100;
    }

    public function getGrade(float $nilai): string
    {
        return match(true) {
            $nilai >= 91.00 => 'M',
            $nilai >= 81.00 => 'SB',
            $nilai >= 71.00 => 'B',
            default         => 'C',
        };
    }

    public function getGradeLabel(string $grade): string
    {
        return match($grade) {
            'M'     => 'Memuaskan',
            'SB'    => 'Sangat Baik',
            'B'     => 'Baik',
            'C'     => 'Cukup',
            default => '—',
        };
    }

    public function getGradeColor(string $grade): array
    {
        return match($grade) {
            'M'     => ['bg'=>'#1F4E79','color'=>'#FFFFFF'],
            'SB'    => ['bg'=>'#C6EFCE','color'=>'#375623'],
            'B'     => ['bg'=>'#BDD7EE','color'=>'#1F4E79'],
            'C'     => ['bg'=>'#FFF2CC','color'=>'#7F6000'],
            default => ['bg'=>'#f0f0f0','color'=>'#888'],
        };
    }

    /**
     * Warna badge berdasarkan SKOR (bukan grade huruf)
     * Dipakai khusus untuk AJAX real-time feedback
     */
    public function getColorBySkor(float $skor): string
    {
        return match(true) {
            $skor >= 91 => 'success',
            $skor >= 81 => 'primary',
            $skor >= 71 => 'warning',
            default     => 'danger',
        };
    }

    public function getGradeInfo(): array
    {
        return [
            'M'  => ['label'=>'Memuaskan',   'range'=>'91.00 – 100.00', 'min'=>91.00,'max'=>100.00,'bg'=>'#1F4E79','color'=>'#FFFFFF','desc'=>'Kinerja melebihi ekspektasi secara konsisten'],
            'SB' => ['label'=>'Sangat Baik',  'range'=>'81.00 – 90.99',  'min'=>81.00,'max'=>90.99, 'bg'=>'#C6EFCE','color'=>'#375623','desc'=>'Kinerja melampaui target yang ditetapkan'],
            'B'  => ['label'=>'Baik',         'range'=>'71.00 – 80.99',  'min'=>71.00,'max'=>80.99, 'bg'=>'#BDD7EE','color'=>'#1F4E79','desc'=>'Kinerja memenuhi target yang ditetapkan'],
            'C'  => ['label'=>'Cukup',        'range'=>'< 71.00',        'min'=>0,    'max'=>70.99, 'bg'=>'#FFF2CC','color'=>'#7F6000','desc'=>'Kinerja perlu ditingkatkan'],
        ];
    }
}