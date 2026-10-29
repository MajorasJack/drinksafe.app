#!/bin/sh
#
# Builds public/downloads/drink-safe-partner-pack.zip from the rendered print
# files and the Reel, and copies the individual PDFs into public/downloads, so
# /partners and the zip always hold the same files.
#
# Run it with `npm run partner-pack`, which renders the artwork first.
#
# The Reel and its share copy are not in git. They are read from
# brag-output-2026-10-09-133822/ unless a folder is given as the first
# argument or as PARTNER_PACK_MEDIA.

set -eu

root=$(cd "$(dirname "$0")/../.." && pwd)
media=${1:-${PARTNER_PACK_MEDIA:-$root/brag-output-2026-10-09-133822}}
output=$root/print/output
downloads=$root/public/downloads
zip=$downloads/drink-safe-partner-pack.zip

# 25MB, the largest attachment most email providers accept.
max_bytes=26214400

for file in "$media/brag.mp4" "$media/share-copy.txt"; do
    if [ ! -f "$file" ]; then
        echo "Missing $file." >&2
        echo "Pass the folder that holds brag.mp4 and share-copy.txt as the first argument, or set PARTNER_PACK_MEDIA." >&2
        exit 1
    fi
done

staging=$(mktemp -d)
trap 'rm -r "$staging"' EXIT

for piece in poster-a4 poster-a3 flyer sticker-a sticker-b about; do
    cp "$output/$piece.pdf" "$staging/drink-safe-$piece.pdf"
done

for piece in poster-a4 poster-a3 flyer sticker-a sticker-b; do
    cp "$output/$piece.pdf" "$downloads/drink-safe-$piece.pdf"
done

cp "$media/brag.mp4" "$staging/drink-safe-reel.mp4"
cp "$media/share-copy.txt" "$staging/share-copy.txt"
cp "$root/print/partner-pack/README.txt" "$staging/README.txt"

# A fixed date means the same files always produce the same zip.
touch -t 202601010000 "$staging"/*

rm -f "$zip"
(
    cd "$staging"
    zip -X -j -q "$zip" \
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

size=$(wc -c < "$zip" | tr -d ' ')

if [ "$size" -gt "$max_bytes" ]; then
    rm -f "$zip"
    echo "The partner pack came to $size bytes, over the 25MB limit. Leave the Reel out and link to it from /partners instead." >&2
    exit 1
fi

echo "Built $zip ($size bytes)."
