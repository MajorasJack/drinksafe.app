/**
 * Renders the print artwork to PDF at physical size, plus PNG proofs for a
 * quick look on screen.
 *
 * Chromium honours the @page size in each file, so nothing is scaled and the
 * PDFs come out at trim plus 3mm bleed.
 */

import { chromium } from 'playwright';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { mkdir } from 'node:fs/promises';

const here = dirname(fileURLToPath(import.meta.url));
const out = join(here, '..', 'output');

const pieces = [
    { file: 'sticker-a.html', name: 'sticker-a', width: 106.2, height: 106.2 },
    { file: 'sticker-b.html', name: 'sticker-b', width: 106.2, height: 106.2 },
    { file: 'flyer.html', name: 'flyer', width: 154.2, height: 216.2 },
];

const MM_PER_INCH = 25.4;
const PROOF_DPI = 300;

await mkdir(out, { recursive: true });

const browser = await chromium.launch();

for (const piece of pieces) {
    const page = await browser.newPage({
        deviceScaleFactor: PROOF_DPI / 96,
        viewport: {
            width: Math.round((piece.width / MM_PER_INCH) * 96),
            height: Math.round((piece.height / MM_PER_INCH) * 96),
        },
    });

    await page.goto(`file://${join(here, piece.file)}`, { waitUntil: 'networkidle' });
    await page.evaluate(() => document.fonts.ready);

    await page.pdf({
        path: join(out, `${piece.name}.pdf`),
        width: `${piece.width}mm`,
        height: `${piece.height}mm`,
        printBackground: true,
        preferCSSPageSize: true,
    });

    const sides = await page.locator('.piece').all();
    for (const [index, side] of sides.entries()) {
        const suffix = sides.length > 1 ? `-${index === 0 ? 'front' : 'back'}` : '';
        await side.screenshot({ path: join(out, `${piece.name}${suffix}.png`) });
    }

    console.log(`${piece.name}: ${piece.width} x ${piece.height}mm, ${sides.length} side(s)`);
    await page.close();
}

await browser.close();
