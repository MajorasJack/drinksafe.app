/**
 * Renders the print artwork to PDF at physical size, plus PNG proofs for a
 * quick look on screen.
 *
 * Chromium honours the @page size in each file, so nothing is scaled and the
 * PDFs come out at trim plus 3mm bleed. The poster is the exception: it has no
 * bleed, and its A3 is the A4 page scaled up by the A-series ratio.
 *
 * Pass a partner code to render only the A4 poster with that partner's QR, as a
 * PDF with no proof:
 *
 *     node print/artwork/render.mjs uom   ->   print/output/poster-a4-uom.pdf
 */

import { access, mkdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const here = dirname(fileURLToPath(import.meta.url));
const out = join(here, '..', 'output');

const partnerCode = process.argv[2];

// The code becomes part of a file name, so anything else could escape print/output.
if (partnerCode !== undefined && !/^[a-z0-9-]+$/.test(partnerCode)) {
    console.error(`Partner code "${partnerCode}" may only use lowercase letters, digits and hyphens.`);
    process.exit(1);
}

const partnerQr = partnerCode && join(here, '..', 'qr', 'partners', `${partnerCode}.svg`);

if (partnerQr) {
    await access(partnerQr).catch(() => {
        console.error(`No QR for "${partnerCode}" at ${partnerQr}. Generate it first.`);
        process.exit(1);
    });
}

const allPieces = [
    { file: 'sticker-a.html', name: 'sticker-a', width: 106.2, height: 106.2 },
    { file: 'sticker-b.html', name: 'sticker-b', width: 106.2, height: 106.2 },
    { file: 'flyer.html', name: 'flyer', width: 154.2, height: 216.2 },
    { file: 'business-card.html', name: 'business-card', width: 91.2, height: 61.2 },
    { file: 'poster.html', name: 'poster-a4', width: 210, height: 297 },
    // The A3 proof would be the A4 one again, so it is skipped.
    { file: 'poster.html', name: 'poster-a3', width: 297, height: 420, scale: 420 / 297, proof: false },
];

const pieces = partnerCode
    ? [{ ...allPieces.find((piece) => piece.name === 'poster-a4'), name: `poster-a4-${partnerCode}`, proof: false }]
    : allPieces;

const MM_PER_INCH = 25.4;
const PROOF_DPI = 300;

await mkdir(out, { recursive: true });

const browser = await chromium.launch();

// A QR that fails to load leaves a blank square and no error, so stop rather than write that file.
const stopUnlessQrsLoaded = async (page, pieceName) => {
    const brokenSources = await page
        .locator('img.qr')
        .evaluateAll((images) => images.filter((image) => !image.complete || image.naturalWidth === 0).map((image) => image.src));

    if (brokenSources.length > 0) {
        console.error(`${pieceName}: QR did not load from ${brokenSources.join(', ')}. Check the file exists and is a readable SVG.`);
        await browser.close();
        process.exit(1);
    }
};

for (const piece of pieces) {
    const page = await browser.newPage({
        deviceScaleFactor: PROOF_DPI / 96,
        viewport: {
            width: Math.round((piece.width / (piece.scale ?? 1) / MM_PER_INCH) * 96),
            height: Math.round((piece.height / (piece.scale ?? 1) / MM_PER_INCH) * 96),
        },
    });

    await page.goto(`file://${join(here, piece.file)}`, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);
    await stopUnlessQrsLoaded(page, piece.name);

    if (partnerQr) {
        // decode() rejects for an unreadable file, which the check below reports.
        await page.locator('.qr').evaluate((qr, src) => {
            qr.src = src;

            return qr.decode().catch(() => {});
        }, `file://${partnerQr}`);
        await stopUnlessQrsLoaded(page, piece.name);
    }

    await page.pdf({
        path: join(out, `${piece.name}.pdf`),
        width: `${piece.width}mm`,
        height: `${piece.height}mm`,
        printBackground: true,
        preferCSSPageSize: true,
        scale: piece.scale ?? 1,
    });

    const sides = piece.proof === false ? [] : await page.locator('.piece').all();

    for (const [index, side] of sides.entries()) {
        const suffix = sides.length > 1 ? `-${index === 0 ? 'front' : 'back'}` : '';
        await side.screenshot({ path: join(out, `${piece.name}${suffix}.png`) });
    }

    console.log(`${piece.name}: ${piece.width} x ${piece.height}mm, ${sides.length} side(s)`);
    await page.close();
}

await browser.close();
