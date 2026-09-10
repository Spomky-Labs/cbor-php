<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\ByteStringObject;
use CBOR\StringStream;
use CBOR\TextStringObject;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * A string object computes its length head only when that head is observed.
 *
 * The payload is fixed at construction, so unlike a container the head can never go stale -- it is computed once and
 * kept. What these tests pin down is that both observers, __toString() and getAdditionalInformation(), see it, in
 * either order, and that the encoding is unchanged across the width boundaries.
 *
 * @internal
 */
final class LazyStringHeadTest extends CBORTestCase
{
    #[DataProvider('getPayloadLength')]
    #[Test]
    public function aTextStringReportsItsHeadWithoutBeingEncodedFirst(
        int $length,
        int $expectedAdditionalInformation
    ): void {
        $object = TextStringObject::create(str_repeat('a', $length));

        static::assertSame($expectedAdditionalInformation, $object->getAdditionalInformation());
    }

    #[DataProvider('getPayloadLength')]
    #[Test]
    public function aByteStringReportsItsHeadWithoutBeingEncodedFirst(
        int $length,
        int $expectedAdditionalInformation
    ): void {
        $object = ByteStringObject::create(str_repeat("\x01", $length));

        static::assertSame($expectedAdditionalInformation, $object->getAdditionalInformation());
    }

    /**
     * The head widens at 24 bytes, then at 256.
     */
    public static function getPayloadLength(): Iterator
    {
        yield 'empty' => [0, 0];
        yield 'one byte' => [1, 1];
        yield 'last inline length' => [23, 23];
        yield 'first one-byte length' => [24, 24];
        yield 'last one-byte length' => [255, 24];
        yield 'first two-byte length' => [256, 25];
    }

    #[DataProvider('getEncodedPayload')]
    #[Test]
    public function aTextStringEncodesToTheSameBytesWhicheverObserverComesFirst(
        int $length,
        string $expectedHead
    ): void {
        $payload = str_repeat('a', $length);

        $encodedFirst = bin2hex((string) TextStringObject::create($payload));

        $headFirst = TextStringObject::create($payload);
        $headFirst->getAdditionalInformation();

        static::assertStringStartsWith($expectedHead, $encodedFirst);
        static::assertSame($encodedFirst, bin2hex((string) $headFirst));
    }

    public static function getEncodedPayload(): Iterator
    {
        yield 'inline length' => [5, '65'];
        yield 'one-byte length' => [24, '7818'];
        yield 'two-byte length' => [256, '790100'];
    }

    /**
     * Asking twice must not compute twice, nor return something different the second time.
     */
    #[Test]
    public function theHeadIsStableAcrossRepeatedReads(): void
    {
        $object = TextStringObject::create(str_repeat('a', 24));

        static::assertSame(24, $object->getAdditionalInformation());
        static::assertSame(24, $object->getAdditionalInformation());
        static::assertSame('7818' . str_repeat('61', 24), bin2hex((string) $object));
        static::assertSame(24, $object->getAdditionalInformation());
    }

    /**
     * A decoded string is the case the laziness is for: it is built from the payload and read back through
     * normalize(), so the head is never needed -- but re-encoding it still has to produce the original bytes.
     */
    #[DataProvider('getRoundTrip')]
    #[Test]
    public function aDecodedStringReEncodesToItsOriginalBytes(string $encoded, string $expectedValue): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create(hex2bin($encoded)))
        ;

        static::assertSame($expectedValue, $object->normalize());
        static::assertSame($encoded, bin2hex((string) $object));
    }

    public static function getRoundTrip(): Iterator
    {
        yield 'inline text' => ['6161', 'a'];
        yield 'one-byte text' => ['7818' . str_repeat('61', 24), str_repeat('a', 24)];
        yield 'two-byte text' => ['790100' . str_repeat('61', 256), str_repeat('a', 256)];
        yield 'inline bytes' => ['4101', "\x01"];
        yield 'one-byte bytes' => ['5818' . str_repeat('01', 24), str_repeat("\x01", 24)];
    }
}
