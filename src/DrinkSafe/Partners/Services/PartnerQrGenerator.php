<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Services;

use DrinkSafe\Partners\Models\PartnerLink;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * PartnerQrGenerator
 *
 * Writes a partner's QR code to print/qr/partners/<code>.svg with segno, to
 * the same spec as print/qr/qr-report-106mm.svg: error correction M without
 * boosting, a four module quiet zone, 106mm square, #0b1220 on white, with a
 * trailing newline. segno is not a project dependency and runs through uvx.
 */
final class PartnerQrGenerator
{
    /**
     * The segno release the committed QR files were made with.
     */
    public const string SEGNO_VERSION = '1.6.6';

    /**
     * The QR is drawn at the poster's 106mm, which the A3 enlarges.
     */
    private const string SEGNO_SCRIPT = <<<'PYTHON'
        import sys, segno
        qr = segno.make(sys.argv[1], error='m', micro=False, boost_error=False)
        qr.save(sys.argv[2], scale=106 / qr.symbol_size()[0], unit='mm',
                dark='#0b1220', light='#fff', svgclass=None, lineclass=None, nl=False)
        open(sys.argv[2], 'a').write('\n')
        PYTHON;

    /**
     * Where a partner's QR file lives.
     */
    public function path(PartnerLink $partnerLink): string
    {
        return base_path(sprintf('print/qr/partners/%s.svg', $partnerLink->code));
    }

    /**
     * The command that writes a partner's QR file. The URL and path are passed
     * as arguments rather than written into the script.
     *
     * @return list<string>
     */
    public function command(PartnerLink $partnerLink): array
    {
        return [
            'uvx',
            '--with',
            sprintf('segno==%s', self::SEGNO_VERSION),
            'python',
            '-c',
            self::SEGNO_SCRIPT,
            $partnerLink->url(),
            $this->path($partnerLink),
        ];
    }

    /**
     * The same command, quoted to paste into a shell.
     */
    public function commandLine(PartnerLink $partnerLink): string
    {
        return implode(' ', array_map(escapeshellarg(...), $this->command($partnerLink)));
    }

    /**
     * Write the partner's QR file, returning whether it worked.
     */
    public function generate(PartnerLink $partnerLink): bool
    {
        File::ensureDirectoryExists(dirname($this->path($partnerLink)));

        return Process::run($this->command($partnerLink))->successful();
    }
}
