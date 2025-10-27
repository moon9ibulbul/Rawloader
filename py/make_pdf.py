#!/usr/bin/env python3
import argparse
import os
import sys
from typing import List

try:
    from PIL import Image
except ImportError as exc:
    print(f"Pillow is required to build PDF: {exc}", file=sys.stderr)
    sys.exit(1)


try:
    RESAMPLE_LANCZOS = Image.Resampling.LANCZOS  # Pillow >= 9.1
except AttributeError:  # pragma: no cover - fallback for older Pillow
    RESAMPLE_LANCZOS = Image.LANCZOS


QUALITY_SCALE = {
    "high": 1.0,
    "medium": 0.8,
    "low": 0.6,
}


def read_list(path: str) -> List[str]:
    items: List[str] = []
    with open(path, "r", encoding="utf-8") as handle:
        for line in handle:
            item = line.strip()
            if not item:
                continue
            items.append(item)
    return items


def load_image(path: str, scale: float) -> Image.Image | None:
    try:
        img = Image.open(path)
        img.load()
        if img.mode not in ("RGB", "L"):
            img = img.convert("RGB")
        elif img.mode == "L":
            img = img.convert("RGB")
        if scale != 1.0:
            new_width = max(1, int(img.width * scale))
            new_height = max(1, int(img.height * scale))
            img = img.resize((new_width, new_height), RESAMPLE_LANCZOS)
        return img
    except Exception as exc:
        print(f"[make_pdf] Skip {path}: {exc}", file=sys.stderr)
        return None


def main() -> int:
    parser = argparse.ArgumentParser(description="Bundle images into a single PDF")
    parser.add_argument("--list", required=True, help="Path to text file containing image paths")
    parser.add_argument("--output", required=True, help="Destination PDF path")
    parser.add_argument("--quality", choices=QUALITY_SCALE.keys(), default="high", help="Quality preset")
    args = parser.parse_args()

    image_paths = read_list(args.list)
    if not image_paths:
        print("[make_pdf] No images provided", file=sys.stderr)
        return 1

    scale = QUALITY_SCALE.get(args.quality, 1.0)

    images: List[Image.Image] = []
    try:
        for path in image_paths:
            if not os.path.isfile(path):
                print(f"[make_pdf] Missing file: {path}", file=sys.stderr)
                continue
            img = load_image(path, scale)
            if img is not None:
                images.append(img)

        if not images:
            print("[make_pdf] No valid images to include", file=sys.stderr)
            return 1

        first, *rest = images
        first.save(args.output, format="PDF", save_all=True, append_images=rest)
        print(f"[make_pdf] PDF created at {args.output} with {len(images)} page(s)")
        return 0
    finally:
        for img in images:
            try:
                img.close()
            except Exception:
                pass


if __name__ == "__main__":
    sys.exit(main())