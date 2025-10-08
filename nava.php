<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Webtoon Downloader + Smart Stitch</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <header>
      <h1>Naver Webtoon Downloader</h1>
      <p class="muted">Hanya untuk chapter gratis dan bukan smut</p>
    </header>

    <section class="card">
      <form id="jobForm" onsubmit="return false;">
        <div class="grid">
          <div class="field">
            <label>Comic ID (Naver)</label>
            <input type="number" name="comic_id" required placeholder="contoh: 183559">
          </div>
          <div class="field">
<label>Episode</label>
<input type="number" name="start" min="1" required placeholder="contoh: 12">
          </div>
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
            <label>Width Enforce</label>
            <select name="width_enforce_type">
              <option value="0">No enforce</option>
              <option value="1">Min width</option>
              <option value="2">Custom width</option>
            </select>
          </div>
          <div class="field">
            <label>Custom Width (mode=2)</label>
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
            <label>Low RAM Mode</label>
            <select name="low_ram">
              <option value="0">Off</option>
              <option value="1">On</option>
            </select>
          </div>
          <div class="field">
            <label>Unit Images (Low RAM)</label>
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
      
      <div id="downloadBox" class="hidden">
        <a id="downloadLink" class="btn" href="#" download>Unduh ZIP</a>
        <div class="muted" id="expiryNote"></div>
      </div>
    </section>

    <footer class="muted small">
      <p>Brought to you by AstralExpress</p>
    </footer>
  </div>

  <script src="assets/js/app.js?v=3"></script>
</body>
</html>
