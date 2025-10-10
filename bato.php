<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Bato v2 Downloader + Smart Stitch</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <header>
      <h1>Bato Downloader</h1>
      <p class="muted">Scraper + SmartStitch untuk sumber Bato v2</p>
    </header>

    <section class="card">
      <form id="jobForm" onsubmit="return false;">
        <div class="field">
          <label>URL Sumber (Pakai bato v2 ya guys, jangan bato.si atau bato.ing. Jangan pakai bato v3 juga buset 😭)</label>
          <input type="url" name="source_url" required placeholder="e.g. https://bato.to/chapter/3841340">
        </div>

        <div class="grid">
          <div class="field">
            <label>Split Height (maks 24000)</label>
            <input type="number" name="split_height" min="100" max="24000" value="7500">
          </div>
          <div class="field">
            <label>Output Format</label>
            <select name="output_files_type">
              <option value=".png">.png</option>
              <option value=".jpg">.jpg</option>
              <option value=".webp">.webp</option>
              <option value=".bmp">.bmp</option>
              <option value=".tiff">.tiff</option>
              <option value=".tga">.tga</option>
            </select>
          </div>
          <div class="field">
            <label>Width Enforce <br>(* Mode 1 : lebar gambar tidak diubah, tapi kalau lebar gambarnya nggak sama, ntar hasil stitchnya juga nggak rata, terutama kalau ngambil raw bato yang ada CRnya<br>* Mode 2 : lebar gambar disamaratakan ke ukuran terkecil<br>* Mode 3 : lebar gambar disesuaikan dengan ukuran custom width)</label>
            <select name="width_enforce_type">
              <option value="0">Mode 1</option>
              <option value="1">Mode 2</option>
              <option value="2" selected>Mode 3</option>
            </select>
          </div>
          <div class="field">
            <label>Custom Width</label>
            <input type="number" name="custom_width" value="720" min="100">
          </div>
        </div>

        <div class="grid">
          <div class="field">
            <label>Sensitivity [0-100]</label>
            <input type="number" name="senstivity" value="90" min="0" max="100">
          </div>
          <div class="field">
            <label>Ignorable Pixels</label>
            <input type="number" name="ignorable_pixels" value="0" min="0" max="100">
          </div>
          <div class="field">
            <label>Scan Line Step [1-20]</label>
            <input type="number" name="scan_line_step" value="5" min="1" max="20">
          </div>
        </div>

        <div class="grid">
          <div class="field">
            <label>Batch Mode</label>
            <select name="batch_mode">
              <option value="0">Off</option>
              <option value="1">On</option>
            </select>
          </div>
          <div class="field">
            <label>Low RAM Mode (Set ke ON biar servernya nggak meledak)</label>
            <select name="low_ram">
              <option value="0">Off</option>
              <option value="1" selected>On</option>
            </select>
          </div>
          <div class="field">
            <label>Unit Images (Kalau stuck, ubah jadi 10 atau terserahlah yang penting kurang dari 20 🫠)</label>
            <input type="number" name="unit_images" value="20" min="2" max="50">
          </div>
        </div>

        <div class="actions">
          <button id="startBtn" class="btn">Mulai Proses</button>
          <span id="cooldownMsg" class="muted"></span>
        </div>
      </form>
    </section>

    <section class="card">
      <div class="progress-wrap">
        <div id="progressBar" class="progress"><span id="progressFill" style="width:0%"></span></div>
        <div class="progress-meta">
          <div id="progressText">Menunggu...</div>
          <div id="progressEta" class="muted"></div>
        </div>
      </div>

      <pre id="logBox" class="log"></pre>

      <div id="downloadBox" class="hidden">
        <a id="downloadLink" class="btn" href="#" download>Unduh ZIP</a>
        <div class="muted" id="expiryNote"></div>
      </div>
    </section>

    <footer class="muted small">
      <p>Brought to you by AstralExpress</p>
    </footer>
  </div>

  <script>
    const $ = (sel) => document.querySelector(sel);
    let currentJob = null;
    let pollTimer = null;
    let cooldownTimer = null;

    function setProgress(pct, text = "") {
      const fill = $("#progressFill");
      if (fill) fill.style.width = `${pct}%`;
      const txt = $("#progressText");
      if (txt) txt.textContent = text || `${pct}%`;
    }

    function appendLog(line) {
      const box = $("#logBox");
      if (!box) return;
      const atBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 5;
      box.textContent += line.endsWith("\n") ? line : (line + "\n");
      if (atBottom) box.scrollTop = box.scrollHeight;
    }

    function startCooldown(seconds) {
      const btn = $("#startBtn");
      if (btn) btn.disabled = true;
      const msg = $("#cooldownMsg");
      let left = seconds;
      if (msg) msg.textContent = `Tunggu ${left}s sebelum proses berikutnya...`;
      cooldownTimer = setInterval(() => {
        left--;
        if (left <= 0) {
          clearInterval(cooldownTimer);
          if (btn) btn.disabled = false;
          if (msg) msg.textContent = "";
          return;
        }
        if (msg) msg.textContent = `Tunggu ${left}s sebelum proses berikutnya...`;
      }, 1000);
    }

    async function startJob() {
      const form = $("#jobForm");
      if (!form) return;
      const formData = new FormData(form);
      try {
        const resp = await fetch('process_bato.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (!resp.ok || !data.ok) {
          appendLog(data && data.error ? data.error : 'Gagal memulai proses.');
          return;
        }
        currentJob = data.job_id;
        $("#logBox").textContent = '';
        appendLog(`Job ID: ${currentJob}`);
        setProgress(1, 'Memulai...');
        startCooldown(data.cooldown || 60);
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(pollStatus, 1000);
      } catch (e) {
        appendLog('Gagal memulai proses: ' + e.message);
      }
    }

    async function pollStatus() {
      if (!currentJob) return;
      try {
        const resp = await fetch(`status.php?job_id=${encodeURIComponent(currentJob)}`);
        const data = await resp.json();
        if (!resp.ok || !data.ok) {
          appendLog(data && data.error ? data.error : 'Gagal mengambil status.');
          clearInterval(pollTimer);
          return;
        }
        if (data.log_delta && data.log_delta.length) {
          data.log_delta.forEach(line => line && appendLog(line));
        }
        setProgress(data.progress || 0, data.stage || 'Memproses');
        const eta = $("#progressEta");
        if (eta) eta.textContent = data.eta_note ? data.eta_note : '';
        if (data.done) {
          clearInterval(pollTimer);
          setProgress(100, 'Selesai');
          const box = $("#downloadBox");
          if (box) box.classList.remove('hidden');
          const link = $("#downloadLink");
          if (link) link.href = data.zip_url;
          const expiry = $("#expiryNote");
          if (expiry) expiry.textContent = `File akan dihapus otomatis pada ${data.expires_at_local}.`;
        }
      } catch (e) {
        appendLog('Gagal mengambil status: ' + e.message);
        clearInterval(pollTimer);
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      const btn = $("#startBtn");
      if (btn) btn.addEventListener('click', startJob);
    });
  </script>
</body>
</html>
