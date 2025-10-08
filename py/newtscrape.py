#!/usr/bin/env python3
import sys, re, os, html
from urllib.parse import urlparse
try:
    import requests
    from bs4 import BeautifulSoup
except Exception as e:
    print(f"[scraper] Missing deps: {e}")
    sys.exit(2)

UA = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"
TIMEOUT = 20
ALLOWED_IMG = re.compile(r"^https?://[\w.-]*toon\.[\w.-]+/.*\.(?:jpe?g|png|webp)$", re.I)
IMG_EXT = re.compile(r"\.(jpe?g|png|webp)$", re.I)

if len(sys.argv) < 3:
    print("Usage: newtscrape.py <episode_url> <output_dir>")
    sys.exit(1)

url = sys.argv[1]
output_root = sys.argv[2]

sess = requests.Session()
sess.headers.update({"User-Agent": UA, "Accept-Language": "en-US,en;q=0.9"})

print("🌐 Fetching page...")
r = sess.get(url, timeout=TIMEOUT)
r.raise_for_status()
html_text = r.text
soup = BeautifulSoup(html_text, "html.parser")

title_text = soup.title.string.strip() if soup.title and soup.title.string else "newtoki"
title_text = re.sub(r"[\\/:*?\"<>|]", "_", title_text)[:100]
base_dir = os.path.join(output_root, title_text)
os.makedirs(base_dir, exist_ok=True)

candidates = []
for img in soup.find_all("img"):
    src = img.get("src") or ""
    if not src:
        continue
    src = html.unescape(src)
    if ALLOWED_IMG.search(src) or IMG_EXT.search(src):
        candidates.append(src)

seen = set()
urls = []
for u in candidates:
    if u in seen:
        continue
    seen.add(u)
    urls.append(u)

if not urls:
    print("[scraper] No images found.")
    sys.exit(3)

print("==================================================")
print("🚀 Starting NewToki scrape & download...")
print(f"📁 Output: {base_dir}")
print(f"🖼️  Images found: {len(urls)}")

ok = 0
for idx, u in enumerate(urls, 1):
    m = IMG_EXT.search(u)
    ext = '.' + (m.group(1).lower() if m else 'jpg')
    name = f"{idx:04d}{ext}"
    outp = os.path.join(base_dir, name)
    try:
        with sess.get(u, timeout=TIMEOUT, stream=True) as resp:
            resp.raise_for_status()
            with open(outp, 'wb') as f:
                for chunk in resp.iter_content(chunk_size=1<<15):
                    if chunk:
                        f.write(chunk)
        size = os.path.getsize(outp)
        ok += 1
        print(f"✅ Downloaded: {name} ({size:,} bytes)")
    except Exception as e:
        print(f"⚠️  Skip {u} -> {e}")

print("\n📊 Download Statistics:")
print(f"✅ Successfully downloaded: {ok}")
print(f"⏭️  Skipped (already exists): 0")
print(f"❌ Failed downloads: {len(urls)-ok}")
print(f"📁 Total processed: {len(urls)}")
print("🎉 FINISHED!")
