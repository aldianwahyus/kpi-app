<div class="d-flex align-items-center gap-2 mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-robot me-1"></i> AI Asisten KPI
    </h5>
    <small class="text-muted">
      Tanyakan apa saja seputar data KPI, analisis, dan rekomendasi
    </small>
  </div>
</div>

<!-- Info grade -->
<?= view('components/grade_info') ?>
<br>
<div class="row g-3">
  <div class="col-md-8">
    <!-- Chat window -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div id="chat-messages"
             style="height:460px;overflow-y:auto;padding:20px">

          <!-- Welcome message -->
          <div class="d-flex gap-2 mb-3">
            <div style="width:36px;height:36px;border-radius:50%;
                        background:#1F4E79;display:flex;
                        align-items:center;justify-content:center;
                        flex-shrink:0">
              <i class="ti ti-robot"
                 style="color:#fff;font-size:18px"></i>
            </div>
            <div style="background:#f0f4f8;border-radius:0 12px 12px 12px;
                        padding:12px 16px;max-width:80%;font-size:13px;
                        line-height:1.6">
              Halo! Saya adalah <strong>AI Asisten KPI</strong> Bank NTB Syariah.
              Saya siap membantu Anda menganalisis data KPI, memberikan
              rekomendasi, dan menjawab pertanyaan seputar penilaian kinerja.
              <br><br>
              Apa yang ingin Anda tanyakan?
            </div>
          </div>

        </div>

        <!-- Input area -->
        <div style="border-top:1px solid #e5e7eb;padding:16px">
          <div class="d-flex gap-2">
            <input type="text"
                   id="chat-input"
                   class="form-control"
                   placeholder="Ketik pertanyaan Anda..."
                   style="font-size:13px;border-radius:20px">
            <button id="btn-send"
                    class="btn btn-primary"
                    style="border-radius:20px;padding:8px 20px">
              <i class="ti ti-send"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="col-md-4">

    <!-- Pertanyaan cepat -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header py-2"
           style="background:#E6F1FB">
        <span class="fw-semibold"
              style="color:#1F4E79;font-size:13px">
          <i class="ti ti-bolt me-1"></i> Pertanyaan Cepat
        </span>
      </div>
      <div class="card-body py-2 px-3">
        <?php
        $quickQuestions = [
            'Siapa pegawai dengan nilai KPI tertinggi?',
            'Berapa rata-rata nilai KPI periode ini?',
            'Pegawai mana yang perlu perhatian khusus?',
            'Bagaimana distribusi grade KPI saat ini?',
            'Apa rekomendasi untuk pegawai grade C?',
            'Jelaskan cara perhitungan KPI',
            'Apa perbedaan grade M dan SB?',
            'Buatkan ringkasan eksekutif KPI periode ini',
        ];
        ?>
        <?php foreach ($quickQuestions as $q): ?>
        <button class="btn btn-light btn-sm quick-question w-100
                       text-start mb-1"
                style="font-size:12px;border:1px solid #e5e7eb"
                data-question="<?= esc($q) ?>">
          <?= esc($q) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<style>
.chat-bubble-user {
    background: #1F4E79;
    color: white;
    border-radius: 12px 0 12px 12px;
    padding: 10px 14px;
    max-width: 80%;
    font-size: 13px;
    line-height: 1.6;
}
.chat-bubble-ai {
    background: #f0f4f8;
    border-radius: 0 12px 12px 12px;
    padding: 12px 16px;
    max-width: 85%;
    font-size: 13px;
    line-height: 1.6;
}
.typing-indicator span {
    display: inline-block;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #888;
    animation: typing 1s infinite;
    margin: 0 2px;
}
.typing-indicator span:nth-child(2) { animation-delay: .2s; }
.typing-indicator span:nth-child(3) { animation-delay: .4s; }
@keyframes typing {
    0%, 80%, 100% { transform: scale(0.8); opacity: .5; }
    40% { transform: scale(1.2); opacity: 1; }
}
</style>

<script>
const BASE_URL  = '<?= base_url() ?>';
const CSRF_NAME = '<?= csrf_token() ?>';
let   csrfHash  = '<?= csrf_hash() ?>';

const chatMessages = document.getElementById('chat-messages');
const chatInput    = document.getElementById('chat-input');
const btnSend      = document.getElementById('btn-send');

function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function addMessage(content, isUser = false) {
    const wrap = document.createElement('div');
    wrap.className = 'd-flex gap-2 mb-3'
        + (isUser ? ' flex-row-reverse' : '');

    const avatar = document.createElement('div');
    avatar.style.cssText =
        'width:36px;height:36px;border-radius:50%;'
        + (isUser
            ? 'background:#E6F1FB;display:flex;align-items:center;justify-content:center;flex-shrink:0'
            : 'background:#1F4E79;display:flex;align-items:center;justify-content:center;flex-shrink:0');
    avatar.innerHTML = isUser
        ? '<i class="ti ti-user" style="color:#1F4E79;font-size:18px"></i>'
        : '<i class="ti ti-robot" style="color:#fff;font-size:18px"></i>';

    const bubble = document.createElement('div');
    bubble.className = isUser ? 'chat-bubble-user' : 'chat-bubble-ai';
    bubble.innerHTML = content;

    wrap.appendChild(avatar);
    wrap.appendChild(bubble);
    chatMessages.appendChild(wrap);
    scrollToBottom();
    return wrap;
}

function showTyping() {
    const wrap = document.createElement('div');
    wrap.className = 'd-flex gap-2 mb-3';
    wrap.id = 'typing-indicator';
    wrap.innerHTML = `
        <div style="width:36px;height:36px;border-radius:50%;
                    background:#1F4E79;display:flex;
                    align-items:center;justify-content:center;
                    flex-shrink:0">
            <i class="ti ti-robot"
               style="color:#fff;font-size:18px"></i>
        </div>
        <div class="chat-bubble-ai typing-indicator">
            <span></span><span></span><span></span>
        </div>`;
    chatMessages.appendChild(wrap);
    scrollToBottom();
}

function removeTyping() {
    document.getElementById('typing-indicator')?.remove();
}

async function sendMessage(message) {
    if (!message.trim()) return;

    addMessage(message, true);
    chatInput.value = '';
    btnSend.disabled = true;
    showTyping();

    const fd = new FormData();
    fd.append('message', message);
    fd.append(CSRF_NAME, csrfHash);

    try {
        const res  = await fetch(BASE_URL + 'ai/chat', {
            method: 'POST', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();

        // Update CSRF hash
        const newHash = res.headers.get('X-CSRF-TOKEN');
        if (newHash) csrfHash = newHash;

        removeTyping();
        addMessage(data.reply || 'Tidak ada respons.');
    } catch (err) {
        removeTyping();
        addMessage('Terjadi kesalahan. Coba lagi nanti.');
        console.error(err);
    } finally {
        btnSend.disabled = false;
        chatInput.focus();
    }
}

btnSend.addEventListener('click', () => {
    sendMessage(chatInput.value);
});

chatInput.addEventListener('keypress', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage(chatInput.value);
    }
});

document.querySelectorAll('.quick-question').forEach(btn => {
    btn.addEventListener('click', () => {
        sendMessage(btn.dataset.question);
    });
});
</script>