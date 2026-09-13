<?php

declare(strict_types=1);

namespace CBOR\Test\Diagnostic;

use CBOR\ByteStringObject;
use CBOR\Diagnostic\DiagnosticNotation;
use CBOR\Diagnostic\Schema;
use CBOR\ListObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\StringStream;
use CBOR\Tag\GenericTag;
use CBOR\Test\CBORTestCase;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use function hex2bin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use function str_repeat;

/**
 * The notation with a schema: what the appendices of RFC 9052 and RFC 9338 do by hand, done from a few
 * declarations. The COSE productions here are the fixture; the library that implements COSE owns the real ones.
 *
 * @internal
 */
final class SchemaTest extends CBORTestCase
{
    /**
     * RFC 9052 Appendix C.2.1: a COSE_Sign1 over "This is the content.", ES256, kid '11'.
     */
    private const RFC_9052_C_2_1 = 'd28443a10126a10442313154546869732069732074686520636f6e74656e742e5840'
        . '8eb33e4ca31d1c465ab05aac34cc6b23d58fef5c083106c4d25a91aef0b0117e'
        . '2af9a291aa32e14ab834dc56ed2a223444547e01f11d3b0916e5a4c345cacb36';

    /**
     * Enough of RFC 9052 and RFC 9338 to exercise every kind of declaration: a tag, array productions (one recursive),
     * a map, a registry, an embedded byte string, an array of productions, a choice and a text-literal discriminator.
     */
    private static function cose(): Schema
    {
        return Schema::create()
            ->addTag(18, 'COSE_Sign1', 'COSE_Sign1_Tagged = #6.18(COSE_Sign1)')
            ->addArray('COSE_Sign1', [
                'protected' => 'bstr .cbor header_map',
                'unprotected' => 'header_map',
                'payload' => 'bstr / nil',
                'signature' => 'bstr',
            ], 'COSE_Sign1 = [ Headers, payload : bstr / nil, signature : bstr ]')
            ->addTag(96, 'COSE_Encrypt')
            ->addArray('COSE_Encrypt', [
                'protected' => 'bstr .cbor header_map',
                'unprotected' => 'header_map',
                'ciphertext' => 'bstr / nil',
                'recipients' => '[+ COSE_recipient]',
            ])
            ->addArray('COSE_recipient', [
                'protected' => 'bstr .cbor header_map',
                'unprotected' => 'header_map',
                'ciphertext' => 'bstr / nil',
                'recipients' => '[+ COSE_recipient]',
            ], 'COSE_recipient = [ Headers, ciphertext : bstr / nil, ? recipients : [+COSE_recipient] ]')
            ->addArray('COSE_Countersignature', [
                'protected' => 'bstr .cbor header_map',
                'unprotected' => 'header_map',
                'signature' => 'bstr',
            ], 'COSE_Countersignature = COSE_Signature')
            ->addArray('Sig_structure', [
                'context' => '"Signature1"',
                'body_protected' => 'bstr .cbor header_map',
                'external_aad' => 'bstr',
                'payload' => 'bstr',
            ], 'Sig_structure = [ context : "Signature" / "Signature1", body_protected : empty_or_serialized_map, ? sign_protected : empty_or_serialized_map, external_aad : bstr, payload : bstr ]')
            ->addMap('header_map', [
                1 => ['alg', 'alg-id'],
                4 => 'kid',
                11 => ['Countersignature version 2', 'COSE_Countersignature / [+ COSE_Countersignature]'],
                -1 => ['ephemeral key', 'COSE_Key'],
            ], 'header_map = { * label => values }')
            ->addMap('COSE_Key', [
                1 => ['kty', 'kty-id'],
                -1 => 'crv',
                -2 => 'x',
            ], 'COSE_Key = { 1 => tstr / int, * label => values }')
            ->addRegistry('alg-id', [
                -7 => 'ES256',
                1 => 'A128GCM',
            ])
            ->addRegistry('kty-id', [
                2 => 'EC2',
            ]);
    }

