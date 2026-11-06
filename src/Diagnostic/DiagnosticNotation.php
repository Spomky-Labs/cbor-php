<?php

declare(strict_types=1);

namespace CBOR\Diagnostic;

use function array_key_last;
use function bin2hex;
use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\Decoder;
use CBOR\DecoderInterface;
use CBOR\IndefiniteLengthByteStringObject;
use CBOR\IndefiniteLengthListObject;
use CBOR\IndefiniteLengthMapObject;
use CBOR\IndefiniteLengthTextStringObject;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\Normalizable;
use CBOR\OtherObject;
use CBOR\OtherObject\DoublePrecisionFloatObject;
use CBOR\OtherObject\FalseObject;
use CBOR\OtherObject\HalfPrecisionFloatObject;
use CBOR\OtherObject\NullObject;
use CBOR\OtherObject\SimpleObject;
use CBOR\OtherObject\SinglePrecisionFloatObject;
use CBOR\OtherObject\TrueObject;
use CBOR\OtherObject\UndefinedObject;
use CBOR\StringStream;
use CBOR\Tag;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use CBOR\Utils;
use function count;
use function implode;
use function in_array;
use InvalidArgumentException;
use function is_float;
use function is_infinite;
use function is_nan;
use function json_encode;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use Throwable;

/**
 * Writes a CBOR item in diagnostic notation, RFC 8949 section 8.
 *
 * On its own the notation is what the RFC test vectors use: `[1, [2, 3], [4, 5]]`, `h'01020304'`, `1(1363896240)`,
 * `[_ 1, 2]` for an indefinite-length array, `(_ h'0102', h'030405')` for an indefinite-length string. Given a
 * {@see Schema}, it also does what the appendices of the COSE RFCs do by hand: it names every item after the CDDL
 * production it instantiates, opens the byte strings that carry CBOR, and spells the value of a registry:
 *
 * ```
 * 18([
 *   / protected h'a10126' / << {
 *     / alg / 1: -7 / ES256 /
 *   } >>,
 *   / unprotected / {
 *     / kid / 4: '11'
 *   },
 *   / payload / h'546869732069732074686520636f6e74656e742e',
 *   / signature / h'8eb33e4c...'
 * ])
 * ```
 *
 * A byte string is written `h'...'`; withBytesAsText() writes `'...'` when its bytes read as printable text, the
 * shorthand RFC 8949 section 8.1 allows and RFC 9052 Appendix C uses for its payloads. One item per line by
 * default, `singleLine()` for the form of the test vectors.
 *
 * @see \CBOR\Test\Diagnostic\DiagnosticNotationTest
 * @see https://www.rfc-editor.org/rfc/rfc8949#section-8
 */
final class DiagnosticNotation
{
    private Schema $schema;

    private DecoderInterface $decoder;

    private bool $multiline = true;

    private bool $bytesAsText = false;

    private string $indent = '  ';

    /**
     * @var list<string> the CDDL of the productions used so far, each once, in order of first use
     */
    private array $cddl = [];

    private function __construct(?Schema $schema, ?DecoderInterface $decoder)
    {
        $this->schema = $schema ?? Schema::create();
        $this->decoder = $decoder ?? Decoder::create();
    }

    /**
     * @param Schema|null           $schema  the productions to annotate with; none for the bare notation
     * @param DecoderInterface|null $decoder the decoder for the byte strings a schema says carry CBOR
     */
    public static function create(?Schema $schema = null, ?DecoderInterface $decoder = null): self
    {
        return new self($schema, $decoder);
    }

    /**
     * Everything on one line, as the RFC 8949 test vectors write it.
     */
    public function singleLine(): self
    {
        $clone = clone $this;
        $clone->multiline = false;

        return $clone;
    }

    /**
     * A byte string whose bytes read as printable text written `'text'`, the shorthand of RFC 8949 section 8.1 that
     * RFC 9052 Appendix C uses for its payloads. Off by default: bytes that happen to be printable, the h'6449455446'
     * of RFC 8949 Appendix A for one, would be misread as text.
     */
    public function withBytesAsText(): self
    {
        $clone = clone $this;
        $clone->bytesAsText = true;

        return $clone;
    }

