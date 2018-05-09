<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test\Type;

use CBOR\StringStream;

final class SignedIntegerTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getDataSet
     *
     * @param string $data
     * @param string $expected_normalized_data
     */
    public function anUnsignedIntegerCanBeEncodedAndDecoded(string $data, string $expected_normalized_data)
    {
        $stream = new StringStream(hex2bin($data));
        $object = $this->getDecoder()->decode($stream);
        $object->getNormalizedData();
        self::assertEquals($data, bin2hex((string) $object));
        self::assertEquals($expected_normalized_data, $object->getNormalizedData());
    }

    /**
     * @return array
     */
    public function getDataSet(): array
    {
        return [
            [
                '20',
                '-1',
            ], [
                '29',
                '-10',
            ], [
                '3863',
                '-100',
            ], [
                '3903e7',
                '-1000',
            ], [
                'c349010000000000000000',
                '-18446744073709551617',
            ], [
                '3bffffffffffffffff',
                '-18446744073709551616',
            ],
        ];
    }
}
