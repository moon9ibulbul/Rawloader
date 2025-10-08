<?php
require_once __DIR__ . '/config.php';

$now = time();
$removed = 0;

foreach (glob(JOBS_DIR.'/*', GLOB_ONLYDIR) as $job_dir) {
  $meta_path = $job_dir . '/meta.json';
  if (!file_exists($meta_path)) continue;
  $meta = json_decode(file_get_contents($meta_path), true) ?: [];
  $exp = intval($meta['expires_at'] ?? 0);
  $done = ($meta['stage'] ?? '') === 'done';
  if ($done && $exp > 0 && $now >= $exp) {
    $job_id = basename($job_dir);
    // delete job dir, output dir, logs
    $out_dir = OUTPUT_DIR . "/$job_id";
    $log = LOGS_DIR . "/$job_id.log";
    $cmd = "rm -rf " . escapeshellarg($job_dir) . " " . escapeshellarg($out_dir) . " " . escapeshellarg($log);
    exec($cmd);
    $removed++;
  }
}

echo "Removed: $removed\n";