    public function withIndent(string $indent): self
    {
        $clone = clone $this;
        $clone->indent = $indent;

        return $clone;
    }

    /**
     * The notation alone.
     *
     * @param string|null $as the production the item is an instance of, when its tag does not say
     */
    public function encode(CBORObject $item, ?string $as = null): string
    {
        return $this->render($item, $as)
            ->getNotation();
    }

    /**
     * The notation, with the CDDL of the productions the item instantiates.
     *
     * @param string|null $as the production the item is an instance of, when its tag does not say
     */
    public function render(CBORObject $item, ?string $as = null): Rendering
    {
        $renderer = clone $this;
        $renderer->cddl = [];
        if ($as !== null && ! $renderer->schema->has($as)) {
            throw new InvalidArgumentException(sprintf('The production "%s" is not declared in the schema.', $as));
        }
        $lines = $renderer->item($item, $as === null ? Type::any() : Type::name($as), false);

        return new Rendering(implode("\n", $lines), $renderer->cddl);
    }

    /**
     * An item read as a type.
     *
     * @param bool $named whether to name the production the item turns out to be, which is done where nothing else
     *                    says it: the items of an array of productions, the winner of a choice
     * @return list<string>
     */
    private function item(CBORObject $item, Type $type, bool $named): array
    {
        if ($type->getKind() === Type::CHOICE) {
            $resolved = $type->resolve($item, $this->schema);

            return $this->item($item, $resolved ?? Type::any(), true);
        }
        if ($item instanceof Tag) {
            return $this->tag($item, $type);
        }
        switch ($type->getKind()) {
            case Type::EMBEDDED:
                return $this->embedded($item, $type);
            case Type::ARRAY_OF:
                if (self::isList($item)) {
                    return $this->list($item, static fn (CBORObject $child): Type => $type->getInner() ?? Type::any(), true);
                }
                break;
            case Type::NAME:
                $name = (string) $type->getValue();
                if ($this->schema->isArray($name) && self::isList($item)) {
                    return $this->production($item, $name, $named);
                }
                if ($this->schema->isMap($name) && self::isMap($item)) {
                    $this->quote($name);

                    return $this->map($item, $name);
                }
                if ($this->schema->isRegistry($name) && ($item instanceof UnsignedIntegerObject || $item instanceof NegativeIntegerObject)) {
                    $this->quote($name);
                    $registered = $this->schema->getRegistryName($name, (int) $item->normalize());

                    return [$this->scalar($item) . ($registered === null ? '' : ' / ' . $registered . ' /')];
                }
                break;
        }
        if (self::isList($item)) {
            $literals = $this->schema->getProductionsByFirstLiteral();
            $first = count($item) > 0 ? $item->get(0) : null;
            if ($first instanceof TextStringObject && isset($literals[$first->getValue()])) {
                return $this->production($item, $literals[$first->getValue()], $named);
            }

            return $this->list($item, static fn (CBORObject $child): Type => Type::any(), false);
        }
        if (self::isMap($item)) {
            return $this->map($item, null);
        }
        if ($item instanceof IndefiniteLengthByteStringObject || $item instanceof IndefiniteLengthTextStringObject) {
            return $this->chunks($item);
        }

        return [$this->scalar($item)];
    }

    /**
     * @return list<string>
     */
    private function tag(Tag $item, Type $type): array
    {
        $number = $item->getTagNumber();
        $inner = $type;
        if ($type->getKind() === Type::TAGGED) {
            $inner = $type->getTagNumber() === $number ? ($type->getInner() ?? Type::any()) : Type::any();
        } elseif ($type->getKind() === Type::ANY) {
            $production = $this->schema->getTagProduction($number);
            if ($production !== null) {
                $this->remember($this->schema->getTagCddl($number));
                $inner = Type::name($production);
            }
        }
        // Any other type applies through the tag: "COSE_Countersignature" reads 19([...]) as one.
        $lines = $this->item($item->getValue(), $inner, false);
        $lines[0] = $number . '(' . $lines[0];
        $lines[array_key_last($lines)] .= ')';

        return $lines;
    }

