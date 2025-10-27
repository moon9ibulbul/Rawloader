<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

function ip_key() {
  return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function cooldown_ok(&$wait_left) {
  $key = ip_key();
  $path = JOBS_DIR . "/cooldown_" . md5($key) . ".json";
  $now = time();
  if (file_exists($path)) {
    $data = json_decode(file_get_contents($path), true) ?: [];
    $last = intval($data['last'] ?? 0);
    $left = COOLDOWN_SECONDS - ($now - $last);
    if ($left > 0) { $wait_left = $left; return false; }
  }
  file_put_contents($path, json_encode(['last'=>$now]));
  return true;
}

try {
  $chapter_url = trim($_POST['chapter_url'] ?? '');
  if ($chapter_url === '' || !preg_match('~^https?://~i', $chapter_url)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'URL chapter tidak valid.']);
    exit;
  }

  $split_height = intval($_POST['split_height'] ?? 7500);
  if ($split_height > MAX_SPLIT_HEIGHT) $split_height = MAX_SPLIT_HEIGHT;
  if ($split_height < 100) $split_height = 100;

  $output_files_type = $_POST['output_files_type'] ?? '.png';
  global $ALLOWED_FORMATS;
  if (!in_array($output_files_type, $ALLOWED_FORMATS, true)) $output_files_type = '.png';

  $width_enforce_type = intval($_POST['width_enforce_type'] ?? 0);
  if (!in_array($width_enforce_type, [0,1,2], true)) $width_enforce_type = 0;

  $custom_width = intval($_POST['custom_width'] ?? 720);
  if ($custom_width < 100) $custom_width = 100;

  $senstivity = intval($_POST['senstivity'] ?? 90);
  if ($senstivity < 0) $senstivity = 0;
  if ($senstivity > 100) $senstivity = 100;

  $ignorable_pixels = intval($_POST['ignorable_pixels'] ?? 0);
  if ($ignorable_pixels < 0) $ignorable_pixels = 0;

  $scan_line_step = intval($_POST['scan_line_step'] ?? 5);
  if ($scan_line_step < 1) $scan_line_step = 1;
  if ($scan_line_step > 20) $scan_line_step = 20;

  $low_ram = intval($_POST['low_ram'] ?? 0) === 1 ? 1 : 0;
  $unit_images = intval($_POST['unit_images'] ?? 20);
  if ($unit_images < 2) $unit_images = 2;
  if ($unit_images > 50) $unit_images = 50;

  $package_content = $_POST['package_content'] ?? 'stitched';
  $allowed_packages = ['stitched','raw','both'];
  if (!in_array($package_content, $allowed_packages, true)) $package_content = 'stitched';

  $wait_left = 0;
  if (!cooldown_ok($wait_left)) {
    http_response_code(429);
    echo json_encode(['ok'=>false,'error'=>"Terlalu sering. Coba lagi dalam {$wait_left}s."]);
    exit;
  }

  $job_id = bin2hex(random_bytes(8));
  $job_dir = JOBS_DIR . "/$job_id";
  $out_dir = OUTPUT_DIR . "/$job_id";
  $meta_path = $job_dir . "/meta.json";
  mkdir($job_dir, 0775, true);
  mkdir($out_dir, 0775, true);

  $meta = [
    'job_id'=>$job_id,
    'created_at'=>time(),
    'expires_at'=>time() + (AUTO_DELETE_MINUTES*60),
    'stage'=>'queued',
    'progress'=>1,
    'zip'=>null,
    'package_type'=>'zip'
  ];
  file_put_contents($meta_path, json_encode($meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

  $payload = [
    'chapter_url'=>$chapter_url,
    'split_height'=>$split_height,
    'output_files_type'=>$output_files_type,
    'width_enforce_type'=>$width_enforce_type,
    'custom_width'=>$custom_width,
    'senstivity'=>$senstivity,
    'ignorable_pixels'=>$ignorable_pixels,
    'scan_line_step'=>$scan_line_step,
    'low_ram'=>$low_ram,
    'unit_images'=>$unit_images,
    'package_content'=>$package_content
  ];

  $envelope = __DIR__ . '/last_payload_' . $job_id . '.json';
  file_put_contents($envelope, json_encode($payload, JSON_UNESCAPED_SLASHES));

  $php_cli = trim(shell_exec('command -v php')) ?: '/usr/bin/php';
  $boot_log = LOGS_DIR . "/{$job_id}.boot.log";

  $sh_cmd = $php_cli.' '.escapeshellarg(__DIR__.'/runner_mangago.php').' '.escapeshellarg($job_id)
          .' > '.escapeshellarg($boot_log).' 2>&1 &';
  $cmd = '/bin/sh -c '.escapeshellarg($sh_cmd);

  $rc = 1;
  @exec($cmd, $out, $rc);
  if ($rc !== 0) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>"Gagal spawn runner (rc=$rc). Cek izin folder logs/ & open_basedir."]);
    exit;
  }

  echo json_encode(['ok'=>true,'job_id'=>$job_id,'cooldown'=>COOLDOWN_SECONDS]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
