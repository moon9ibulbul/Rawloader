<?php
require_once __DIR__ . '/config.php';

$job_id = $argv[1] ?? null;
if (!$job_id) exit(1);

$job_dir = JOBS_DIR . "/$job_id";
$out_dir = OUTPUT_DIR . "/$job_id";
$log_path = LOGS_DIR . "/$job_id.log";
$meta_path = $job_dir . "/meta.json";

function update_meta($arr) {
  global $meta_path;
  $cur = json_decode(@file_get_contents($meta_path), true) ?: [];
  $new = array_merge($cur, $arr);
  file_put_contents($meta_path, json_encode($new, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}

function logln($s) {
  global $log_path;
  $line = $s . (str_ends_with($s, "\n") ? '' : "\n");
  file_put_contents($log_path, $line, FILE_APPEND);
}

update_meta(['stage'=>'starting','progress'=>2]);
logln("[runner-bato] Job $job_id mulai.");

$envelope = __DIR__ . "/last_payload_$job_id.json";
$payload = [];
if (file_exists($envelope)) {
  $payload = json_decode(file_get_contents($envelope), true) ?: [];
  @unlink($envelope);
}

$source_url       = trim($payload['source_url'] ?? '');
$split_height     = min(MAX_SPLIT_HEIGHT, max(100, intval($payload['split_height'] ?? 7500)));
$output_type      = $payload['output_files_type'] ?? '.png';
$width_enforce    = intval($payload['width_enforce_type'] ?? 0);
$custom_width     = intval($payload['custom_width'] ?? 720);
$senstivity       = intval($payload['senstivity'] ?? 90);
$ignorable_px     = intval($payload['ignorable_pixels'] ?? 0);
$scan_step        = intval($payload['scan_line_step'] ?? 5);
$batch_mode       = intval($payload['batch_mode'] ?? 0) === 1 ? 1 : 0;
$low_ram          = intval($payload['low_ram'] ?? 0) === 1 ? 1 : 0;
$unit_images      = intval($payload['unit_images'] ?? 20);

if ($source_url === '') {
  logln('[runner-bato] ERROR: URL kosong.');
  update_meta(['stage'=>'error','progress'=>100,'error'=>'Source URL empty']);
  exit(1);
}

$raw_dir = $out_dir . "/raw";
if (!is_dir($raw_dir)) { mkdir($raw_dir, 0775, true); }

$cmd1 = escapeshellcmd(PYTHON_BIN) . ' -u ' . escapeshellarg(SCRIPTS_DIR . '/bato.py') . ' '
      . escapeshellarg($source_url) . ' '
      . escapeshellarg($raw_dir)
      . ' --skip-env-setup';

update_meta(['stage'=>'download','progress'=>5]);
logln('[runner-bato] Menjalankan scraper...');
logln($cmd1);

$rc1 = 0;
$proc = popen($cmd1 . " 2>&1", "r");
if ($proc) {
  while (!feof($proc)) {
    $line = fgets($proc);
    if ($line === false) break;
    logln(rtrim($line));
    $cur = intval((json_decode(@file_get_contents($meta_path), true)['progress'] ?? 5));
    if ($cur < 60) update_meta(['progress'=>$cur+1]);
  }
  $rc1 = pclose($proc);
} else {
  logln('[runner-bato] ERROR: gagal menjalankan scraper (popen).');
  update_meta(['stage'=>'error','progress'=>100,'error'=>'Scraper failed to start']);
  exit(1);
}

if ($rc1 !== 0) {
  logln("[runner-bato] ERROR: scraper exit code $rc1");
  update_meta(['stage'=>'error','progress'=>100,'error'=>'Scraper failed']);
  exit(1);
}

function dir_has_images($dir) {
  if (!is_dir($dir)) return false;
  $exts = array('jpg','jpeg','png','webp','bmp','tiff','tga','JPG','JPEG','PNG','WEBP','BMP','TIFF','TGA');
  foreach ($exts as $e) {
    $found = glob($dir . '/*.' . $e);
    if ($found && count($found) > 0) return true;
  }
  return false;
}

function pick_image_dir($base) {
  if (dir_has_images($base)) return $base;
  $subs1 = array_filter(glob($base . '/*'), 'is_dir');
  foreach ($subs1 as $d1) {
    if (dir_has_images($d1)) return $d1;
    $subs2 = array_filter(glob($d1 . '/*'), 'is_dir');
    foreach ($subs2 as $d2) {
      if (dir_has_images($d2)) return $d2;
    }
  }
  return null;
}

$input_for_stitch = pick_image_dir($raw_dir);
if (!$input_for_stitch) {
  logln('[runner-bato] ERROR: No image files found after scraping.');
  update_meta(['stage'=>'error','progress'=>100,'error'=>'No images found after scrape']);
  exit(1);
}

logln('[runner-bato] Stitch input: ' . $input_for_stitch);

$stitched_dir = $out_dir . "/stitched";
if (!is_dir($stitched_dir)) { mkdir($stitched_dir, 0775, true); }

$cmd2 = escapeshellcmd(PYTHON_BIN) . ' -u ' . escapeshellarg(SCRIPTS_DIR . '/main.py') . ' '
       . ' --input_folder ' . escapeshellarg($input_for_stitch)
       . ' --split_height ' . escapeshellarg($split_height)
       . ' --output_files_type ' . escapeshellarg($output_type)
       . ' --unit_images ' . escapeshellarg($unit_images)
       . ' --width_enforce_type ' . escapeshellarg($width_enforce)
       . ' --custom_width ' . escapeshellarg($custom_width)
       . ' --senstivity ' . escapeshellarg($senstivity)
       . ' --ignorable_pixels ' . escapeshellarg($ignorable_px)
       . ' --scan_line_step ' . escapeshellarg($scan_step)
       . ' --cleanup-input';

if ($batch_mode) { $cmd2 .= ' --batch_mode'; }
if ($low_ram)   { $cmd2 .= ' --low_ram'; }

update_meta(['stage'=>'stitch','progress'=>65]);
logln('[runner-bato] Menjalankan stitcher...');
logln($cmd2);

$rc2 = 0;
$proc2 = popen($cmd2 . " 2>&1", "r");
if ($proc2) {
  while (!feof($proc2)) {
    $line = fgets($proc2);
    if ($line === false) break;
    logln(rtrim($line));
    $cur = intval((json_decode(@file_get_contents($meta_path), true)['progress'] ?? 65));
    if ($cur < 95) update_meta(['progress'=>$cur+1]);
  }
  $rc2 = pclose($proc2);
} else {
  logln('[runner-bato] ERROR: gagal menjalankan stitcher (popen).');
  update_meta(['stage'=>'error','progress'=>100,'error'=>'Stitcher failed to start']);
  exit(1);
}

if ($rc2 !== 0) {
  logln("[runner-bato] ERROR: stitcher exit code $rc2");
  update_meta(['stage'=>'error','progress'=>100,'error'=>'Stitcher failed']);
  exit(1);
}

update_meta(['stage'=>'zipping','progress'=>96]);
logln('[runner-bato] Mengemas hasil menjadi ZIP...');

$zip_name = "job_$job_id.zip";
$zip_path = $out_dir . "/$zip_name";

$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($out_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
  );
  foreach ($it as $file) {
    if ($file->isDir()) continue;
    $filepath = $file->getRealPath();
    if (basename($filepath) === $zip_name) continue;
    $localname = substr($filepath, strlen($out_dir) + 1);
    $zip->addFile($filepath, $localname);
  }
  $zip->close();
}

update_meta(['stage'=>'done','progress'=>100,'zip'=>basename($zip_path)]);
logln('[runner-bato] Selesai. ZIP: ' . basename($zip_path));
