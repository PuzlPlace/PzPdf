<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfImageAlign;
use ValueError;

#[CoversClass(PzPdfImageAlign::class)]
final class ImageAlignTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfImageAlign, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'Left' => [PzPdfImageAlign::Left, 'left'],
            'Center' => [PzPdfImageAlign::Center, 'center'],
            'Right' => [PzPdfImageAlign::Right, 'right'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(PzPdfImageAlign $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(3, PzPdfImageAlign::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(PzPdfImageAlign $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfImageAlign::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        PzPdfImageAlign::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(PzPdfImageAlign::tryFrom('invalid'));
    }
}
