<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>XToon Downloader</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <header>
      <h1>XToon Downloader</h1>
      <p class="muted">Download chapter dari https://t1.xtoon2.com (NewToki error, jadi ku ganti ini. Sama aja kok)</p>
    </header>

    <section class="card">
      <form id="jobForm" onsubmit="return false;" data-process-url="process_toki.php" data-status-url="status.php">
        <div class="grid">
          <div class="field">
            <label>URL Episode</label>
            <input type="url" name="url" placeholder="e.g. https://t1.xtoon2.com/chapter/123456" required>
          </div>
          <div class="grid">
          <div class="field">
            <label>Output Format</label>
            <select name="output_files_type">
              <option value=".png">.png</option>
              <option value=".jpg">.jpg</option>
              <option value=".webp">.webp</option>
            </select>
          </div>
          <div class="field">
            <label>Unit Images (Low RAM)</label>
            <input type="number" name="unit_images" value="20" min="3" max="50">
          </div>
          <div class="field">
            <label>Width Enforce</label>
            <select name="width_enforce_type">
              <option value="0">No enforce</option>
              <option value="1">Custom width</option>
            </select>
          </div>
          <div class="field">
            <label>Custom Width (mode=1)</label>
            <input type="number" name="custom_width" value="720" min="360" max="2000">
          </div>
        </div>

      <div class="grid">
          <div class="field">
            <label>Sensitivity [50-100]</label>
            <input type="number" name="senstivity" value="90" min="50" max="100">
          </div>
          <div class="field">
            <label>Ignorable Pixels</label>
            <input type="number" name="ignorable_pixels" value="0" min="0" max="50">
          </div>
          <div class="field">
            <label>Scan Line Step [1-20]</label>
            <input type="number" name="scan_line_step" value="5" min="1" max="20">
          </div>
        </div>

        <div class="actions">
          <button id="startBtn" class="btn" type="button">Mulai Proses</button>
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

      <div id="downloadBox" class="hidden" style="margin-top:16px">
        <a id="downloadLink" class="btn" href="#" download>Unduh ZIP</a>
        <div class="muted" id="expiryNote"></div>
      </div>
    </section>

    <footer class="muted small">
      <p>Brought to you by AstralExpress</p>
    </footer>
  </div>

  <script src="assets/js/app.js?v=4"></script>
</body>
</html>
