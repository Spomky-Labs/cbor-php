<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;
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

    public static function getDataSet(): array
    {
        return [['f4'], ['f5'], ['f6'], ['f7'], ['f0'], ['f820'], ['f8ff']];
    }
}
