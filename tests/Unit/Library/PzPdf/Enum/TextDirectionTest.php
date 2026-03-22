<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfTextDirection;
use ValueError;

#[CoversClass(PzPdfTextDirection::class)]
final class TextDirectionTest extends TestCase
{
    /**
     * @return array<string, array{PzPdfTextDirection, string}>
     */
    public static function casesProvider(): array
    {
        return [
            'LeftToRight' => [PzPdfTextDirection::LeftToRight, 'R'],
            'BottomToTop' => [PzPdfTextDirection::BottomToTop, 'U'],
            'TopToBottom' => [PzPdfTextDirection::TopToBottom, 'D'],
            'RightToLeft' => [PzPdfTextDirection::RightToLeft, 'L'],
        ];
    }

    #[DataProvider('casesProvider')]
    public function test_case_has_correct_backed_value(PzPdfTextDirection $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    public function test_all_cases_exist(): void
    {
        $this->assertCount(4, PzPdfTextDirection::cases());
    }

    #[DataProvider('casesProvider')]
    public function test_from_returns_correct_case(PzPdfTextDirection $expectedCase, string $value): void
    {
        $this->assertSame($expectedCase, PzPdfTextDirection::from($value));
    }

    public function test_from_throws_value_error_for_invalid_value(): void
    {
        $this->expectException(ValueError::class);

        PzPdfTextDirection::from('X');
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(PzPdfTextDirection::tryFrom('X'));
    }
}
