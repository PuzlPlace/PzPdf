<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Laravel;

use Illuminate\Support\ServiceProvider;
use Puzl\PzPdf\Config\PzPdfConfiguration;
use Puzl\PzPdf\Laravel\Console\PzPdfInstallCommand;

final class PzPdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/pzpdf.php', 'pzpdf');

        $this->app->singleton(PzPdfConfiguration::class, static function ($app): PzPdfConfiguration {
            /** @var array<string, mixed> $data */
            $data = $app['config']->get('pzpdf', []);

            return PzPdfConfiguration::fromArray($data);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PzPdfInstallCommand::class]);
        }

        $this->publishes([
            __DIR__.'/../../resources/stubs/pzpdf.laravel.php' => config_path('pzpdf.php'),
        ], 'pzpdf-config');

        $this->publishes([
            __DIR__.'/../../public/img' => public_path('vendor/puzl/pzpdf/img'),
        ], 'pzpdf-assets');
    }
}
