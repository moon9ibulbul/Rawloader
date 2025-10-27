<?php
require_once __DIR__ . '/config.php';

$job_id = $argv[1] ?? null; if(!$job_id) exit(1);
$job_dir = JOBS_DIR . "/$job_id";
$out_dir = OUTPUT_DIR . "/$job_id";
$log_path = LOGS_DIR . "/$job_id.log";
$meta_path = $job_dir . "/meta.json";

function update_meta($arr){ global $meta_path; $cur=json_decode(@file_get_contents($meta_path),true)?:[]; $new=array_merge($cur,$arr); file_put_contents($meta_path,json_encode($new,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); }
function logln($s){ global $log_path; file_put_contents($log_path, rtrim($s)."\n", FILE_APPEND); }

update_meta(['stage'=>'starting','progress'=>2]);
logln("[runner-xtoon] Job $job_id mulai.");

// ambil payload
$envelope = __DIR__ . "/last_payload_{$job_id}.json"; $payload=[];
if(file_exists($envelope)){ $payload=json_decode(file_get_contents($envelope),true)?:[]; @unlink($envelope); }

$url = $payload['url'] ?? '';
$split_height = (int)($payload['split_height'] ?? 7500);
$output_type  = $payload['output_files_type'] ?? '.png';
$unit_images  = (int)($payload['unit_images'] ?? 20);
$width_enforce= (int)($payload['width_enforce_type'] ?? 0);
$custom_width = (int)($payload['custom_width'] ?? 720);
$senstivity   = (int)($payload['senstivity'] ?? 90);
$ignorable_px = (int)($payload['ignorable_pixels'] ?? 0);
$scan_step    = (int)($payload['scan_line_step'] ?? 5);
$low_ram      = (int)($payload['low_ram'] ?? 1) === 1 ? 1 : 0;

// ====== Stage 1: scrape & download ======
$raw_dir = $out_dir . "/raw"; if(!is_dir($raw_dir)) mkdir($raw_dir,0775,true);

$cmd1 = escapeshellcmd(PYTHON_BIN) . ' -u ' . escapeshellarg(SCRIPTS_DIR . '/newtscrape.py') . ' ' . escapeshellarg($url) . ' ' . escapeshellarg($raw_dir);
update_meta(['stage'=>'download','progress'=>5]);
logln("[runner-xtoon] Menjalankan scraper..."); logln($cmd1);

$rc1 = 0;
$proc = popen($cmd1 . " 2>&1", "r");
if ($proc) {
  while (!feof($proc)) {
    $line = fgets($proc); if ($line === false) break;
    logln(rtrim($line));
    $cur = (int)((json_decode(@file_get_contents($meta_path),true)['progress'] ?? 5));
    if ($cur < 60) update_meta(['progress'=>$cur+1]);
  }
  $rc1 = pclose($proc);
} else {
  logln('[runner-xtoon] ERROR: gagal jalanin scraper');
  update_meta(['stage'=>'error','progress'=>100,'error'=>'Scraper failed to start']);
  exit(1);
}

// ====== Pilih folder input berisi gambar (0-2 level) ======
function dir_has_images($d){ if(!is_dir($d)) return false; foreach(['jpg','jpeg','png','webp','bmp','tiff','tga','JPG','JPEG','PNG','WEBP'] as $e){ if(glob($d.'/*.'. $e)) return true; } return false; }
function pick_image_dir($base){ if(dir_has_images($base)) return $base; $lvl1=array_filter(glob($base.'/*'),'is_dir'); foreach($lvl1 as $d1){ if(dir_has_images($d1)) return $d1; $lvl2=array_filter(glob($d1.'/*'),'is_dir'); foreach($lvl2 as $d2){ if(dir_has_images($d2)) return $d2; } } return null; }
$input_for_stitch = pick_image_dir($raw_dir);
$subs = array_filter(glob($raw_dir.'/*'),'is_dir'); if($subs){ $names=[]; foreach($subs as $sd){ $names[]=basename($sd);} logln('[runner-xtoon] raw subdirs: '.implode(', ',$names)); }
if(!$input_for_stitch){ logln('[runner-xtoon] ERROR: No image files found after scrape'); update_meta(['stage'=>'error','progress'=>100,'error'=>'No images found']); exit(1); }
logln('[runner-xtoon] Stitch input: '.$input_for_stitch);

// ====== Stage 2: stitch ======
$cmd2 = escapeshellcmd(PYTHON_BIN) . ' -u ' . escapeshellarg(SCRIPTS_DIR . '/main.py') . ' ' .
        ' --input_folder ' . escapeshellarg($input_for_stitch) .
        ' --split_height ' . escapeshellarg($split_height) .
        ' --output_files_type ' . escapeshellarg($output_type) .
        ($low_ram ? ' --low_ram ' : '') .
        ' --unit_images ' . escapeshellarg($unit_images) .
        ' --width_enforce_type ' . escapeshellarg($width_enforce) .
        ' --custom_width ' . escapeshellarg($custom_width) .
        ' --senstivity ' . escapeshellarg($senstivity) .
        ' --ignorable_pixels ' . escapeshellarg($ignorable_px) .
        ' --scan_line_step ' . escapeshellarg($scan_step);
$cmd2 .= ' --cleanup-input';
        
update_meta(['stage'=>'stitch','progress'=>65]);
logln('[runner-xtoon] Menjalankan stitcher...'); logln($cmd2);

$rc2 = 0;
$proc2 = popen($cmd2 . " 2>&1", "r");
if ($proc2) {
  while (!feof($proc2)) {
    $line = fgets($proc2); if ($line === false) break;
    logln(rtrim($line));
    $cur = (int)((json_decode(@file_get_contents($meta_path),true)['progress'] ?? 65));
    if ($cur < 95) update_meta(['progress'=>$cur+1]);
  }
  $rc2 = pclose($proc2);
} else {
  logln('[runner-xtoon] ERROR: gagal jalanin stitcher');
  update_meta(['stage'=>'error','progress'=>100,'error'=>'Stitcher failed to start']);
  exit(1);
}

if ($rc2 !== 0) {
  logln('[runner-xtoon] ERROR: stitcher exit code '.$rc2);
  update_meta(['stage'=>'error','progress'=>100,'error'=>'Stitcher failed']);
  exit(1);
}

// ====== Stage 3: ZIP ======
update_meta(['stage'=>'zipping','progress'=>96]); logln('[runner-toki] Mengemas hasil menjadi ZIP...');
$zip_name = "job_$job_id.zip";
$zip_path = $out_dir . "/$zip_name";

$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out_dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY);
  foreach ($it as $file) {
    if ($file->isDir()) continue;
    $fp = $file->getRealPath();
    if (basename($fp) === $zip_name) continue;
    $ln = substr($fp, strlen($out_dir) + 1);
    $zip->addFile($fp, $ln);
  }
  $zip->close();
}

update_meta(['stage'=>'done','progress'=>100,'zip'=>basename($zip_path),'package_type'=>'zip']);
logln('[runner-xtoon] Selesai. ZIP: '.basename($zip_path));
