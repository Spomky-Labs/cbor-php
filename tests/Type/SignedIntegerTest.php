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

final class SignedIntegerTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getDataSet
     *
     * @param string $data
     */
    public function anUnsignedIntegerCanBeEncodedAndDecoded(string $data)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, hex2bin($data));
        rewind($stream);

        $object = $this->getDecoder()->decode($stream);
        self::assertEquals($data, bin2hex($object->__toString()));
    }

    /**
     * @return array
     */
    public function getDataSet(): array
    {
        return [
            [
                '20',
            ], [
                '29',
            ], [
                '3863',
            ], [
                '3903e7',
            ],
        ];
    }
}
