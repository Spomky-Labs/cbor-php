<?php

declare(strict_types=1);

namespace CBOR\Test\Type;

use CBOR\StringStream;

/**
 * @internal
 */
final class OtherTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getDataSet
     */
    public function aSignedIntegerCanBeParsed(string $data): void
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
                'f4',
            ], [
                'f5',
            ], [
                'f6',
            ], [
                'f7',
            ], [
                'f0',
            ], [
                'f818',
            ], [
                'f8ff',
            ],
        ];
    }
}
