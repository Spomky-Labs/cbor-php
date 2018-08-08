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

final class FloatTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getDataSet
     */
    public function aFloatCanBeParsed(string $data)
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
                'f90000',
            ], [
                'f98000',
            ], [
                'f93c00',
            ], [
                'fb3ff199999999999a',
            ], [
                'f93e00',
            ], [
                'f97bff',
            ], [
                'fa47c35000',
            ], [
                'fa7f7fffff',
            ], [
                'fb7e37e43c8800759c',
            ], [
                'f90001',
            ], [
                'f90400',
            ], [
                'f9c400',
            ], [
                'fbc010666666666666',
            ], [
                'f97c00',
            ], [
                'f97e00',
            ], [
                'f9fc00',
            ], [
                'fa7f800000',
            ], [
                'fa7fc00000',
            ], [
                'faff800000',
            ], [
                'fb7ff0000000000000',
            ], [
                'fb7ff8000000000000',
            ], [
                'fbfff0000000000000',
            ],
        ];
    }
}
