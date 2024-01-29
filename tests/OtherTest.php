<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class OtherTest extends CBORTestCase
{
    #[DataProvider('getDataSet')]
    #[Test]
    public function aSignedIntegerCanBeParsed(string $data): void
    {
        $stream = StringStream::create(hex2bin($data));
        $object = $this->getDecoder()
            ->decode($stream)
        ;
        static::assertSame($data, bin2hex((string) $object));
    }

    public static function getDataSet(): Iterator
    {
        yield ['f4'];
        yield ['f5'];
        yield ['f6'];
        yield ['f7'];
        yield ['f0'];
        yield ['f820'];
        yield ['f8ff'];
    }
}