    /**
     * An array production: each item under the name of its field.
     *
     * @return list<string>
     */
    private function production(CBORObject $list, string $name, bool $named): array
    {
        $this->quote($name);
        if (! self::isList($list)) {
            return $this->item($list, Type::any(), false);
        }
        $fields = $this->schema->getFields($name);
        $children = [];
        foreach ($list as $index => $item) {
            $field = $fields[$index] ?? null;
            if ($field === null) {
                $children[] = ['', $this->item($item, Type::any(), false)];
                continue;
            }
            [$fieldName, $type] = $field;
            $lines = $this->item($item, $type, false);
            $comment = '/ ' . $fieldName;
            if (self::isBytes($item) && str_starts_with($lines[0], '<< ')) {
                // The bytes and what they encode, side by side: the way RFC 9052 Appendix C shows a protected header.
                $comment .= " h'" . bin2hex($item->getValue()) . "'";
            }
            $children[] = [$comment . ' / ', $lines];
        }
        $lines = $this->block($list instanceof IndefiniteLengthListObject ? '[_' : '[', $children, ']');
        if ($named) {
            $lines[0] = '/ ' . $name . ' / ' . $lines[0];
        }

        return $lines;
    }

    /**
     * A list, each item read as the type $typeOf returns for it.
     *
     * @param callable(CBORObject): Type $typeOf
     * @return list<string>
     */
    private function list(ListObject|IndefiniteLengthListObject $list, callable $typeOf, bool $named): array
    {
        $children = [];
        foreach ($list as $item) {
            $children[] = ['', $this->item($item, $typeOf($item), $named)];
        }

        return $this->block($list instanceof IndefiniteLengthListObject ? '[_' : '[', $children, ']');
    }

    /**
     * A map, its entries named and typed by the production when one is known.
     *
     * @return list<string>
     */
    private function map(MapObject|IndefiniteLengthMapObject $map, ?string $production): array
    {
        $children = [];
        foreach ($map as $entry) {
            $key = $entry->getKey();
            $label = $key instanceof UnsignedIntegerObject || $key instanceof NegativeIntegerObject
                ? (int) $key->normalize()
                : ($key instanceof TextStringObject ? $key->getValue() : null);
            $declared = $production !== null && $label !== null ? $this->schema->getEntry($production, $label) : null;
            $comment = $declared === null ? '' : '/ ' . $declared[0] . ' / ';
            $type = $declared === null ? Type::any() : $declared[1];
            $keyNotation = implode(' ', $this->item($key, Type::any(), false));
            $children[] = [$comment . $keyNotation . ': ', $this->item($entry->getValue(), $type, false)];
        }

        return $this->block($map instanceof IndefiniteLengthMapObject ? '{_' : '{', $children, '}');
    }

    /**
     * A byte string that carries CBOR, written `<< ... >>`; left as bytes when it does not decode.
     *
     * @return list<string>
     */
    private function embedded(CBORObject $item, Type $type): array
    {
        if (! self::isBytes($item)) {
            return $this->item($item, Type::any(), false);
        }
        try {
            $inner = $this->decoder->decode(StringStream::create($item->getValue()));
        } catch (Throwable) {
            return [$this->scalar($item)];
        }
        $lines = $this->item($inner, $type->getInner() ?? Type::any(), false);
        $lines[0] = '<< ' . $lines[0];
        $lines[array_key_last($lines)] .= ' >>';

        return $lines;
    }

    /**
     * An indefinite-length string: its chunks, `(_ h'0102', h'030405')`.
     *
     * @return list<string>
     */
    private function chunks(IndefiniteLengthByteStringObject|IndefiniteLengthTextStringObject $string): array
    {
        $children = [];
        foreach ($string->getChunks() as $chunk) {
            $children[] = ['', [$this->scalar($chunk)]];
        }

        return $this->block('(_', $children, ')');
    }

