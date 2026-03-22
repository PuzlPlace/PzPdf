<?php

declare(strict_types=1);

/**
 * Worker CLI disparado por {@see \Puzl\PzPdf\Tests\Integration\Concurrency\PzPdfConcurrentGenerationTest}
 * via proc_open. Variáveis de ambiente obrigatórias: PZPDF_OUT_FILE, PZPDF_REPO_ROOT.
 * Opcionais: PZPDF_CACHE_DIR, TEST_TOKEN, UNIQUE_TEST_TOKEN (isolamento de cache em paralelo).
 */

$root = getenv('PZPDF_REPO_ROOT');
$out = getenv('PZPDF_OUT_FILE');
if (! is_string($root) || $root === '' || ! is_string($out) || $out === '') {
    fwrite(STDERR, "PZPDF_REPO_ROOT and PZPDF_OUT_FILE are required.\n");
    exit(1);
}

require $root.'/vendor/autoload.php';

use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\Enum\PzPdfOutputMode;
use Puzl\PzPdf\Enum\PzPdfPageOrientation;
use Puzl\PzPdf\Enum\PzPdfPageSize;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\PzPdfFactory;

$label = getenv('TEST_TOKEN') ?: (getenv('UNIQUE_TEST_TOKEN') ?: 'worker');
$pdf = PzPdfFactory::make();
$pdf
    ->addPage(PzPdfPageOrientation::Portrait, PzPdfPageSize::A4)
    ->font('Helvetica', PzPdfFontStyle::Regular, 12)
    ->cell(80, 10, 'token:'.$label, 0, 1, PzPdfTextAlignment::Left, false);

$pdf->output($out, PzPdfOutputMode::File);

exit(0);
