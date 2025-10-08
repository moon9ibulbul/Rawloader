<?php
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Downloader — NewToki</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <style>
    .card{max-width:720px;margin:32px auto;padding:20px;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 10px 20px rgba(0,0,0,.05)}
    .row{display:flex;gap:12px;align-items:center}
    .row>label{width:180px}
    .row+ .row{margin-top:12px}
    .actions{display:flex;gap:12px;align-items:center;margin-top:16px}
    .progress{height:10px;background:#eee;border-radius:999px;overflow:hidden}
    .progress>div{height:100%;width:0}
    .hidden{display:none}
  </style>
</head>
<body>
  <main class="card">
    <h1>Unduh dari NewToki (URL penuh)</h1>
    <p>Masukkan URL laman episode newtoki.biz (bukan newtoki468). Sistem akan scrape semua gambar pada laman tersebut</p>

    <form id="jobForm" onsubmit="return false;">
      <div class="row">
        <label>URL episode</label>
        <input type="url" name="url" placeholder="https://newtoki..." required style="flex:1" />
      </div>

      <div class="row"><label>Split height</label><input type="number" name="split_height" value="7500" min="500" max="24000" /></div>
      <div class="row"><label>Output type</label>
        <select name="output_files_type">
          <option value=".png">.png</option>
          <option value=".jpg">.jpg</option>
          <option value=".webp">.webp</option>
        </select>
      </div>
      <div class="row"><label>Unit images</label><input type="number" name="unit_images" value="20" min="3" max="50" /></div>
      <div class="row"><label>Enforce width</label>
        <select name="width_enforce_type">
          <option value="0">No enforce</option>
          <option value="1">Custom width</option>
        </select>
      </div>
      <div class="row"><label>Custom width</label><input type="number" name="custom_width" value="720" min="360" max="2000" /></div>
      <div class="row"><label>Sensitivity</label><input type="number" name="senstivity" value="90" min="50" max="100" /></div>
      <div class="row"><label>Ignorable pixels</label><input type="number" name="ignorable_pixels" value="0" min="0" max="50" /></div>
      <div class="row"><label>Scan line step</label><input type="number" name="scan_line_step" value="5" min="1" max="20" /></div>

      <div class="actions">
        <button id="startBtn" type="button">Mulai</button>
        <span id="cooldownMsg"></span>
      </div>

      <div class="row" style="margin-top:16px">
        <div class="progress" style="flex:1"><div id="progressFill"></div></div>
        <span id="progressText" style="width:100px;text-align:right">0%</span>
      </div>
      <div style="margin-top:6px;color:#666" id="progressEta"></div>

      <div id="downloadBox" class="hidden" style="margin-top:16px">
        <a id="downloadLink" class="btn">Unduh ZIP</a>
        <div id="expiryNote" style="color:#666;margin-top:6px"></div>
      </div>
    </form>

    <p style="margin-top:16px"><a href="index.php">← Kembali ke menu</a></p>
  </main>

  <script>
  const $ = (s)=>document.querySelector(s);
  function setProgress(p,t){ const f=$('#progressFill'),x=$('#progressText'); if(f)f.style.width=p+'%'; if(x)x.textContent=t||(p+'%'); }
  let currentJob=null, poll=null, cdTimer=null;
  function startCooldown(sec){ const btn=$('#startBtn'),label=$('#cooldownMsg'); if(btn)btn.disabled=true; let left=sec; if(label)label.textContent=`Tunggu ${left}s...`; cdTimer=setInterval(()=>{left--; if(left<=0){clearInterval(cdTimer); if(btn)btn.disabled=false; if(label)label.textContent='';} else { if(label)label.textContent=`Tunggu ${left}s...`; }},1000); }
  async function startJob(){
    const data=new FormData($('#jobForm'));
    const r=await fetch('process_toki.php',{method:'POST',body:data});
    let j={}; try{ j=await r.json(); }catch(e){}
    if(!r.ok||!j.ok){ alert(j.error||'Gagal memulai proses'); return; }
    currentJob=j.job_id; setProgress(1,'Memulai...'); startCooldown(j.cooldown||60);
    poll=setInterval(pollStatus,1000);
  }
  async function pollStatus(){ if(!currentJob) return; const r=await fetch('status.php?job_id='+encodeURIComponent(currentJob)); let j={}; try{ j=await r.json(); }catch(e){};
    if(!r.ok||!j.ok){ clearInterval(poll); alert(j.error||'Gagal ambil status'); return; }
    setProgress(j.progress||0, j.stage||'Memproses'); if(j.eta_note) $('#progressEta').textContent=j.eta_note; else $('#progressEta').textContent='';
    if(j.done){ clearInterval(poll); setProgress(100,'Selesai'); $('#downloadBox').classList.remove('hidden'); $('#downloadLink').href=j.zip_url; $('#expiryNote').textContent='File akan dihapus otomatis pada '+j.expires_at_local; }
  }
  document.addEventListener('DOMContentLoaded',()=>{ const btn=$('#startBtn'); if(btn) btn.addEventListener('click',startJob); });
  </script>
</body>
</html>
