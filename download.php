<?php
require_once __DIR__ . '/config.php';
$job_id = $_GET['job_id'] ?? '';
if (!$job_id) { http_response_code(400); echo "job_id?"; exit; }

$out_dir = OUTPUT_DIR . "/$job_id";
$meta_path = JOBS_DIR . "/$job_id/meta.json";
if (!file_exists($meta_path)) { http_response_code(404); echo "Job tidak ditemukan"; exit; }
$meta = json_decode(file_get_contents($meta_path), true) ?: [];
if (($meta['stage'] ?? '') !== 'done' || empty($meta['zip'])) { http_response_code(409); echo "Belum siap"; exit; }

$zip_path = $out_dir . "/" . $meta['zip'];
if (!file_exists($zip_path)) { http_response_code(404); echo "ZIP tidak ada"; exit; }

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="'.basename($zip_path).'"');
header('Content-Length: ' . filesize($zip_path));
readfile($zip_path);
