<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use CBOR\ByteStringObject;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\Normalizable;
use CBOR\StringStream;
use CBOR\Tag\Ipv4Tag;
use CBOR\Tag\Ipv6Tag;
use CBOR\Tag\NetworkAddressPrefixTag;
use CBOR\Tag\NetworkAddressTag;
use CBOR\Test\CBORTestCase;
use CBOR\UnsignedIntegerObject;
use function hex2bin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use function str_replace;

/**
 * The address tags of RFC 9164 (52 and 54) and the two earlier ones they replaced (260 and 261).
 *
 * @internal
 */
final class IpAddressTagTest extends CBORTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function addresses(): iterable
    {
        yield 'an IPv4 address' => ['d8344401020304', '1.2.3.4'];
        // 52([24, h'0a0a0a']): the trailing zero byte of the prefix is left out.
        yield 'an IPv4 prefix' => ['d834821818430a0a0a', '10.10.10.0/24'];
        // 52([h'01020304', "eth0"]).
        yield 'an IPv4 address with a zone' => ['d834824401020304646574 6830', '1.2.3.4%eth0'];
        yield 'an IPv6 address' => ['d8365020010db8000000000000000000000000', '2001:db8::'];
        yield 'an IPv6 prefix' => ['d8368218204420010db8', '2001:db8::/32'];
        yield 'a legacy IPv4 address' => ['d901044401020304', '1.2.3.4'];
        yield 'a legacy MAC address' => ['d9010446010203040506', '01:02:03:04:05:06'];
        yield 'a legacy IPv6 address' => ['d901045020010db8000000000000000000000000', '2001:db8::'];
    }

    #[DataProvider('addresses')]
    #[Test]
    public function anAddressIsNormalizedIntoItsTextualForm(string $data, string $expected): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin(str_replace(' ', '', $data))));

        static::assertInstanceOf(Normalizable::class, $object);
        static::assertSame($expected, $object->normalize());
    }

    #[Test]
    public function anAddressOfTheWrongSizeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a 4 byte Byte String object as an address.');
        Ipv4Tag::create(ByteStringObject::create("\x01\x02\x03"));
    }

    #[Test]
    public function aPrefixLongerThanTheAddressFamilyAllowsIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid prefix length. Expected at most 32.');
        Ipv4Tag::create(ListObject::create([
            UnsignedIntegerObject::create(33),
            ByteStringObject::create("\x0a"),
        ]));
    }

    #[Test]
    public function aPrefixWithMoreBytesThanTheAddressFamilyAllowsIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid prefix. Expected at most 4 bytes.');
        Ipv4Tag::create(ListObject::create([
            UnsignedIntegerObject::create(24),
            ByteStringObject::create("\x0a\x0a\x0a\x0a\x0a"),
        ]));
    }

    #[Test]
    public function anAddressIsBuiltFromItsTextualForm(): void
    {
        static::assertSame('192.0.2.1', Ipv4Tag::createFromAddress('192.0.2.1')->normalize());
        static::assertSame('2001:db8::1', Ipv6Tag::createFromAddress('2001:db8::1')->normalize());
    }

    #[Test]
    public function aTextThatIsNotAnAddressOfThatFamilyIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value is not a valid IPv4 address.');
        Ipv4Tag::createFromAddress('2001:db8::1');
    }

    #[Test]
    public function aLegacyAddressOfAnUnknownSizeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a 4, 6 or 16 byte Byte String object.');
        NetworkAddressTag::create(ByteStringObject::create("\x01\x02\x03"));
    }

    #[Test]
    public function aLegacyPrefixIsTheSingleEntryMapItIs(): void
    {
        // 261({h'0a0a0a00': 24})
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d90105a1440a0a0a001818')));

        static::assertInstanceOf(NetworkAddressPrefixTag::class, $object);
        static::assertSame([
            "\x0a\x0a\x0a\x00" => '24',
        ], $object->normalize());
    }

    #[Test]
    public function aLegacyPrefixWithMoreThanOneEntryIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Map object that contains 1 item.');
        NetworkAddressPrefixTag::create(MapObject::create());
    }
}
