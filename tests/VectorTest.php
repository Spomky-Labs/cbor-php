<?php

declare(strict_types=1);

/*
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

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
