<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

try {
  // Cooldown per IP (opsional) — samakan dengan nava kalau kamu punya utilnya.
  $cooldownSeconds = defined('COOLDOWN_SECONDS') ? COOLDOWN_SECONDS : 60;

  $url = trim($_POST['url'] ?? '');
  if (!$url || !preg_match('~^https?://~i', $url)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'URL tidak valid']);
    exit;
  }

  $job_id = bin2hex(random_bytes(8));
  $job_dir = JOBS_DIR . "/$job_id";
  $out_dir = OUTPUT_DIR . "/$job_id";
  if (!is_dir($job_dir)) mkdir($job_dir, 0775, true);
  if (!is_dir($out_dir)) mkdir($out_dir, 0775, true);

  $split_height = (int)($_POST['split_height'] ?? 7500);
  if ($split_height < 100) $split_height = 100;
  if (defined('MAX_SPLIT_HEIGHT') && $split_height > MAX_SPLIT_HEIGHT) $split_height = MAX_SPLIT_HEIGHT;

  $payload = [
    'mode' => 'toki',
    'url'  => $url,
    'split_height' => $split_height,
    'output_files_type' => $_POST['output_files_type'] ?? '.png',
    'unit_images'       => (int)($_POST['unit_images'] ?? 20),
    'width_enforce_type'=> (int)($_POST['width_enforce_type'] ?? 0),
    'custom_width'      => (int)($_POST['custom_width'] ?? 720),
    'senstivity'        => (int)($_POST['senstivity'] ?? 90),
    'ignorable_pixels'  => (int)($_POST['ignorable_pixels'] ?? 0),
    'scan_line_step'    => (int)($_POST['scan_line_step'] ?? 5),
    'low_ram'           => (int)($_POST['low_ram'] ?? 0),
  ];

  file_put_contents(__DIR__ . "/last_payload_{$job_id}.json", json_encode($payload));
  file_put_contents($job_dir . '/meta.json', json_encode(['stage'=>'queued','progress'=>1], JSON_PRETTY_PRINT));

  // Spawn runner
  $php_cli = '/usr/bin/php';
  if (!is_executable($php_cli)) {
    $maybe = trim(@shell_exec('command -v php'));
    if ($maybe) $php_cli = $maybe;
  }
  $boot_log = LOGS_DIR . "/{$job_id}.boot.log";
  $sh_cmd = $php_cli . ' ' . escapeshellarg(__DIR__.'/runner_toki.php') . ' ' . escapeshellarg($job_id) .
            ' > ' . escapeshellarg($boot_log) . ' 2>&1 &';
  $cmd = '/bin/sh -c ' . escapeshellarg($sh_cmd);
  $rc = 1; @exec($cmd, $o, $rc);
  if ($rc !== 0) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Gagal menjalankan runner (spawn)']);
    exit;
  }

  echo json_encode(['ok'=>true,'job_id'=>$job_id,'cooldown'=>$cooldownSeconds]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
