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

final class UnsignedIntegerTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getDataSet
     *
     * @param string $data
     * @param string $expected_normalized_data
     */
    public function anUnsignedIntegerCanBeParsed(string $data, string $expected_normalized_data)
    {
        $stream = new StringStream(hex2bin($data));
        $object = $this->getDecoder()->decode($stream);
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
                '00',
                '0',
            ], [
                '01',
                '1',
            ], [
                '0a',
                '10',
            ], [
                '17',
                '23',
            ], [
                '1818',
                '24',
            ], [
                '1819',
                '25',
            ], [
                '1864',
                '100',
            ], [
                '1903e8',
                '1000',
            ], [
                '1a000f4240',
                '1000000',
            ], [
                '1b000000e8d4a51000',
                '1000000000000',
            ], [
                '1bffffffffffffffff',
                '18446744073709551615',
            ], [
                'c249010000000000000000',
                '18446744073709551616',
            ],
        ];
    }
}
