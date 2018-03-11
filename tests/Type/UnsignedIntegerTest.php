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

final class UnsignedIntegerTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getDataSet
     *
     * @param string $data
     */
    public function anUnsignedIntegerCanBeParsed(string $data)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, hex2bin($data));
        rewind($stream);

        $object = $this->getDecoder()->decode($stream);
        $object->getNormalizedData();
        self::assertEquals($data, bin2hex($object->__toString()));
    }

    /**
     * @return array
     */
    public function getDataSet(): array
    {
        return [
            [
                '00',
            ], [
                '01',
            ], [
                '0a',
            ], [
                '17',
            ], [
                '1818',
            ], [
                '1819',
            ], [
                '1864',
            ], [
                '1903e8',
            ], [
                '1a000f4240',
            ], [
                '1b000000e8d4a51000',
            ], [
                '1bffffffffffffffff',
            ], [
                'c249010000000000000000',
            ],
        ];
    }
}
