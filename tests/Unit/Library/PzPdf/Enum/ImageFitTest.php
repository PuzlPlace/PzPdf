<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfImageFit;
use ValueError;

#[CoversClass(PzPdfImageFit::class)]
final class ImageFitTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfImageFit, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'None' => [PzPdfImageFit::None, 'none'],
            'Fit' => [PzPdfImageFit::Fit, 'fit'],
            'Cover' => [PzPdfImageFit::Cover, 'cover'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(PzPdfImageFit $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(3, PzPdfImageFit::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(PzPdfImageFit $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfImageFit::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        PzPdfImageFit::from('invalid');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(PzPdfImageFit::tryFrom('invalid'));
    }
}
