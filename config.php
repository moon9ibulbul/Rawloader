<?php
// =============== Basic Configuration ===============

// IMPORTANT: adjust this if your Python path is different
// SALAH (terlihat dari log): '/venv/bin/python3'
define('PYTHON_BIN', __DIR__ . '/venv/bin/python3');  // <- yang benar


// Absolute or relative path to your python scripts on the server
// If your web root is, e.g., /var/www/html and you upload scripts there:
define('SCRIPTS_DIR', __DIR__ . '/py'); // we will create ./py and place placeholders

// Where to keep jobs, logs, and outputs (must be writable by PHP)
define('JOBS_DIR', __DIR__ . '/jobs');       // job metadata
define('OUTPUT_DIR', __DIR__ . '/outputs');  // stitched images and zips
define('LOGS_DIR', __DIR__ . '/logs');       // progress logs

// Auto-delete finished artifacts after N minutes
define('AUTO_DELETE_MINUTES', 15);

// Simple per-IP cooldown in seconds
define('COOLDOWN_SECONDS', 60);

// Maximum allowed split height (hard cap)
define('MAX_SPLIT_HEIGHT', 24000);

// Security: basic allow-list for image output formats
$ALLOWED_FORMATS = ['.png', '.jpg', '.webp', '.bmp', '.tiff', '.tga'];

// Ensure required folders exist
foreach ([JOBS_DIR, OUTPUT_DIR, LOGS_DIR] as $d) {
  if (!is_dir($d)) { mkdir($d, 0775, true); }
}
?>
