<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Features\Domain;

use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Enum\PzPdfTextAlignment;
use Puzl\PzPdf\ValueObject\PzPdfColor;

/**
 * Garante que VOs e enums públicos compõem estruturas estáveis (ex.: estilo de célula)
 * consumíveis por componentes de layout nas tarefas seguintes.
 */
final class PzPdfDomainTypesCompositionTest extends TestCase
{
    /**
     * @return array{textAlign: string, fillRgb: array{int, int, int}}
     */
    private static function buildCellStyleSnapshot(PzPdfColor $fill, PzPdfTextAlignment $align): array
    {
        return [
            'textAlign' => $align->value,
            'fillRgb' => $fill->toArray(),
        ];
    }

    public function test_color_and_text_alignment_compose_serializable_cell_style(): void
    {
        $fill = PzPdfColor::hex('#337ab7');
        $align = PzPdfTextAlignment::Center;

        $style = self::buildCellStyleSnapshot($fill, $align);

        $this->assertSame('C', $style['textAlign']);
        $this->assertSame([51, 122, 183], $style['fillRgb']);

        $json = json_encode($style, JSON_THROW_ON_ERROR);
        $roundTrip = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($style, $roundTrip);
    }
}
