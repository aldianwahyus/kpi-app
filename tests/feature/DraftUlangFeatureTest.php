<?php

use App\Models\DraftUlangRequestModel;
use App\Models\PenilaianModel;
use App\Models\PeriodeModel;
use Tests\Support\KpiTestCase;

/**
 * @internal
 */
final class DraftUlangFeatureTest extends KpiTestCase
{
    private function setupPenilaianApproved(): array
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Satu');
        $periodeId    = $this->makePeriodeAktif();

        (new PenilaianModel())->insert([
            'pegawai_id' => $pegawaiId,
            'kpi_id'     => $this->makeKpiUnit($direktoratId, 'TK-F1'),
            'periode_id' => $periodeId,
            'realisasi'  => 100,
            'skor'       => 100,
            'status'     => 'approved',
        ]);

        return ['divisiId' => $divisiId, 'pegawaiId' => $pegawaiId, 'periodeId' => $periodeId];
    }

    public function testApproverTidakBisaMengajukanDraftUlangUntukStatusBelumApproved(): void
    {
        $direktoratId = $this->makeDirektorat();
        $divisiId     = $this->makeDivisi($direktoratId, 'DVA');
        $pegawaiId    = $this->makePegawai($divisiId, 'Pegawai Satu');
        $this->makePeriodeAktif();
        // Sengaja tidak ada penilaian berstatus approved untuk pegawai ini.

        $result = $this->withSession($this->sessionFor('approver', 1, $pegawaiId))
            ->post("draft-ulang/request-pegawai/{$pegawaiId}", ['alasan' => 'Ada kesalahan input.']);

        $result->assertRedirect();
        $this->assertSame(0, (new DraftUlangRequestModel())->countAllResults());
    }

    public function testApproverBisaMengajukanDraftUlangUntukStatusApproved(): void
    {
        $ctx = $this->setupPenilaianApproved();

        $result = $this->withSession($this->sessionFor('approver', 1, $ctx['pegawaiId']))
            ->post("draft-ulang/request-pegawai/{$ctx['pegawaiId']}", ['alasan' => 'Ada kesalahan input realisasi.']);

        $result->assertRedirect();
        $req = (new DraftUlangRequestModel())->where('pegawai_id', $ctx['pegawaiId'])->first();
        $this->assertNotNull($req);
        $this->assertSame('pending', $req['status']);
    }

    public function testTidakBisaMengajukanDraftUlangKeduaSaatMasihPending(): void
    {
        $ctx = $this->setupPenilaianApproved();

        $this->withSession($this->sessionFor('approver', 1, $ctx['pegawaiId']))
            ->post("draft-ulang/request-pegawai/{$ctx['pegawaiId']}", ['alasan' => 'Alasan pertama.']);

        $this->withSession($this->sessionFor('approver', 1, $ctx['pegawaiId']))
            ->post("draft-ulang/request-pegawai/{$ctx['pegawaiId']}", ['alasan' => 'Alasan kedua.']);

        $this->assertSame(1, (new DraftUlangRequestModel())->where('pegawai_id', $ctx['pegawaiId'])->countAllResults());
    }

    public function testAdminKonfirmasiMengembalikanPenilaianKeStatusDraft(): void
    {
        $ctx = $this->setupPenilaianApproved();

        $this->withSession($this->sessionFor('approver', 1, $ctx['pegawaiId']))
            ->post("draft-ulang/request-pegawai/{$ctx['pegawaiId']}", ['alasan' => 'Kesalahan input.']);

        $req = (new DraftUlangRequestModel())->where('pegawai_id', $ctx['pegawaiId'])->first();

        $result = $this->withSession($this->sessionFor('admin', 2))
            ->post("draft-ulang/confirm/{$req['id']}", ['catatan_admin' => 'Disetujui.']);

        $result->assertRedirect();

        $penilaian = (new PenilaianModel())->where('pegawai_id', $ctx['pegawaiId'])->first();
        $this->assertSame('draft', $penilaian['status']);

        $reqAfter = (new DraftUlangRequestModel())->find($req['id']);
        $this->assertSame('dikonfirmasi', $reqAfter['status']);
    }

    // ── Regresi: Admin tidak boleh bisa mengonfirmasi draft ulang untuk
    // periode yang sudah ditutup — data yang sudah dikunci tidak boleh
    // terbuka kembali secara tidak sengaja.
    public function testAdminTidakBisaKonfirmasiJikaPeriodeSudahTutup(): void
    {
        $ctx = $this->setupPenilaianApproved();

        $this->withSession($this->sessionFor('approver', 1, $ctx['pegawaiId']))
            ->post("draft-ulang/request-pegawai/{$ctx['pegawaiId']}", ['alasan' => 'Kesalahan input.']);

        $req = (new DraftUlangRequestModel())->where('pegawai_id', $ctx['pegawaiId'])->first();

        // Tutup periode SETELAH permintaan diajukan (skenario realistis:
        // periode ditutup sebelum admin sempat memproses permintaan).
        (new PeriodeModel())->update($ctx['periodeId'], ['status' => 'tutup']);

        $result = $this->withSession($this->sessionFor('admin', 2))
            ->post("draft-ulang/confirm/{$req['id']}", ['catatan_admin' => 'Disetujui.']);

        $result->assertRedirect();

        $penilaian = (new PenilaianModel())->where('pegawai_id', $ctx['pegawaiId'])->first();
        $this->assertSame('approved', $penilaian['status'], 'Penilaian tidak boleh kembali ke draft karena periode sudah tutup.');

        $reqAfter = (new DraftUlangRequestModel())->find($req['id']);
        $this->assertSame('pending', $reqAfter['status'], 'Permintaan tidak boleh berubah status karena ditolak oleh guard periode-tutup.');
    }

    public function testNonAdminTidakBisaMengonfirmasiDraftUlang(): void
    {
        $ctx = $this->setupPenilaianApproved();

        $this->withSession($this->sessionFor('approver', 1, $ctx['pegawaiId']))
            ->post("draft-ulang/request-pegawai/{$ctx['pegawaiId']}", ['alasan' => 'Kesalahan input.']);

        $req = (new DraftUlangRequestModel())->where('pegawai_id', $ctx['pegawaiId'])->first();

        $result = $this->withSession($this->sessionFor('hr', 2))
            ->post("draft-ulang/confirm/{$req['id']}", ['catatan_admin' => 'Disetujui.']);

        $result->assertRedirect();
        $reqAfter = (new DraftUlangRequestModel())->find($req['id']);
        $this->assertSame('pending', $reqAfter['status']);
    }
}
