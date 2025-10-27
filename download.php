<?php
require_once __DIR__ . '/config.php';
$job_id = $_GET['job_id'] ?? '';
if (!$job_id) { http_response_code(400); echo "job_id?"; exit; }

$out_dir = OUTPUT_DIR . "/$job_id";
$meta_path = JOBS_DIR . "/$job_id/meta.json";
if (!file_exists($meta_path)) { http_response_code(404); echo "Job tidak ditemukan"; exit; }
$meta = json_decode(file_get_contents($meta_path), true) ?: [];
if (($meta['stage'] ?? '') !== 'done' || empty($meta['zip'])) { http_response_code(409); echo "Belum siap"; exit; }

$package_path = $out_dir . "/" . $meta['zip'];
if (!file_exists($package_path)) { http_response_code(404); echo "Berkas tidak ada"; exit; }

$package_type = $meta['package_type'] ?? null;
if (!$package_type) {
  $package_type = str_ends_with(strtolower($package_path), '.pdf') ? 'pdf' : 'zip';
}

$mime = $package_type === 'pdf' ? 'application/pdf' : 'application/zip';
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="'.basename($package_path).'"');
header('Content-Length: ' . filesize($package_path));
readfile($package_path);
