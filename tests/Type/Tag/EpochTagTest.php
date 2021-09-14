<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2021 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test\Type\Tag;

use CBOR\CBORObject;
use CBOR\Tag\EpochTag;
use DateTime;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EpochTagTest extends TestCase
{
    /**
     * @test
     * @dataProvider getValidValue
     */
    public function createAndNormalize(CBORObject $object, \DateTimeInterface $expectedDateTime): void
    {
        $tag = EpochTag::create($object);
        static::assertEquals($expectedDateTime, $tag->getNormalizedData());
    }

    protected function createCBORObjectMock(string $normalizedData): CBORObject
    {
        $mock = $this->createMock(CBORObject::class);
        $mock->method('getNormalizedData')
            ->willReturn($normalizedData);

        return $mock;
    }

    public function getValidValue(): array
    {
        $buildTestEntry = function (string $datetime) {
            return [
                $this->createCBORObjectMock($datetime),
                new DateTime($datetime)
            ];
        };

        return [
            $buildTestEntry('2003-12-13T18:30:02Z'),
            $buildTestEntry('2003-12-13T18:30:02.25Z'),
            $buildTestEntry('2003-12-13T18:30:02+01:00'),
            $buildTestEntry('2003-12-13T18:30:02.25+01:00'),
            $buildTestEntry('2003-12-13T18:30:02.251254+01:00'),
        ];
    }
}
