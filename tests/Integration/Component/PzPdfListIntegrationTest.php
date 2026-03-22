<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Integration\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Component\PzPdfListBuilder;
use Puzl\PzPdf\Enum\PzPdfFontStyle;
use Puzl\PzPdf\PzPdfFactory;

#[CoversClass(PzPdfListBuilder::class)]
final class PzPdfListIntegrationTest extends TestCase
{
    public function testListWithNestedItemsProducesValidPdf(): void
    {
        $pdf = PzPdfFactory::make();
        $pdf->margins(20, 25, 20, 25)
            ->addPage()
            ->font('Helvetica', PzPdfFontStyle::Regular, 11)
            ->setXY(20, 40);

        $pdf->bulletList()
            ->bulletStyle('•', 5, 8)
            ->lineHeight(6)
            ->subListIndent(5)
            ->items([
                'First top-level item',
                [
                    'Nested A with more text',
                    'Nested B',
                ],
                'Second top-level item',
            ])
            ->end();

        $raw = $pdf->toString();

        self::assertStringStartsWith('%PDF', $raw);
        self::assertGreaterThan(500, strlen($raw));
    }
}
