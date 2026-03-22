<?php

declare(strict_types=1);

namespace Puzl\PzPdf\ValueObject;

use Puzl\PzPdf\Enum\PzPdfPageSize;
use Puzl\PzPdf\Exception\PzPdfException;

/**
 * Encapsula um tamanho de página — enum predefinido ou dimensões customizadas (mm).
 *
 * Permite que addPage() aceite tanto PzPdfPageSize::A4 quanto dimensões
 * arbitrárias (largura × altura) de forma type-safe num único tipo.
 */
final readonly class PzPdfPageDimension
{
    private function __construct(
        public ?PzPdfPageSize $enum,
        public ?float $width,
        public ?float $height,
    ) {
    }

    /**
     * Cria dimensão a partir de um tamanho de página predefinido.
     */
    public static function fromEnum(PzPdfPageSize $size): self
    {
        return new self(enum: $size, width: null, height: null);
    }

    /**
     * Cria dimensão customizada em milímetros.
     *
     * @throws PzPdfException Se largura ou altura forem <= 0
     */
    public static function custom(float $width, float $height): self
    {
        if ($width <= 0 || $height <= 0) {
            throw new PzPdfException(
                "Dimensões de página inválidas ({$width}×{$height}mm). "
                .'Ambos largura e altura devem ser maiores que zero.'
            );
        }

        return new self(enum: null, width: $width, height: $height);
    }

    /**
     * Retorna o parâmetro no formato esperado pelo FPDF.
     *
     * @return string|array{float, float} String para enum (ex: 'A4'), array [width, height] para customizado
     */
    public function toFpdfParam(): string|array
    {
        if ($this->enum !== null) {
            return $this->enum->value;
        }

        return [$this->width, $this->height];
    }
}
