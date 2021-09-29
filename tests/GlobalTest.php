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
final class GlobalTest extends BaseTestCase
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
            ], [
                'a401030339010020590100c5da6f4d9357bde202f5c558cd0a3156d254f2e0ad9ab57931f9826b747de1ac4f29d6070874dce57910e19844499d8e42470339b170d022b501ab88e9c2f4ed302e4719c70debe8842403ed9bdfc22730a61a1b70f616c5f1b700cacf7846137dc4b2d469a8e15aab4fad8657084022d28f44d9075323126b7007c981939fdf724caf4fbe475040431a4ea064430bcb2cfad7d05bdb9f64b5b0e0952ecf8679273d6c6dfa81601f14503316a13d0782c31a3e6bdded3d7bc46bc1fa9bef0dff83b7deaf146b582c4644821a3c62edbaa6be422bf04e43edaf5fd3783086153d7361a203061a6298ab26e1337ca1c9ed06741a5905477988e720304eae189d7f2143010001',
            ],
        ];
    }
}
