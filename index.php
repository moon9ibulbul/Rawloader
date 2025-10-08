<?php
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Downloader Menu</title>
  <style>
    body{font-family: ui-sans-serif,system-ui,Segoe UI,Roboto,Helvetica,Arial; background:#f7f7fb; margin:0; padding:0;}
    .wrap{max-width:880px;margin:64px auto;padding:24px}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;box-shadow:0 10px 20px rgba(0,0,0,.05);}
    .card h2{margin:0 0 8px}
    .btn{display:inline-block;margin-top:8px;padding:10px 14px;border-radius:10px;border:1px solid #111; text-decoration:none}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Downloader</h1>
    <p>Pilih sumber unduhan:</p>
    <div class="grid">
      <div class="card">
        <h2>Naver Webtoon</h2>
        <p>Masukkan ID komik & satu episode.</p>
        <a class="btn" href="nava.php">Buka</a>
      </div>
      <div class="card">
        <h2>NewToki</h2>
        <p>Masukkan URL penuh laman episode NewToki, otomatis scrape semua gambar.</p>
        <a class="btn" href="toki.php">Buka</a>
      </div>
    </div>
  </div>
</body>
</html>