    /**
     * The RFC 9052 Appendix C.2.1 message, rendered the way the appendix prints it.
     */
    #[Test]
    public function aTaggedMessageIsReadAsTheProductionItsTagDeclares(): void
    {
        // Given
        $message = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin(self::RFC_9052_C_2_1)));

        // When
        $rendering = DiagnosticNotation::create(self::cose())
            ->withBytesAsText()
            ->render($message);

        // Then
        static::assertSame(<<<'EDN'
            18([
              / protected h'a10126' / << {
                / alg / 1: -7 / ES256 /
              } >>,
              / unprotected / {
                / kid / 4: '11'
              },
              / payload / 'This is the content.',
              / signature / h'8eb33e4ca31d1c465ab05aac34cc6b23d58fef5c083106c4d25a91aef0b0117e2af9a291aa32e14ab834dc56ed2a223444547e01f11d3b0916e5a4c345cacb36'
            ])
            EDN
            , $rendering->getNotation());
        // The productions met, each once, in order of first use; the ones declared without a CDDL are left out.
        static::assertSame([
            'COSE_Sign1_Tagged = #6.18(COSE_Sign1)',
            'COSE_Sign1 = [ Headers, payload : bstr / nil, signature : bstr ]',
            'header_map = { * label => values }',
        ], $rendering->getCddl());
        static::assertStringStartsWith("; COSE_Sign1_Tagged = #6.18(COSE_Sign1)\n; COSE_Sign1 = [", (string) $rendering);
    }

    /**
     * RFC 9338 section 2: label 11 holds "COSE_Countersignature / [+ COSE_Countersignature]". The single form opens
     * with a byte string, the array form with a list; the choice tells them apart and names what it picked, since
     * nothing on the wire does.
     */
    #[Test]
    public function aChoiceIsResolvedOnTheShapeOfTheItemAndNamed(): void
    {
        // Given: a countersignature, and a message carrying one, then two, under label 11
        $countersignature = static fn (string $kid): ListObject => ListObject::create([
            ByteStringObject::create((string) hex2bin('a10126')),
            MapObject::create([MapItem::create(UnsignedIntegerObject::create(4), ByteStringObject::create($kid))]),
            ByteStringObject::create(str_repeat("\x00", 4)),
        ]);
        $message = static fn (ListObject $label11): ListObject => ListObject::create([
            ByteStringObject::create(''),
            MapObject::create([MapItem::create(UnsignedIntegerObject::create(11), $label11)]),
            ByteStringObject::create('x'),
            ByteStringObject::create(str_repeat("\x00", 4)),
        ]);
        $notation = DiagnosticNotation::create(self::cose())
            ->withBytesAsText();

        // When
        $single = $notation->encode($message($countersignature('a')), 'COSE_Sign1');
        $several = $notation->encode($message(ListObject::create([$countersignature('a'), $countersignature('b')])), 'COSE_Sign1');

        // Then
        static::assertSame(<<<'EDN'
            [
              / protected / h'',
              / unprotected / {
                / Countersignature version 2 / 11: / COSE_Countersignature / [
                  / protected h'a10126' / << {
                    / alg / 1: -7 / ES256 /
                  } >>,
                  / unprotected / {
                    / kid / 4: 'a'
                  },
                  / signature / h'00000000'
                ]
              },
              / payload / 'x',
              / signature / h'00000000'
            ]
            EDN
            , $single);
        static::assertStringContainsString(<<<'EDN'
                / Countersignature version 2 / 11: [
                  / COSE_Countersignature / [
                    / protected h'a10126' / << {
                      / alg / 1: -7 / ES256 /
                    } >>,
                    / unprotected / {
                      / kid / 4: 'a'
                    },
                    / signature / h'00000000'
                  ],
                  / COSE_Countersignature / [
            EDN
            , $several);
    }

    /**
     * RFC 9338 section 3.1: the countersignature "can be encoded as either tagged or untagged". A type that is not
     * itself a tag applies through one, and the tag number is written as it is.
     */
    #[Test]
    public function aTypeAppliesThroughATag(): void
    {
        // Given: 19([h'', {}, h'00'])
        $tagged = GenericTag::createFromLoadedData(19, null, ListObject::create([
            ByteStringObject::create(''),
            MapObject::create(),
            ByteStringObject::create("\x00"),
        ]));

        // When
        $notation = DiagnosticNotation::create(self::cose())
            ->singleLine()
            ->encode($tagged, 'COSE_Countersignature');

        // Then
        static::assertSame("19([/ protected / h'', / unprotected / {}, / signature / h'00'])", $notation);
    }

    /**
     * A recipient may carry recipients (RFC 9052 section 5.1): the production refers to itself, and each level is
     * named the same way. The ephemeral key under -1 is a map production with a registry of its own.
     */
    #[Test]
    public function aRecursiveProductionAndANestedMapAreRendered(): void
    {
        // Given
        $ephemeral = MapObject::create([
            MapItem::create(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2)),
            MapItem::create(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1)),
        ]);
        $inner = ListObject::create([
            ByteStringObject::create(''),
            MapObject::create([MapItem::create(NegativeIntegerObject::create(-1), $ephemeral)]),
            ByteStringObject::create("\x01"),
        ]);
        $outer = ListObject::create([
            ByteStringObject::create(''),
            MapObject::create(),
            ByteStringObject::create("\x02"),
            ListObject::create([$inner]),
        ]);
        $message = GenericTag::createFromLoadedData(24, "\x60", ListObject::create([
            ByteStringObject::create((string) hex2bin('a10101')),
            MapObject::create(),
            ByteStringObject::create("\x03"),
            ListObject::create([$outer]),
        ]));

        // When
        $notation = DiagnosticNotation::create(self::cose())
            ->encode($message);

        // Then
        static::assertSame(<<<'EDN'
            96([
              / protected h'a10101' / << {
                / alg / 1: 1 / A128GCM /
              } >>,
              / unprotected / {},
              / ciphertext / h'03',
              / recipients / [
                / COSE_recipient / [
                  / protected / h'',
                  / unprotected / {},
                  / ciphertext / h'02',
                  / recipients / [
                    / COSE_recipient / [
                      / protected / h'',
                      / unprotected / {
                        / ephemeral key / -1: {
                          / kty / 1: 2 / EC2 /,
                          / crv / -1: 1
                        }
                      },
                      / ciphertext / h'01'
                    ]
                  ]
                ]
              ]
            ])
            EDN
            , $notation);
    }

    /**
     * RFC 9052 section 4.4: nothing but its context string identifies a Sig_structure. An array production whose first
     * field is a text literal is recognized on it.
     */
    #[Test]
    public function aBareListIsRecognizedOnItsFirstTextLiteral(): void
    {
        // Given: the Sig_structure of the Appendix C.2.1 message
        $structure = ListObject::create([
            TextStringObject::create('Signature1'),
            ByteStringObject::create((string) hex2bin('a10126')),
            ByteStringObject::create(''),
            ByteStringObject::create('This is the content.'),
        ]);

        // When
        $rendering = DiagnosticNotation::create(self::cose())
            ->withBytesAsText()
            ->render($structure);

        // Then
        static::assertSame(<<<'EDN'
            [
              / context / "Signature1",
              / body_protected h'a10126' / << {
                / alg / 1: -7 / ES256 /
              } >>,
              / external_aad / h'',
              / payload / 'This is the content.'
            ]
            EDN
            , $rendering->getNotation());
        static::assertSame('Sig_structure = [ context : "Signature" / "Signature1", body_protected : empty_or_serialized_map, ? sign_protected : empty_or_serialized_map, external_aad : bstr, payload : bstr ]', $rendering->getCddl()[0]);
    }

    /**
     * A bare map says nothing about what it is; the caller does.
     */
    #[Test]
    public function aBareMapIsReadAsTheProductionTheCallerNames(): void
    {
        // Given: {1: 2, -1: 1, -2: h'0102'}, a COSE_Key, but also a map whose label 1 an header_map reads as "alg"
        $key = MapObject::create([
            MapItem::create(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2)),
            MapItem::create(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1)),
            MapItem::create(NegativeIntegerObject::create(-2), ByteStringObject::create("\x01\x02")),
        ]);
        $notation = DiagnosticNotation::create(self::cose())
            ->singleLine();

        // When
        $asKey = $notation->encode($key, 'COSE_Key');
        $asHeader = $notation->encode($key, 'header_map');
        $bare = $notation->encode($key);

        // Then
        static::assertSame("{/ kty / 1: 2 / EC2 /, / crv / -1: 1, / x / -2: h'0102'}", $asKey);
        static::assertSame("{/ alg / 1: 2, / ephemeral key / -1: 1, -2: h'0102'}", $asHeader);
        static::assertSame("{1: 2, -1: 1, -2: h'0102'}", $bare);
    }

    /**
     * ...with a production that exists.
     */
    #[Test]
    public function anUnknownProductionIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The production "COSE_Nope" is not declared in the schema.');

        DiagnosticNotation::create(self::cose())
            ->encode(MapObject::create(), 'COSE_Nope');
    }

    /**
     * What a declaration says and the item is may differ: a value that is not what the type expects is written as
     * it is, a byte string that does not decode stays a byte string, an unregistered value has no name.
     */
    #[Test]
    public function whatDoesNotMatchTheSchemaIsWrittenAsItIs(): void
    {
        // Given: a protected header that is not CBOR, an "alg" that is a text string, an unregistered algorithm, a
        // fifth item the production does not declare
        $message = ListObject::create([
            ByteStringObject::create("\xff"),
            MapObject::create([
                MapItem::create(UnsignedIntegerObject::create(1), TextStringObject::create('ES256')),
                MapItem::create(UnsignedIntegerObject::create(4), NegativeIntegerObject::create(-35)),
                MapItem::create(TextStringObject::create('x'), UnsignedIntegerObject::create(1)),
            ]),
            ByteStringObject::create('p'),
            ByteStringObject::create('s'),
            UnsignedIntegerObject::create(5),
        ]);

        // When
        $notation = DiagnosticNotation::create(self::cose())
            ->singleLine()
            ->withBytesAsText()
            ->encode($message, 'COSE_Sign1');

        // Then
        static::assertSame(
            "[/ protected / h'ff', / unprotected / {/ alg / 1: \"ES256\", / kid / 4: -35, \"x\": 1}, / payload / 'p', / signature / 's', 5]",
            $notation
        );
    }

    #[Test]
    public function anUnregisteredValueOfARegistryHasNoName(): void
    {
        // Given
        $header = MapObject::create([MapItem::create(UnsignedIntegerObject::create(1), NegativeIntegerObject::create(-35))]);

        // When
        $notation = DiagnosticNotation::create(self::cose())
            ->singleLine()
            ->encode($header, 'header_map');

        // Then
        static::assertSame('{/ alg / 1: -35}', $notation);
    }

    /**
     * A tag the schema does not declare is written with its number, its content read as anything.
     */
    #[Test]
    public function anUndeclaredTagIsWrittenAsItIs(): void
    {
        // Given
        $tagged = GenericTag::createFromLoadedData(1, null, UnsignedIntegerObject::create(1363896240));

        // When
        $notation = DiagnosticNotation::create(self::cose())
            ->encode($tagged);

        // Then
        static::assertSame('1(1363896240)', $notation);
    }

    /**
     * Declarations are checked when they are made, not when a document happens to reach them.
     */
    #[Test]
    public function aProductionCannotBeDeclaredTwice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The production "alg-id" is already declared.');

        Schema::create()
            ->addRegistry('alg-id', [])
            ->addArray('alg-id', []);
    }

    #[Test]
    public function theFieldsOfAnArrayMapNamesToTypeExpressions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The fields of "X" shall map a field name to a type expression.');

        Schema::create()
            ->addArray('X', ['bstr', 'bstr']);
    }

    #[Test]
    public function theEntriesOfAMapMapLabelsToNamesOrPairs(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entries of "X" shall map a label to a name, or to a [name, type expression] pair.');

        Schema::create()
            ->addMap('X', [
                1 => ['alg'],
            ]);
    }

    #[Test]
    public function aRegistryMapsIntegersToNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The registry "X" shall map integers to names.');

        Schema::create()
            ->addRegistry('X', [
                'a' => 'b',
            ]);
    }

    #[Test]
    public function aTagNumberIsNotNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A tag number is a non-negative integer.');

        Schema::create()
            ->addTag(-1, 'X');
    }

    /**
     * Text labels are declared the way integer labels are: RFC 9052 section 3 allows both.
     */
    #[Test]
    public function aMapProductionCanDeclareTextLabels(): void
    {
        // Given
        $schema = Schema::create()
            ->addMap('claims', [
                'iss' => ['issuer', 'tstr'],
                2 => 'sub',
            ]);
        $claims = MapObject::create([
            MapItem::create(TextStringObject::create('iss'), TextStringObject::create('me')),
            MapItem::create(UnsignedIntegerObject::create(2), TextStringObject::create('you')),
        ]);

        // When
        $notation = DiagnosticNotation::create($schema)
            ->singleLine()
            ->encode($claims, 'claims');

        // Then
        static::assertSame('{/ issuer / "iss": "me", / sub / 2: "you"}', $notation);
    }
}