    /**
     * An array or a map: the children between the brackets, separated by commas, one per line unless the notation
     * is on a single line.
     *
     * @param list<array{string, list<string>}> $children each a prefix (comment, key) and the lines of the value
     * @return list<string>
     */
    private function block(string $open, array $children, string $close): array
    {
        if ($children === []) {
            return [$open . (str_ends_with($open, '_') ? ' ' : '') . $close];
        }
        if (! $this->multiline) {
            $items = [];
            foreach ($children as [$prefix, $lines]) {
                $items[] = $prefix . implode(' ', $lines);
            }

            return [$open . (str_ends_with($open, '_') ? ' ' : '') . implode(', ', $items) . $close];
        }
        $lines = [$open];
        $last = count($children) - 1;
        foreach ($children as $index => [$prefix, $inner]) {
            $inner[0] = $prefix . $inner[0];
            if ($index !== $last) {
                $inner[array_key_last($inner)] .= ',';
            }
            foreach ($inner as $line) {
                $lines[] = $this->indent . $line;
            }
        }
        $lines[] = $close;

        return $lines;
    }

    private function scalar(CBORObject $item): string
    {
        if ($item instanceof ByteStringObject || $item instanceof IndefiniteLengthByteStringObject) {
            return $this->bytes($item->getValue());
        }
        if ($item instanceof TextStringObject || $item instanceof IndefiniteLengthTextStringObject) {
            return self::text($item->getValue());
        }
        if ($item instanceof UnsignedIntegerObject || $item instanceof NegativeIntegerObject) {
            return $item->normalize();
        }
        if ($item instanceof HalfPrecisionFloatObject || $item instanceof SinglePrecisionFloatObject || $item instanceof DoublePrecisionFloatObject) {
            return self::float($item->normalize());
        }
        if ($item instanceof NullObject) {
            return 'null';
        }
        if ($item instanceof TrueObject) {
            return 'true';
        }
        if ($item instanceof FalseObject) {
            return 'false';
        }
        if ($item instanceof UndefinedObject) {
            return 'undefined';
        }
        if ($item instanceof SimpleObject) {
            return sprintf('simple(%d)', $item->normalize());
        }
        if ($item instanceof Normalizable) {
            $value = $item->normalize();

            return is_float($value) ? self::float($value) : json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $content = $item instanceof OtherObject ? $item->getContent() : null;

        return sprintf('simple(%d)', $content === null || $content === '' ? $item->getAdditionalInformation() : Utils::binToInt($content));
    }

    private function bytes(string $value): string
    {
        if ($this->bytesAsText && $value !== '' && ! str_contains($value, "'") && ! str_contains($value, '\\') && preg_match('/^\P{C}+$/u', $value) === 1) {
            return "'" . $value . "'";
        }

        return "h'" . bin2hex($value) . "'";
    }

    private static function text(string $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * RFC 8949 section 8: the shortest decimal that round-trips, always with a fraction, and the three specials by
     * name.
     */
    private static function float(float $value): string
    {
        if (is_nan($value)) {
            return 'NaN';
        }
        if (is_infinite($value)) {
            return $value > 0 ? 'Infinity' : '-Infinity';
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function quote(string $production): void
    {
        $this->remember($this->schema->getCddl($production));
    }

    private function remember(?string $cddl): void
    {
        if ($cddl !== null && ! in_array($cddl, $this->cddl, true)) {
            $this->cddl[] = $cddl;
        }
    }

    /**
     * @phpstan-assert-if-true ListObject|IndefiniteLengthListObject $item
     */
    private static function isList(CBORObject $item): bool
    {
        return $item instanceof ListObject || $item instanceof IndefiniteLengthListObject;
    }

    /**
     * @phpstan-assert-if-true MapObject|IndefiniteLengthMapObject $item
     */
    private static function isMap(CBORObject $item): bool
    {
        return $item instanceof MapObject || $item instanceof IndefiniteLengthMapObject;
    }

    /**
     * @phpstan-assert-if-true ByteStringObject|IndefiniteLengthByteStringObject $item
     */
    private static function isBytes(CBORObject $item): bool
    {
        return $item instanceof ByteStringObject || $item instanceof IndefiniteLengthByteStringObject;
    }
}
