<?php

declare(strict_types=1);

namespace CBOR\Test\Diagnostic;

use CBOR\ByteStringObject;
use CBOR\Diagnostic\Schema;
use CBOR\Diagnostic\Type;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\OtherObject\NullObject;
use CBOR\Tag\GenericTag;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The subset of CDDL a type expression is read with.
 *
 * @internal
 */
final class TypeTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, string|null}>
     */
    public static function expressions(): iterable
    {
        yield 'a production name' => ['header_map', Type::NAME, 'header_map'];
        yield 'a name with dots and dashes' => ['alg-id.v2', Type::NAME, 'alg-id.v2'];
        yield 'a prelude type' => ['bstr', Type::PRIMITIVE, 'bstr'];
        yield 'any' => ['any', Type::ANY, null];
        yield 'a prelude type with a control operator' => ['bstr .size 0', Type::PRIMITIVE, 'bstr'];
        yield 'a production name with a control operator reads as any' => ['label .within int', Type::ANY, null];
        yield 'a range reads as any' => ['0..23', Type::ANY, null];
        yield 'a text literal' => ['"Signature1"', Type::LITERAL, 'Signature1'];
        yield 'a text literal with an escaped quote' => ['"a\"b"', Type::LITERAL, 'a"b'];
        yield 'embedded CBOR' => ['bstr .cbor header_map', Type::EMBEDDED, null];
        yield 'an array of one or more' => ['[+ COSE_Signature]', Type::ARRAY_OF, null];
        yield 'an array of zero or more' => ['[* bstr]', Type::ARRAY_OF, null];
        yield 'a tag' => ['#6.18(COSE_Sign1)', Type::TAGGED, null];
        yield 'a choice' => ['bstr / nil', Type::CHOICE, null];
    }

    #[Test]
    #[DataProvider('expressions')]
    public function anExpressionIsReadIntoItsKind(string $expression, string $kind, ?string $value): void
    {
        $type = Type::parse($expression);

        static::assertSame($kind, $type->getKind());
        static::assertSame($value, $type->getValue());
    }

    #[Test]
    public function theInnerTypesAreKept(): void
    {
        $embedded = Type::parse('bstr .cbor header_map');
        $array = Type::parse('[+ bstr .cbor Receipt]');
        $tagged = Type::parse('#6.18(COSE_Sign1)');
        $choice = Type::parse('COSE_Countersignature / [+ COSE_Countersignature] / #6.19(COSE_Countersignature)');

        static::assertSame('header_map', $embedded->getInner()?->getValue());
        static::assertSame(Type::EMBEDDED, $array->getInner()?->getKind());
        static::assertSame(18, $tagged->getTagNumber());
        static::assertSame('COSE_Sign1', $tagged->getInner()?->getValue());
        static::assertCount(3, $choice->getAlternatives());
        static::assertSame(Type::NAME, $choice->getAlternatives()[0]->getKind());
        static::assertSame(Type::ARRAY_OF, $choice->getAlternatives()[1]->getKind());
        static::assertSame(Type::TAGGED, $choice->getAlternatives()[2]->getKind());
    }

    /**
     * A "/" inside brackets or quotes is not a choice: "[+ bstr / nil]" is one array type, "\"a/b\"" one literal.
     */
    #[Test]
    public function aSlashInsideBracketsOrQuotesDoesNotSplit(): void
    {
        $array = Type::parse('[+ bstr / nil]');
        $literal = Type::parse('"a/b"');

        static::assertSame(Type::ARRAY_OF, $array->getKind());
        static::assertSame(Type::CHOICE, $array->getInner()?->getKind());
        static::assertSame(Type::LITERAL, $literal->getKind());
        static::assertSame('a/b', $literal->getValue());
    }

    #[Test]
    public function anEmptyExpressionIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A type expression cannot be empty.');

        Type::parse('bstr / ');
    }

    #[Test]
    public function anUnbalancedExpressionIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unbalanced type expression "[+ bstr".');

        Type::parse('[+ bstr');
    }

    /**
     * What each kind accepts, looking through tags for every kind but a tag itself.
     */
    #[Test]
    public function acceptanceFollowsTheKind(): void
    {
        $schema = Schema::create()
            ->addArray('Sig', [
                'protected' => 'bstr',
                'rest' => 'any',
            ])
            ->addMap('hdr', [])
            ->addRegistry('alg', []);
        $bytes = ByteStringObject::create('x');
        $text = TextStringObject::create('Signature1');
        $int = NegativeIntegerObject::create(-7);
        $list = ListObject::create([$bytes]);
        $map = MapObject::create();
        $tagged = GenericTag::createFromLoadedData(19, null, $list);

        static::assertTrue(Type::parse('any')->accepts($map, $schema, 2));
        static::assertTrue(Type::parse('bstr')->accepts($bytes, $schema, 2));
        static::assertFalse(Type::parse('bstr')->accepts($text, $schema, 2));
        static::assertTrue(Type::parse('nil')->accepts(NullObject::create(), $schema, 2));
        static::assertTrue(Type::parse('int')->accepts($int, $schema, 2));
        static::assertFalse(Type::parse('uint')->accepts($int, $schema, 2));
        static::assertTrue(Type::parse('"Signature1"')->accepts($text, $schema, 2));
        static::assertFalse(Type::parse('"Signature"')->accepts($text, $schema, 2));
        static::assertTrue(Type::parse('bstr .cbor hdr')->accepts($bytes, $schema, 2));
        static::assertTrue(Type::parse('hdr')->accepts($map, $schema, 2));
        static::assertFalse(Type::parse('hdr')->accepts($list, $schema, 2));
        static::assertTrue(Type::parse('alg')->accepts($int, $schema, 2));
        static::assertFalse(Type::parse('alg')->accepts($text, $schema, 2));
        // An array production looks at its first field; an undeclared name accepts anything.
        static::assertTrue(Type::parse('Sig')->accepts($list, $schema, 2));
        static::assertFalse(Type::parse('Sig')->accepts(ListObject::create([$list]), $schema, 2));
        static::assertTrue(Type::parse('Sig')->accepts(ListObject::create([$list]), $schema, 0));
        static::assertTrue(Type::parse('Undeclared')->accepts($text, $schema, 2));
        static::assertTrue(Type::parse('[+ Sig]')->accepts(ListObject::create([$list]), $schema, 2));
        static::assertFalse(Type::parse('[+ Sig]')->accepts($list, $schema, 2));
        static::assertTrue(Type::parse('[+ Sig]')->accepts(ListObject::create(), $schema, 2));
        // Through a tag, except for a tag type, which wants its number.
        static::assertTrue(Type::parse('Sig')->accepts($tagged, $schema, 2));
        static::assertTrue(Type::parse('#6.19(Sig)')->accepts($tagged, $schema, 2));
        static::assertFalse(Type::parse('#6.18(Sig)')->accepts($tagged, $schema, 2));
        static::assertFalse(Type::parse('#6.19(Sig)')->accepts($list, $schema, 2));
    }

    #[Test]
    public function aChoiceResolvesToTheFirstAlternativeThatAccepts(): void
    {
        $schema = Schema::create()
            ->addArray('Sig', [
                'protected' => 'bstr',
            ]);
        $single = ListObject::create([ByteStringObject::create('')]);
        $several = ListObject::create([$single]);
        $choice = Type::parse('Sig / [+ Sig]');

        static::assertSame(Type::NAME, $choice->resolve($single, $schema)?->getKind());
        static::assertSame(Type::ARRAY_OF, $choice->resolve($several, $schema)?->getKind());
        static::assertNull($choice->resolve(UnsignedIntegerObject::create(1), $schema));
        // A type that is not a choice resolves to itself, or to nothing.
        $sig = Type::parse('Sig');
        static::assertSame($sig, $sig->resolve($single, $schema));
        static::assertNull($sig->resolve($several, $schema));
    }
}
