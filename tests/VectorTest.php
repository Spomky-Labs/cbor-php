<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;
use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final class VectorTest extends CBORTestCase
{
    /**
     * @test
     * @dataProvider getVectors
     */
    public function createOnValidValue(string $cbor, string $hex): void
    {
        $stream = StringStream::create(base64_decode($cbor, true));
        $result = $this->getDecoder()
            ->decode($stream)
        ;

        static::assertSame(hex2bin($hex), (string) $result);
    }

    public function getVectors(): array
    {
        return json_decode(file_get_contents(__DIR__ . '/vectors.json'), true, 512, JSON_THROW_ON_ERROR);
    }
}
