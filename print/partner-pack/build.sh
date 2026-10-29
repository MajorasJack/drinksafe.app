#!/bin/sh
#
# Builds public/downloads/drink-safe-partner-pack.zip from the rendered print
# files and the Reel, and copies the individual PDFs into public/downloads, so
# /partners and the zip always hold the same files.
#
# Run it with `npm run partner-pack`. It packs the PDFs already in print/output
# and does not re-render them, so run `npm run print:render` first if the
# artwork has changed. With the same inputs, it always builds the same zip.
#
# The Reel is not in git. It is read from brag-output-2026-10-09-133822/ unless
# a folder is given as the first argument or as PARTNER_PACK_MEDIA.

set -eu

root=$(cd "$(dirname "$0")/../.." && pwd)
media=${1:-${PARTNER_PACK_MEDIA:-$root/brag-output-2026-10-09-133822}}
output=$root/print/output
downloads=$root/public/downloads
zip=$downloads/drink-safe-partner-pack.zip

# 25MB, the largest attachment most email providers accept.
max_bytes=26214400

if ! command -v zip >/dev/null; then
    echo "zip is not installed." >&2
    exit 1
fi

if [ ! -f "$media/brag.mp4" ]; then
    echo "Missing $media/brag.mp4." >&2
    echo "Pass the folder that holds brag.mp4 as the first argument, or set PARTNER_PACK_MEDIA." >&2
    exit 1
fi

staging=$(mktemp -d)
trap 'rm -rf "$staging"' EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

for piece in poster-a4 poster-a3 flyer sticker-a sticker-b about; do
    cp "$output/$piece.pdf" "$staging/drink-safe-$piece.pdf"
done

cp "$media/brag.mp4" "$staging/drink-safe-reel.mp4"
cp "$root/print/partner-pack/share-copy.txt" "$staging/share-copy.txt"
cp "$root/print/partner-pack/README.txt" "$staging/README.txt"

# Fixed permissions and a fixed date mean the same files always produce the
# same zip, whatever the umask or the time it was built.
chmod 644 "$staging"/*
touch -t 202601010000 "$staging"/*

(
    cd "$staging"
    zip -X -j -q pack.zip \
        README.txt \
        drink-safe-about.pdf \
        drink-safe-poster-a4.pdf \
        drink-safe-poster-a3.pdf \
        drink-safe-flyer.pdf \
        drink-safe-sticker-a.pdf \
        drink-safe-sticker-b.pdf \
        drink-safe-reel.mp4 \
        share-copy.txt
)

size=$(wc -c < "$staging/pack.zip" | tr -d ' ')

if [ "$size" -gt "$max_bytes" ]; then
    echo "The partner pack came to $size bytes, over the 25MB limit. Leave the Reel out and link to it from /partners instead." >&2
    exit 1
fi

mv "$staging/pack.zip" "$zip"

for piece in poster-a4 poster-a3 flyer sticker-a sticker-b; do
    cp "$output/$piece.pdf" "$downloads/drink-safe-$piece.pdf"
done

echo "Built $zip ($size bytes)."
