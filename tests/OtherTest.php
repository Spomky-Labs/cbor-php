<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test;

use CBOR\StringStream;

/**
 * @internal
 */
final class OtherTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getDataSet
     */
    public function aSignedIntegerCanBeParsed(string $data): void
    {
        $stream = StringStream::create(hex2bin($data));
        $object = $this->getDecoder()->decode($stream);
        $object->getNormalizedData();
        static::assertEquals($data, bin2hex((string) $object));
    }

    public function getDataSet(): array
    {
        return [
            ['f4'],
            ['f5'],
            ['f6'],
            ['f7'],
            ['f0'],
            ['f820'],
            ['f8ff'],
        ];
    }
}
