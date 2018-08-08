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

final class GlobalTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getDataSet
     */
    public function aSignedIntegerCanBeParsed(string $data)
    {
        $stream = new StringStream(hex2bin($data));
        $object = $this->getDecoder()->decode($stream);
        $object->getNormalizedData();
        static::assertEquals($data, bin2hex((string) $object));
    }

    public function getDataSet(): array
    {
        return [
            [
                'c074323031332d30332d32315432303a30343a30305a',
            ], [
                'c11a514b67b0',
            ], [
                'c1fb41d452d9ec200000',
            ], [
                'd74401020304',
            ], [
                'd818456449455446',
            ], [
                'd82076687474703a2f2f7777772e6578616d706c652e636f6d',
            ], [
                '40',
            ], [
                '4401020304',
            ], [
                '60',
            ], [
                '6161',
            ], [
                '6449455446',
            ], [
                '62225c',
            ], [
                '62c3bc',
            ], [
                '63e6b0b4',
            ], [
                '64f0908591',
            ], [
                '80',
            ], [
                '83010203',
            ], [
                '8301820203820405',
            ], [
                '98190102030405060708090a0b0c0d0e0f101112131415161718181819',
            ], [
                'a0',
            ], [
                'a201020304',
            ], [
                'a26161016162820203',
            ], [
                '826161a161626163',
            ], [
                'a56161614161626142616361436164614461656145',
            ], [
                '5f42010243030405ff',
            ], [
                '7f657374726561646d696e67ff',
            ], [
                '9fff',
            ], [
                '9f018202039f0405ffff',
            ], [
                '9f01820203820405ff',
            ], [
                '83018202039f0405ff',
            ], [
                '83019f0203ff820405',
            ], [
                '9f0102030405060708090a0b0c0d0e0f101112131415161718181819ff',
            ], [
                'bf61610161629f0203ffff',
            ], [
                '826161bf61626163ff',
            ], [
                'bf6346756ef563416d7421ff',
            ],
        ];
    }
}
