<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Laravel\Console;

use Illuminate\Console\Command;

final class PzPdfInstallCommand extends Command
{
    protected $signature = 'pzpdf:install
                            {--force : Sobrescreve arquivos já publicados}
                            {--no-assets : Não copia logos para public/vendor/puzl/pzpdf-fpdf/img}';

    protected $description = 'Publica config/pzpdf.php no app Laravel e, por padrão, copia logos para public/vendor (opcional)';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'pzpdf-config',
            '--force' => (bool) $this->option('force'),
        ]);

        if (! $this->option('no-assets')) {
            $this->call('vendor:publish', [
                '--tag' => 'pzpdf-assets',
                '--force' => (bool) $this->option('force'),
            ]);
        }

        $this->newLine();
        $this->info('PzPdf: config em config/pzpdf.php (sobreponha valores ou use .env conforme o docblock do pacote).');
        if (! $this->option('no-assets')) {
            $this->comment('Assets em public/vendor/puzl/pzpdf-fpdf/img — opcional; o PDF já usa os arquivos dentro de vendor por padrão.');
        }

        return self::SUCCESS;
    }
}
