// ===== Safe DOM helpers =====
const Noop = new Proxy(function(){}, {
  get: () => Noop, set: () => true, apply: () => undefined
});
const $ = (sel)=> document.querySelector(sel) || Noop;

// Global error catcher: jangan biarkan 1 error mematikan app
window.addEventListener("error", e => {
  try { console.log("[app-error]", e.message, e.filename, e.lineno); } catch(_) {}
});

function setProgress(pct, text=""){
  $("#progressFill").style.width = `${pct}%`;
  $("#progressText").textContent = text || `${pct}%`;
}

function appendLog(line){
  const box = document.querySelector("#logBox");
  if (box) {
    const atBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 5;
    box.textContent += line.endsWith("\n") ? line : (line+"\n");
    if (atBottom) box.scrollTop = box.scrollHeight;
  } else {
    try { console.log("[log]", line); } catch(_) {}
  }
}

let currentJob = null;
let pollTimer  = null;
let cooldownTimer = null;
let processEndpoint = "process.php";
let statusEndpoint = "status.php";

function startCooldown(seconds){
  $("#startBtn").disabled = true;
  let left = seconds;
  $("#cooldownMsg").textContent = `Tunggu ${left}s sebelum proses berikutnya...`;
  cooldownTimer = setInterval(()=>{
    left--;
    if(left <= 0){
      clearInterval(cooldownTimer);
      $("#startBtn").disabled = false;
      $("#cooldownMsg").textContent = "";
      return;
    }
    $("#cooldownMsg").textContent = `Tunggu ${left}s sebelum proses berikutnya...`;
  }, 1000);
}

async function startJob(){
  const form = document.querySelector("#jobForm");
  if (!form) { console.log("[log] #jobForm tidak ada"); return; }
  const data = new FormData(form);
  processEndpoint = form.dataset.processUrl || "process.php";
  statusEndpoint = form.dataset.statusUrl || "status.php";
  let j = {};
  let resp;
  try {
    resp = await fetch(processEndpoint, { method: "POST", body: data });
    try { j = await resp.json(); } catch(_) {}
  } catch (e) {
    appendLog("Gagal memulai proses: " + e.message);
    return;
  }
  if(!resp?.ok || !j?.ok){
    appendLog((j && j.error) ? j.error : "Gagal memulai proses.");
    return;
  }
  currentJob = j.job_id;
  setProgress(1, "Memulai...");
  appendLog("Job ID: "+currentJob);
  startCooldown(j.cooldown || 60);
  pollTimer = setInterval(pollStatus, 1000);
}

async function pollStatus(){
  if(!currentJob) return;
  let j = {};
  let resp;
  try {
    let statusUrl;
    try {
      statusUrl = new URL(statusEndpoint, window.location.href);
    } catch (_) {
      statusUrl = new URL("status.php", window.location.href);
    }
    statusUrl.searchParams.set("job_id", currentJob);
    resp = await fetch(statusUrl.toString());
    try { j = await resp.json(); } catch(_) {}
  } catch (e) {
    appendLog("Gagal mengambil status: " + e.message);
    clearInterval(pollTimer);
    return;
  }
  if(!resp?.ok || !j?.ok){
    appendLog((j && j.error) ? j.error : "Gagal mengambil status.");
    clearInterval(pollTimer);
    return;
  }
  if(j.log_delta && j.log_delta.length){
    j.log_delta.forEach(ln=>appendLog(ln));
  }
  setProgress(j.progress || 0, j.stage || "Memproses");
  $("#progressEta").textContent = j.eta_note ? j.eta_note : "";

  if(j.done){
    clearInterval(pollTimer);
    setProgress(100, "Selesai");
    $("#downloadBox").classList.remove("hidden");
    $("#downloadLink").href = j.zip_url;
    $("#expiryNote").textContent = `File akan dihapus otomatis pada ${j.expires_at_local}.`;
  }
}

document.addEventListener("DOMContentLoaded", ()=>{
  $("#startBtn").addEventListener("click", startJob);
});
