<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Exception\PzPdfException;
use RuntimeException;

#[CoversClass(PzPdfException::class)]
final class PzPdfExceptionTest extends TestCase
{
    public function testIsInstanceOfRuntimeException(): void
    {
        $exception = new PzPdfException('test');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testCanBeConstructedWithMessage(): void
    {
        $message = 'PzPdfColor: valor de red (300) fora do range 0-255.';
        $exception = new PzPdfException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testCanBeConstructedWithMessageAndCode(): void
    {
        $exception = new PzPdfException('error', 42);

        $this->assertSame('error', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
    }

    public function testCanBeCaughtAsRuntimeException(): void
    {
        $caught = false;

        try {
            throw new PzPdfException('test');
        } catch (RuntimeException) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }
}
