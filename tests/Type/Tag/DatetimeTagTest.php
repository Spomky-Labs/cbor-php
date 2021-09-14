<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 * Copyright (c) 2018-2020 Spomky-Labs
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test\Type\Tag;

use CBOR\CBORObject;
use CBOR\Tag\DatetimeTag;
use CBOR\TextStringObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DatetimeTagTest extends TestCase
{
    /**
     * @test
     * @dataProvider getValidValue
     */
    public function createAndNormalize(CBORObject $object, string $expectedTimestamp): void
    {
        $tag = DatetimeTag::create($object);
        static::assertEquals($expectedTimestamp, $tag->getNormalizedData()->format('U.u'));
    }

    public function getValidValue(): array
    {
        $buildTestEntry = static function (string $datetime, string $timestamp): array {
            return [
                new TextStringObject($datetime),
                $timestamp,
            ];
        };

        return [
            $buildTestEntry('2003-12-13T18:30:02Z', '1071340202.000000'),
            $buildTestEntry('2003-12-13T18:30:02.25Z', '1071340202.250000'),
            $buildTestEntry('2003-12-13T18:30:02+01:00', '1071336602.000000'),
            $buildTestEntry('2003-12-13T18:30:02.25+01:00', '1071336602.250000'),
            $buildTestEntry('2003-12-13T18:30:02.251254+01:00', '1071336602.251254'),
        ];
    }
}
