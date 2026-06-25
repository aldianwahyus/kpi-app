<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    protected PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host       = env('MAIL_HOST', 'smtp.gmail.com');
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = env('MAIL_USERNAME');
        $this->mail->Password   = env('MAIL_PASSWORD');
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = env('MAIL_PORT', 587);
        $this->mail->CharSet    = 'UTF-8';
        $this->mail->setFrom(
            env('MAIL_FROM_ADDRESS', 'kpi@bankntbsyariah.co.id'),
            env('MAIL_FROM_NAME', 'Sistem KPI Bank NTB Syariah')
        );
    }

    /**
     * Kirim reminder — return array status untuk logging
     */
    public function sendReminderInputKpi(
        string $toEmail,
        string $toName,
        string $periodeName,
        string $deadlineDate,
        int    $belumDiisi
    ): array {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail, $toName);
            $this->mail->isHTML(true);
            $this->mail->Subject =
                "[Reminder] Input KPI Pegawai — $periodeName";
            $this->mail->Body = $this->templateReminder(
                $toName, $periodeName, $deadlineDate, $belumDiisi
            );
            $this->mail->AltBody =
                "Reminder: Segera input KPI pegawai di unit Anda. "
                . "Periode: $periodeName. Deadline: $deadlineDate. "
                . "Tersisa $belumDiisi pegawai belum diinput.";

            $this->mail->send();

            return [
                'success' => true,
                'subject' => $this->mail->Subject,
                'error'   => null,
            ];
        } catch (Exception $e) {
            log_message('error', 'Email gagal: ' . $e->getMessage());
            return [
                'success' => false,
                'subject' => "[Reminder] Input KPI Pegawai — $periodeName",
                'error'   => $this->mail->ErrorInfo ?: $e->getMessage(),
            ];
        }
    }

    public function sendApprovalNotif(
        string $toEmail,
        string $toName,
        string $pegawaiNama,
        string $status,
        string $note = ''
    ): array {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail, $toName);
            $this->mail->isHTML(true);

            $statusLabel = $status === 'approved' ? 'Disetujui' : 'Ditolak';
            $this->mail->Subject =
                "[KPI] Penilaian $pegawaiNama — $statusLabel";
            $this->mail->Body = $this->templateApproval(
                $toName, $pegawaiNama, $status, $note
            );

            $this->mail->send();

            return [
                'success' => true,
                'subject' => $this->mail->Subject,
                'error'   => null,
            ];
        } catch (Exception $e) {
            log_message('error', 'Email gagal: ' . $e->getMessage());
            return [
                'success' => false,
                'subject' => "[KPI] Penilaian $pegawaiNama — Update",
                'error'   => $this->mail->ErrorInfo ?: $e->getMessage(),
            ];
        }
    }

    private function templateReminder(
        string $nama, string $periode, string $deadline, int $belumDiisi
    ): string {
        $baseUrl = base_url('penilaian');
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:20px}
.container{max-width:580px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1)}
.header{background:#1F4E79;padding:24px 30px;text-align:center}
.header h1{color:#fff;margin:0;font-size:20px}
.header p{color:rgba(255,255,255,.7);margin:6px 0 0;font-size:13px}
.body{padding:28px 30px}
.body p{color:#374151;font-size:14px;line-height:1.6}
.stat-box{background:#EFF6FF;border-left:4px solid #2E75B6;padding:12px 16px;border-radius:0 8px 8px 0;margin:16px 0}
.stat-box .num{font-size:28px;font-weight:700;color:#1F4E79}
.deadline-box{background:#FEF3C7;border:1px solid #BF9000;border-radius:8px;padding:12px 16px;margin:16px 0;text-align:center}
.deadline-box .date{font-size:20px;font-weight:700;color:#92400E}
.btn{display:inline-block;background:#1F4E79;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;margin:16px 0}
.footer{background:#f8fafc;padding:16px 30px;text-align:center;font-size:11px;color:#9CA3AF}
</style></head><body>
<div class="container">
<div class="header"><h1>Reminder Input KPI</h1><p>Sistem Penilaian Kinerja — Bank NTB Syariah</p></div>
<div class="body">
<p>Yth. <strong>$nama</strong>,</p>
<p>Ini adalah pengingat untuk segera melakukan input penilaian KPI pegawai di unit Anda.</p>
<div class="stat-box"><div class="num">$belumDiisi pegawai</div><div>belum diinput untuk periode <strong>$periode</strong></div></div>
<div class="deadline-box"><div style="font-size:12px;color:#92400E;margin-bottom:4px">Batas Waktu Input</div><div class="date">$deadline</div></div>
<p>Segera akses sistem dan lengkapi input penilaian sebelum batas waktu berakhir.</p>
<center><a href="$baseUrl" class="btn">Buka Halaman Input KPI</a></center>
</div>
<div class="footer">Email ini dikirim otomatis oleh Sistem KPI Bank NTB Syariah.</div>
</div></body></html>
HTML;
    }

    private function templateApproval(
        string $nama, string $pegawaiNama, string $status, string $note
    ): string {
        $isApproved  = $status === 'approved';
        $statusColor = $isApproved ? '#375623' : '#C00000';
        $statusBg    = $isApproved ? '#C6EFCE'  : '#FCE4D6';
        $statusLabel = $isApproved ? 'Disetujui' : 'Ditolak';
        $baseUrl     = base_url('penilaian');
        $noteHtml    = $note ? "<p><strong>Catatan:</strong> $note</p>" : '';

        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><style>
body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:20px}
.container{max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.1)}
.header{background:#1F4E79;padding:20px 30px;text-align:center}
.header h1{color:#fff;margin:0;font-size:18px}
.body{padding:24px 30px}
.body p{color:#374151;font-size:14px;line-height:1.6}
.status-box{background:$statusBg;border-radius:8px;padding:14px 20px;text-align:center;margin:16px 0}
.status-box .s{font-size:22px;font-weight:700;color:$statusColor}
.btn{display:inline-block;background:#1F4E79;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-size:13px}
.footer{background:#f8fafc;padding:14px 30px;text-align:center;font-size:11px;color:#9CA3AF}
</style></head><body>
<div class="container">
<div class="header"><h1>Update Penilaian KPI</h1></div>
<div class="body">
<p>Yth. <strong>$nama</strong>,</p>
<p>Penilaian KPI untuk pegawai <strong>$pegawaiNama</strong> telah diproses.</p>
<div class="status-box"><div class="s">$statusLabel</div></div>
$noteHtml
<center><a href="$baseUrl" class="btn">Lihat Detail Penilaian</a></center>
</div>
<div class="footer">Email otomatis — Sistem KPI Bank NTB Syariah</div>
</div></body></html>
HTML;
    }
}