<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Config;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Config\PzPdfConfiguration;

#[CoversClass(PzPdfConfiguration::class)]
final class PzPdfConfigurationTest extends TestCase
{
    public function test_dot_path_returns_nested_value(): void
    {
        $config = PzPdfConfiguration::fromArray([
            'a' => ['b' => 1],
        ]);

        self::assertSame(1, $config->get('a.b'));
    }

    public function test_dot_path_missing_returns_typed_default(): void
    {
        $config = PzPdfConfiguration::fromArray([]);

        self::assertSame(42, $config->get('missing.path', 42));
        self::assertSame('fallback', $config->get('x', 'fallback'));
    }

    public function test_rejects_non_array_fonts_section(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("key 'fonts' must be of type array");

        PzPdfConfiguration::fromArray([
            'fonts' => '/tmp',
        ]);
    }

    public function test_rejects_non_string_fonts_directory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('fonts.directory');

        PzPdfConfiguration::fromArray([
            'fonts' => [
                'directory' => 123,
            ],
        ]);
    }

    public function test_to_array_round_trip(): void
    {
        $data = ['k' => ['v' => true]];
        $config = PzPdfConfiguration::fromArray($data);

        self::assertSame($data, $config->toArray());
    }

    public function test_package_config_contains_expected_top_level_keys(): void
    {
        /** @var array<string, mixed> $pkg */
        $pkg = require dirname(__DIR__, 3).'/config/pzpdf.php';

        foreach (['fonts', 'logos', 'colors', 'margins', 'layout', 'paths', 'datetime'] as $key) {
            self::assertArrayHasKey($key, $pkg, "config/pzpdf.php deve definir a chave de topo '{$key}'.");
        }
    }
}
