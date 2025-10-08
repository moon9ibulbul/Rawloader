# Webtoon Downloader + Stitch (PHP + JS + Python)

This app provides a modern UI to run your Python downloader (`nava.py`) and stitcher (`main.py` + `SmartStitchCore.py`), track progress, and serve a ZIP with results. It also auto-deletes results after 15 minutes and rate-limits new jobs (60s cooldown).

## Deploy

1. Upload the whole folder to your PHP-capable server (PHP 8+ recommended).
2. Put your Python scripts into `py/`:
   - `nava.py` (downloader)
   - `main.py` (stitch driver that imports `SmartStitchCore.py`)
   - `SmartStitchCore.py`

3. Edit `config.php` if needed:
   - `PYTHON_BIN` — path to Python (e.g., `/usr/bin/python3`)
   - `SCRIPTS_DIR` — path to the scripts (default `__DIR__/py`)
   - Cooldown and auto-delete windows.

4. Ensure PHP can execute shell commands and the webserver user has permissions to write into `jobs/`, `outputs/`, `logs/`.

5. (Optional) Set up a cron to cleanup expired artifacts:
   ```
   * * * * * php /path/to/cleanup.php >/dev/null 2>&1
   ```

## How it Works

- `index.php`: form and progress UI
- `process.php`: validates inputs, enforces 60s cooldown, creates a job, spawns `runner.php` in background
- `runner.php`: runs `nava.py` then `main.py`, zips results, updates meta & logs
- `status.php`: polled by front-end for progress and logs
- `download.php`: streams the ZIP for a finished job
- `cleanup.php`: removes expired jobs/outputs/logs

## Notes

- `split_height` is **capped** to 24,000 px server-side.
- Output format is allow-listed to common raster formats.
- Progress is heuristic (based on stages/log prints), but clear and responsive.
- If `nava.py` creates per-episode folders, `main.py` will process them (input points to the root `raw/`). Adjust `main.py` to your exact layout if needed.

Enjoy!
