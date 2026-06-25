<?php
namespace App\Services;

class AiAssistantService
{
    protected string $apiKey;
    protected string $model = 'gemini-2.5-flash'; // Menggunakan versi stable terbaru

    public function __construct()
    {
        $this->apiKey = env('gemini.apiKey', '');
    }

    public function chat(string $userMessage, array $context = []): string
    {
        if (empty($this->apiKey)) {
            return 'API Key Gemini belum dikonfigurasi.';
        }

        $systemPrompt = $this->buildSystemPrompt($context);

        // Perbaikan: Pemisahan System Instruction & User Content sesuai standar Google API
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userMessage]
                    ]
                ]
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ],
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 1500,
            ]
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        
        // Endpoint resmi Google Gemini API v1beta
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key=" . $this->apiKey;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false, // Aman untuk localhost / XAMPP
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            log_message('error', 'Gemini cURL error: ' . $error);
            return 'Gagal terhubung ke Gemini AI. (cURL Error)';
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            // Mencatat detail pesan error asli dari Google ke log Anda
            $errDetail = $data['error']['message'] ?? 'No message';
            log_message('error', "Gemini API Error [HTTP $httpCode]: " . $errDetail);
            
            if ($httpCode === 400) {
                return 'Format permintaan ke AI tidak valid. Hubungi Administrator.';
            }
            if ($httpCode === 403) {
                return 'API Key Gemini Anda tidak valid atau tidak aktif.';
            }
            return 'Asisten AI sedang gangguan. Coba lagi nanti.';
        }

        // Membaca struktur response content dari Gemini
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Tidak ada respons dari AI.';
    }

    private function buildSystemPrompt(array $context): string
    {
        $contextStr = '';
        if (!empty($context)) {
            $contextStr = "\n\nData KPI aktual yang harus kamu analisis:\n"
                . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return <<<PROMPT
Kamu adalah Asisten KPI Bank NTB Syariah yang cerdas dan membantu.
Tugasmu adalah membantu HR Manager, Manajer, dan pegawai memahami dan menganalisis data penilaian KPI.

Klasifikasi Grade yang berlaku:
- M (Memuaskan): nilai ≥ 90 — Kinerja melebihi ekspektasi
- SB (Sangat Baik): nilai 75-89 — Kinerja melampaui target
- B (Baik): nilai 60-74 — Kinerja memenuhi target
- C (Cukup): nilai < 60 — Kinerja perlu ditingkatkan

Gunakan bahasa Indonesia yang profesional namun ramah (gaya komunikasi perbankan syariah).
Berikan jawaban yang ringkas, jelas, dan berorientasi pada tindakan (actionable).$contextStr
PROMPT;
    }
}