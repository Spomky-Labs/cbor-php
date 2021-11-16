<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;

/**
 * @internal
 */
final class OtherTest extends CBORTestCase
{
    /**
     * @test
     * @dataProvider getDataSet
     */
    public function aSignedIntegerCanBeParsed(string $data): void
    {
        $stream = StringStream::create(hex2bin($data));
        $object = $this->getDecoder()
            ->decode($stream)
        ;
        $object->getNormalizedData();
        static::assertSame($data, bin2hex((string) $object));
    }

    public function getDataSet(): array
    {
        return [['f4'], ['f5'], ['f6'], ['f7'], ['f0'], ['f820'], ['f8ff']];
    }
}
