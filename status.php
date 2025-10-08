<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$job_id = $_GET['job_id'] ?? '';
if (!$job_id) { echo json_encode(['ok'=>false,'error'=>'job_id kosong']); exit; }

$job_dir = JOBS_DIR . "/$job_id";
$out_dir = OUTPUT_DIR . "/$job_id";
$log_path = LOGS_DIR . "/$job_id.log";
$meta_path = $job_dir . "/meta.json";
if (!is_dir($job_dir) || !file_exists($meta_path)) { echo json_encode(['ok'=>false,'error'=>'Job tidak ditemukan']); exit; }

$meta = json_decode(file_get_contents($meta_path), true) ?: [];
$stage = $meta['stage'] ?? 'unknown';
$progress = intval($meta['progress'] ?? 0);
$zip = $meta['zip'] ?? null;
$expires_at = intval($meta['expires_at'] ?? (time()+AUTO_DELETE_MINUTES*60));

// log delta: serve last ~1000 bytes tail to reduce traffic
$log_text = file_exists($log_path) ? file_get_contents($log_path) : "";
$lines = $log_text ? explode("\n", $log_text) : [];
$log_delta = array_slice($lines, max(0, count($lines)-50)); // last 50 lines

$expires_at_local = date("Y-m-d H:i:s", $expires_at);
$eta_note = null;
if ($stage !== 'done') {
  $secs_left = max(0, $expires_at - time());
  $eta_note = "File akan dihapus dalam ".(AUTO_DELETE_MINUTES)." menit setelah selesai.";
}

$data = [
  'ok'=>true,
  'stage'=>$stage,
  'progress'=>$progress,
  'log_delta'=>$log_delta,
  'done'=> ($stage === 'done'),
  'zip_url'=> ($stage === 'done' && $zip) ? ("download.php?job_id=" . urlencode($job_id)) : null,
  'expires_at_local'=>$expires_at_local,
  'eta_note'=>$eta_note
];

echo json_encode($data);
