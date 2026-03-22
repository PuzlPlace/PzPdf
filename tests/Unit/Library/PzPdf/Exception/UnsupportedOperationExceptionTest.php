<?php

declare(strict_types=1);

namespace Puzl\PzPdf\Tests\Unit\Library\PzPdf\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Puzl\PzPdf\Exception\PzPdfException;
use Puzl\PzPdf\Exception\PzPdfUnsupportedOperationException;
use RuntimeException;

#[CoversClass(PzPdfUnsupportedOperationException::class)]
final class UnsupportedOperationExceptionTest extends TestCase
{
    public function testIsInstanceOfPzPdfException(): void
    {
        $exception = new PzPdfUnsupportedOperationException('test');

        $this->assertInstanceOf(PzPdfException::class, $exception);
    }

    public function testIsInstanceOfRuntimeException(): void
    {
        $exception = new PzPdfUnsupportedOperationException('test');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testCanBeConstructedWithMessage(): void
    {
        $message = 'Operation roundedRect() is not supported by this adapter.';
        $exception = new PzPdfUnsupportedOperationException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testCanBeCaughtAsPzPdfException(): void
    {
        $caught = false;

        try {
            throw new PzPdfUnsupportedOperationException('unsupported');
        } catch (PzPdfException) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }

    public function testCanBeCaughtAsRuntimeException(): void
    {
        $caught = false;

        try {
            throw new PzPdfUnsupportedOperationException('unsupported');
        } catch (RuntimeException) {
            $caught = true;
        }

        $this->assertTrue($caught);
    }
}
