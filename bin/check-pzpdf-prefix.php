#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Checklist RF-04: tipos em src/ (exceto Internal/) devem usar prefixo PzPdf.
 *
 * Uso: php bin/check-pzpdf-prefix.php
 *      composer check:prefix
 */

use Puzl\PzPdf\Tests\Unit\Tooling\PzPdfNamingConventionScanner;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

$violations = PzPdfNamingConventionScanner::scanSourceTree($root.'/src');

foreach ($violations as $line) {
    fwrite(STDERR, $line.PHP_EOL);
}

exit($violations === [] ? 0 : 1);
