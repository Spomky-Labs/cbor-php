<?php

declare(strict_types=1);

namespace CBOR\Test\Diagnostic;

use CBOR\ByteStringObject;
use CBOR\Diagnostic\DiagnosticNotation;
use CBOR\ListObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\StringStream;
use CBOR\Test\CBORTestCase;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use function hex2bin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The bare notation, without a schema: RFC 8949 section 8, checked against the examples of Appendix A.
 *
 * @internal
 */
final class DiagnosticNotationTest extends CBORTestCase
{
    /**
     * RFC 8949 Appendix A, Table 7 and Table 8: the encoded form and the notation the RFC prints next to it.
     *
     * Two departures, both in spelling only. The table shows the bignums as the integers they stand for
     * (18446744073709551616) while the notation proper writes the tag around its byte string, 2(h'...'), which is
     * what section 8 defines and what this encoder does. And 0.00006103515625 comes out as 6.103515625e-5, the same
     * number in the shortest form PHP prints; the table itself mixes the two forms (1.0e+300, 100000.0).
     *
     * @return iterable<string, array{string, string}>
     */
    public static function appendixA(): iterable
    {
        yield '0' => ['00', '0'];
        yield '1' => ['01', '1'];
        yield '10' => ['0a', '10'];
        yield '23' => ['17', '23'];
        yield '24' => ['1818', '24'];
        yield '25' => ['1819', '25'];
        yield '100' => ['1864', '100'];
        yield '1000' => ['1903e8', '1000'];
        yield '1000000' => ['1a000f4240', '1000000'];
        yield '1000000000000' => ['1b000000e8d4a51000', '1000000000000'];
        yield '18446744073709551615' => ['1bffffffffffffffff', '18446744073709551615'];
        yield '2(h\'010000000000000000\')' => ['c249010000000000000000', "2(h'010000000000000000')"];
        yield '-18446744073709551616' => ['3bffffffffffffffff', '-18446744073709551616'];
        yield '3(h\'010000000000000000\')' => ['c349010000000000000000', "3(h'010000000000000000')"];
        yield '-1' => ['20', '-1'];
        yield '-10' => ['29', '-10'];
        yield '-100' => ['3863', '-100'];
        yield '-1000' => ['3903e7', '-1000'];
        yield '0.0' => ['f90000', '0.0'];
        yield '-0.0' => ['f98000', '-0.0'];
        yield '1.0' => ['f93c00', '1.0'];
        yield '1.1' => ['fb3ff199999999999a', '1.1'];
        yield '1.5' => ['f93e00', '1.5'];
        yield '65504.0' => ['f97bff', '65504.0'];
        yield '100000.0' => ['fa47c35000', '100000.0'];
        yield '3.4028234663852886e+38' => ['fa7f7fffff', '3.4028234663852886e+38'];
        yield '1.0e+300' => ['fb7e37e43c8800759c', '1.0e+300'];
        yield '5.960464477539063e-8' => ['f90001', '5.960464477539063e-8'];
        yield '0.00006103515625' => ['f90400', '6.103515625e-5'];
        yield '-4.0' => ['f9c400', '-4.0'];
        yield '-4.1' => ['fbc010666666666666', '-4.1'];
        yield 'Infinity (half)' => ['f97c00', 'Infinity'];
        yield 'NaN (half)' => ['f97e00', 'NaN'];
        yield '-Infinity (half)' => ['f9fc00', '-Infinity'];
        yield 'Infinity (single)' => ['fa7f800000', 'Infinity'];
        yield 'NaN (single)' => ['fa7fc00000', 'NaN'];
        yield '-Infinity (single)' => ['faff800000', '-Infinity'];
        yield 'Infinity (double)' => ['fb7ff0000000000000', 'Infinity'];
        yield 'NaN (double)' => ['fb7ff8000000000000', 'NaN'];
        yield '-Infinity (double)' => ['fbfff0000000000000', '-Infinity'];
        yield 'false' => ['f4', 'false'];
        yield 'true' => ['f5', 'true'];
        yield 'null' => ['f6', 'null'];
        yield 'undefined' => ['f7', 'undefined'];
        yield 'simple(16)' => ['f0', 'simple(16)'];
        yield 'simple(255)' => ['f8ff', 'simple(255)'];
        yield '0("2013-03-21T20:04:00Z")' => ['c074323031332d30332d32315432303a30343a30305a', '0("2013-03-21T20:04:00Z")'];
        yield '1(1363896240)' => ['c11a514b67b0', '1(1363896240)'];
        yield '1(1363896240.5)' => ['c1fb41d452d9ec200000', '1(1363896240.5)'];
        yield '23(h\'01020304\')' => ['d74401020304', "23(h'01020304')"];
        yield '24(h\'6449455446\')' => ['d818456449455446', "24(h'6449455446')"];
        yield '32("http://www.example.com")' => ['d82076687474703a2f2f7777772e6578616d706c652e636f6d', '32("http://www.example.com")'];
        yield 'h\'\'' => ['40', "h''"];
        yield 'h\'01020304\'' => ['4401020304', "h'01020304'"];
        yield '""' => ['60', '""'];
        yield '"a"' => ['6161', '"a"'];
        yield '"IETF"' => ['6449455446', '"IETF"'];
        yield '"\\"\\\\"' => ['62225c', '"\\"\\\\"'];
        yield '"ü"' => ['62c3bc', '"ü"'];
        yield '"水"' => ['63e6b0b4', '"水"'];
        yield '"𐅑"' => ['64f0908591', '"𐅑"'];
        yield '[]' => ['80', '[]'];
        yield '[1, 2, 3]' => ['83010203', '[1, 2, 3]'];
        yield '[1, [2, 3], [4, 5]]' => ['8301820203820405', '[1, [2, 3], [4, 5]]'];
        yield '[1, 2, ..., 25]' => ['98190102030405060708090a0b0c0d0e0f101112131415161718181819', '[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25]'];
        yield '{}' => ['a0', '{}'];
        yield '{1: 2, 3: 4}' => ['a201020304', '{1: 2, 3: 4}'];
        yield '{"a": 1, "b": [2, 3]}' => ['a26161016162820203', '{"a": 1, "b": [2, 3]}'];
        yield '["a", {"b": "c"}]' => ['826161a161626163', '["a", {"b": "c"}]'];
        yield '{"a": "A", ..., "e": "E"}' => ['a56161614161626142616361436164614461656145', '{"a": "A", "b": "B", "c": "C", "d": "D", "e": "E"}'];
        yield '(_ h\'0102\', h\'030405\')' => ['5f42010243030405ff', "(_ h'0102', h'030405')"];
        yield '(_ "strea", "ming")' => ['7f657374726561646d696e67ff', '(_ "strea", "ming")'];
        yield '[_ ]' => ['9fff', '[_ ]'];
        yield '[_ 1, [2, 3], [_ 4, 5]]' => ['9f018202039f0405ffff', '[_ 1, [2, 3], [_ 4, 5]]'];
        yield '[_ 1, [2, 3], [4, 5]]' => ['9f01820203820405ff', '[_ 1, [2, 3], [4, 5]]'];
        yield '[1, [2, 3], [_ 4, 5]]' => ['83018202039f0405ff', '[1, [2, 3], [_ 4, 5]]'];
        yield '[1, [_ 2, 3], [4, 5]]' => ['83019f0203ff820405', '[1, [_ 2, 3], [4, 5]]'];
        yield '[_ 1, 2, ..., 25]' => ['9f0102030405060708090a0b0c0d0e0f101112131415161718181819ff', '[_ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25]'];
        yield '{_ "a": 1, "b": [_ 2, 3]}' => ['bf61610161629f0203ffff', '{_ "a": 1, "b": [_ 2, 3]}'];
        yield '["a", {_ "b": "c"}]' => ['826161bf61626163ff', '["a", {_ "b": "c"}]'];
        yield '{_ "Fun": true, "Amt": -2}' => ['bf6346756ef563416d7421ff', '{_ "Fun": true, "Amt": -2}'];
    }

