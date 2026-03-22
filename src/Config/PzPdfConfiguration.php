<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Config;

use InvalidArgumentException;

/**
 * Value object imutável com a configuração do PzPdf (mesma forma de array que {@code config/pzpdf.php}).
 *
 * O método {@see self::get()} aceita retorno e default tipados como {@code mixed} de propósito, alinhado à Tech Spec §3.1,
 * para acesso genérico por dot-notation sem perder flexibilidade de valores escalares e aninhados.
 */
final readonly class PzPdfConfiguration
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        self::validateStructure($data);

        return new self($data);
    }

    /**
     * Acesso por dot-notation (ex.: {@code fonts.directory}). Retorna {@code $default} se algum segmento não existir.
     *
     * @return mixed
     */
    public function get(string $dotPath, mixed $default = null): mixed
    {
        $segments = explode('.', $dotPath);
        $value = $this->data;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function validateStructure(array $data): void
    {
        $checks = [
            'fonts' => 'array',
            'logos' => 'array',
            'paths' => 'array',
            'datetime' => 'array',
            'colors' => 'array',
            'margins' => 'array',
            'layout' => 'array',
        ];

        foreach ($checks as $key => $expected) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $type = get_debug_type($data[$key]);
            if ($type !== $expected) {
                throw new InvalidArgumentException(
                    "PzPdfConfiguration: a chave '{$key}' deve ser do tipo {$expected}, {$type} fornecido."
                );
            }
        }

        if (isset($data['fonts']['directory']) && ! is_string($data['fonts']['directory'])) {
            throw new InvalidArgumentException(
                "PzPdfConfiguration: 'fonts.directory' deve ser string, ".get_debug_type($data['fonts']['directory']).' fornecido.'
            );
        }

        foreach (['header', 'footer'] as $logoKey) {
            if (isset($data['logos'][$logoKey]) && ! is_string($data['logos'][$logoKey])) {
                throw new InvalidArgumentException(
                    "PzPdfConfiguration: 'logos.{$logoKey}' deve ser string."
                );
            }
        }

        if (isset($data['paths']['public']) && ! is_string($data['paths']['public'])) {
            throw new InvalidArgumentException("PzPdfConfiguration: 'paths.public' deve ser string.");
        }

        foreach (['locale', 'timezone'] as $dtKey) {
            if (isset($data['datetime'][$dtKey]) && ! is_string($data['datetime'][$dtKey])) {
                throw new InvalidArgumentException("PzPdfConfiguration: 'datetime.{$dtKey}' deve ser string.");
            }
        }
    }
}
