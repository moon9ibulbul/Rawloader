#!/usr/bin/env python3
"""Downloader sederhana untuk sumber Bato berbasis konstanta `imgHttps`."""

import argparse
import json
import os
import re
import sys
import time
from pathlib import Path
from urllib.parse import urlparse
from urllib.request import Request, urlopen

USER_AGENT = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36"

def fetch_html(url: str) -> str:
    req = Request(url, headers={"User-Agent": USER_AGENT, "Referer": url})
    with urlopen(req) as resp:
        charset = resp.headers.get_content_charset() or "utf-8"
        data = resp.read()
    return data.decode(charset, errors="replace")


def extract_images(html: str):
    pattern = re.compile(r"const\s+imgHttps\s*=\s*(\[[^;]*\])", re.IGNORECASE | re.DOTALL)
    match = pattern.search(html)
    if not match:
        raise ValueError("Tidak menemukan konstanta imgHttps pada halaman")
    array_text = match.group(1)
    try:
        data = json.loads(array_text)
    except json.JSONDecodeError:
        normalized = array_text.replace("'", '"')
        data = json.loads(normalized)
    if not isinstance(data, list):
        raise ValueError("imgHttps bukan list")
    return [str(item) for item in data if isinstance(item, str) and item.strip()]


def sanitize_ext(url: str) -> str:
    path = urlparse(url).path
    ext = os.path.splitext(path)[1]
    if not ext:
        return ".jpg"
    return ext if ext.startswith('.') else f".{ext}"


def download_image(url: str, dest: Path, idx: int):
    ext = sanitize_ext(url)
    filename = f"img_{idx:04d}{ext}"
    target = dest / filename
    req = Request(url, headers={"User-Agent": USER_AGENT, "Referer": url})
    with urlopen(req) as resp:
        chunk = resp.read()
    target.write_bytes(chunk)
    return target


def main():
    parser = argparse.ArgumentParser(description="Downloader gambar Bato berbasis imgHttps")
    parser.add_argument("url", help="URL halaman Bato")
    parser.add_argument("output", help="Folder output untuk gambar mentah")
    parser.add_argument("--skip-env-setup", action="store_true", help="Kompatibilitas opsional, tidak melakukan apa pun")
    args = parser.parse_args()

    out_dir = Path(args.output)
    out_dir.mkdir(parents=True, exist_ok=True)

    print("[bato] Mengunduh halaman sumber...")
    try:
        html = fetch_html(args.url)
    except Exception as exc:
        print(f"[bato] ERROR saat mengambil halaman: {exc}")
        return 2

    print("[bato] Mengekstrak daftar gambar...")
    try:
        images = extract_images(html)
    except Exception as exc:
        print(f"[bato] ERROR saat parsing imgHttps: {exc}")
        return 3

    if not images:
        print("[bato] Tidak ada gambar ditemukan.")
        return 4

    print(f"[bato] Menemukan {len(images)} gambar. Mulai mengunduh...")
    start = time.time()
    for idx, url in enumerate(images, start=1):
        try:
            download_image(url, out_dir, idx)
            print(f"[bato] ({idx}/{len(images)}) selesai")
        except Exception as exc:
            print(f"[bato] Gagal mengunduh {url}: {exc}")
            return 5

    duration = time.time() - start
    print(f"[bato] Selesai mengunduh {len(images)} file dalam {duration:.2f} detik.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
