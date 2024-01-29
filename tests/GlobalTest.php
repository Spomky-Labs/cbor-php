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
final class GlobalTest extends CBORTestCase
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
        yield ['c074323031332d30332d32315432303a30343a30305a'];
        yield ['c11a514b67b0'];
        yield ['c1fb41d452d9ec200000'];
        yield ['d74401020304'];
        yield ['d818456449455446'];
        yield ['d82076687474703a2f2f7777772e6578616d706c652e636f6d'];
        yield ['40'];
        yield ['4401020304'];
        yield ['60'];
        yield ['6161'];
        yield ['6449455446'];
        yield ['62225c'];
        yield ['62c3bc'];
        yield ['63e6b0b4'];
        yield ['64f0908591'];
        yield ['80'];
        yield ['83010203'];
        yield ['8301820203820405'];
        yield ['98190102030405060708090a0b0c0d0e0f101112131415161718181819'];
        yield ['a0'];
        yield ['a201020304'];
        yield ['a26161016162820203'];
        yield ['826161a161626163'];
        yield ['a56161614161626142616361436164614461656145'];
        yield ['5f42010243030405ff'];
        yield ['7f657374726561646d696e67ff'];
        yield ['9fff'];
        yield ['9f018202039f0405ffff'];
        yield ['9f01820203820405ff'];
        yield ['83018202039f0405ff'];
        yield ['83019f0203ff820405'];
        yield ['9f0102030405060708090a0b0c0d0e0f101112131415161718181819ff'];
        yield ['bf61610161629f0203ffff'];
        yield ['826161bf61626163ff'];
        yield ['bf6346756ef563416d7421ff'];
        yield [
            'a401030339010020590100c5da6f4d9357bde202f5c558cd0a3156d254f2e0ad9ab57931f9826b747de1ac4f29d6070874dce57910e19844499d8e42470339b170d022b501ab88e9c2f4ed302e4719c70debe8842403ed9bdfc22730a61a1b70f616c5f1b700cacf7846137dc4b2d469a8e15aab4fad8657084022d28f44d9075323126b7007c981939fdf724caf4fbe475040431a4ea064430bcb2cfad7d05bdb9f64b5b0e0952ecf8679273d6c6dfa81601f14503316a13d0782c31a3e6bdded3d7bc46bc1fa9bef0dff83b7deaf146b582c4644821a3c62edbaa6be422bf04e43edaf5fd3783086153d7361a203061a6298ab26e1337ca1c9ed06741a5905477988e720304eae189d7f2143010001',
        ];
    }
}