    #[Test]
    #[DataProvider('appendixA')]
    public function theNotationOfAppendixAIsReproduced(string $hex, string $expected): void
    {
        // Given
        $item = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($hex)));

        // When
        $notation = DiagnosticNotation::create()
            ->singleLine()
            ->encode($item);

        // Then
        static::assertSame($expected, $notation);
    }

    /**
     * The same document, one item per line: what a reader gets by default.
     */
    #[Test]
    public function itemsGoOnTheirOwnLinesByDefault(): void
    {
        // Given: [1, [2, 3], {"a": h''}]
        $item = ListObject::create([
            UnsignedIntegerObject::create(1),
            ListObject::create([UnsignedIntegerObject::create(2), UnsignedIntegerObject::create(3)]),
            MapObject::create([MapItem::create(TextStringObject::create('a'), ByteStringObject::create(''))]),
        ]);

        // When
        $notation = DiagnosticNotation::create()
            ->encode($item);

        // Then
        static::assertSame(<<<'EDN'
            [
              1,
              [
                2,
                3
              ],
              {
                "a": h''
              }
            ]
            EDN
            , $notation);
    }

    #[Test]
    public function theIndentIsConfigurable(): void
    {
        // Given
        $item = ListObject::create([UnsignedIntegerObject::create(1)]);

        // When
        $notation = DiagnosticNotation::create()
            ->withIndent("\t")
            ->encode($item);

        // Then
        static::assertSame("[\n\t1\n]", $notation);
    }

    /**
     * RFC 8949 section 8.1 allows 'text' for a byte string whose bytes are text, and RFC 9052 Appendix C writes its
     * payloads that way. Once asked for, bytes that are not printable, or that contain the quote itself, stay
     * hexadecimal.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function byteStrings(): iterable
    {
        yield 'printable ASCII' => ['This is the content.', "'This is the content.'"];
        yield 'printable UTF-8' => ['(｡◕‿◕｡)', "'(｡◕‿◕｡)'"];
        yield 'empty' => ['', "h''"];
        yield 'a control character' => ["a\x00b", "h'610062'"];
        yield 'a newline' => ["a\nb", "h'610a62'"];
        yield 'invalid UTF-8' => ["\xff\xfe", "h'fffe'"];
        yield 'a quote' => ["it's", "h'69742773'"];
        yield 'a backslash' => ['a\\b', "h'615c62'"];
    }

    #[Test]
    #[DataProvider('byteStrings')]
    public function aByteStringThatReadsAsTextCanBeQuotedAsText(string $bytes, string $expected): void
    {
        static::assertSame($expected, DiagnosticNotation::create()->withBytesAsText()->encode(ByteStringObject::create($bytes)));
    }

    /**
     * Not by default: h'6449455446' of Appendix A is bytes that happen to spell "dIETF".
     */
    #[Test]
    public function byteStringsAreHexadecimalByDefault(): void
    {
        static::assertSame("h'6449455446'", DiagnosticNotation::create()->encode(ByteStringObject::create('dIETF')));
    }

    /**
     * Without a schema there is nothing to quote: the rendering is the notation alone.
     */
    #[Test]
    public function aRenderingWithoutASchemaCarriesNoCddl(): void
    {
        // Given
        $item = UnsignedIntegerObject::create(1);

        // When
        $rendering = DiagnosticNotation::create()
            ->render($item);

        // Then
        static::assertSame('1', $rendering->getNotation());
        static::assertSame([], $rendering->getCddl());
        static::assertSame('1', (string) $rendering);
    }

    /**
     * The encoder is immutable: an option returns a new one and leaves the original as it was.
     */
    #[Test]
    public function optionsDoNotChangeTheEncoderTheyAreCalledOn(): void
    {
        // Given
        $multiline = DiagnosticNotation::create();
        $item = ListObject::create([UnsignedIntegerObject::create(1)]);

        // When
        $singleLine = $multiline->singleLine();

        // Then
        static::assertSame('[1]', $singleLine->encode($item));
        static::assertSame("[\n  1\n]", $multiline->encode($item));
    }
}
