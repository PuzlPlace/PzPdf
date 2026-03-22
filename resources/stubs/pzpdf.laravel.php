<?php

declare(strict_types=1);

/**
 * Configuração publicada no app Laravel (rodar {@code php artisan pzpdf:install}).
 *
 * Reaproveita os defaults do pacote (inclui caminhos absolutos para logos em vendor) e fixa
 * {@code paths.public} para a pasta {@code public} do projeto.
 */

use Puzl\PzPdf\Laravel\PzPdfServiceProvider;
use ReflectionClass;

$ref = new ReflectionClass(PzPdfServiceProvider::class);
$packageRoot = dirname($ref->getFileName(), 3);
/** @var array<string, mixed> $base */
$base = require $packageRoot.'/config/pzpdf.php';

return array_replace_recursive($base, [
    'paths' => [
        'public' => public_path(),
    ],
]);
