<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>MangaGo Downloader</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="container">
    <header>
      <h1>MangaGo Downloader</h1>
      <p class="muted">Masukkan URL chapter MangaGo. Tool ini akan mengunduh gambar mentah lalu otomatis di-stitch dengan SmartStitch.</p>
    </header>

    <section class="card">
      <form id="jobForm" onsubmit="return false;" data-process-url="process_mangago.php" data-status-url="status.php">
        <div class="field">
          <label>URL Chapter MangaGo</label>
          <input type="url" name="chapter_url" required placeholder="https://www.mangago.me/read-manga/.../chapter-1/">
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
              <option value="1" selected>On</option>
            </select>
          </div>
          <div class="field">
            <label>Unit Images (Low RAM)</label>
            <input type="number" name="unit_images" value="20" min="2" max="50">
          </div>
        </div>

        <div class="grid">
          <div class="field">
            <label>Isi Paket Unduhan</label>
            <select name="package_content">
              <option value="stitched" selected>Hanya hasil stitched</option>
              <option value="raw">Hanya hasil unstitched</option>
              <option value="both">Stitched + unstitched</option>
            </select>
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

      <div id="downloadBox" class="hidden">
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
