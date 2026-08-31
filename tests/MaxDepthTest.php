<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\Decoder;
use CBOR\ListObject;
use CBOR\StringStream;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use function str_repeat;

/**
 * @internal
 */
final class MaxDepthTest extends CBORTestCase
{
    #[Test]
    public function aStructureThatReachesTheMaximumDepthIsDecoded(): void
    {
        $data = str_repeat("\x81", Decoder::DEFAULT_MAX_DEPTH) . "\x00";

        $object = $this->getDecoder()
            ->decode(StringStream::create($data))
        ;

        static::assertInstanceOf(ListObject::class, $object);
        static::assertCount(1, $object);
    }

    #[Test]
    public function aStructureDeeperThanTheMaximumDepthIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse the data. Maximum nesting depth of 1000 exceeded.');

        $data = str_repeat("\x81", Decoder::DEFAULT_MAX_DEPTH + 1) . "\x00";

        $this->getDecoder()
            ->decode(StringStream::create($data))
        ;
    }

    #[Test]
    public function theMaximumDepthIsConfigurable(): void
    {
        $decoder = Decoder::create(null, null, 2);

        $object = $decoder->decode(StringStream::create("\x81\x81\x00"));

        static::assertInstanceOf(ListObject::class, $object);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse the data. Maximum nesting depth of 2 exceeded.');

        $decoder->decode(StringStream::create("\x81\x81\x81\x00"));
    }

    #[Test]
    public function deeplyNestedMapsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse the data. Maximum nesting depth of 2 exceeded.');

        $data = str_repeat("\xa1\x00", 3) . "\x00";

        Decoder::create(null, null, 2)->decode(StringStream::create($data));
    }

    #[Test]
    public function deeplyNestedIndefiniteLengthListsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse the data. Maximum nesting depth of 2 exceeded.');

        $data = str_repeat("\x9f", 3) . "\x00" . str_repeat("\xff", 3);

        Decoder::create(null, null, 2)->decode(StringStream::create($data));
    }

    #[Test]
    public function deeplyNestedTagsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse the data. Maximum nesting depth of 2 exceeded.');

        $data = str_repeat("\xcb", 3) . "\x00";

        Decoder::create(null, null, 2)->decode(StringStream::create($data));
    }

    #[Test]
    public function theMaximumDepthCannotBeLowerThanOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The maximum nesting depth shall be at least 1. Got 0.');

        Decoder::create(null, null, 0);
    }
}
