<?php

declare(strict_types=1);

namespace CBOR\Diagnostic;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\IndefiniteLengthByteStringObject;
use CBOR\IndefiniteLengthListObject;
use CBOR\IndefiniteLengthMapObject;
use CBOR\IndefiniteLengthTextStringObject;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\OtherObject\DoublePrecisionFloatObject;
use CBOR\OtherObject\FalseObject;
use CBOR\OtherObject\HalfPrecisionFloatObject;
use CBOR\OtherObject\NullObject;
use CBOR\OtherObject\SinglePrecisionFloatObject;
use CBOR\OtherObject\TrueObject;
use CBOR\OtherObject\UndefinedObject;
use CBOR\Tag;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use function count;
use function in_array;
use InvalidArgumentException;
use function sprintf;
use function strlen;

/**
 * The type of a field or of a map value, written the way CDDL (RFC 8610) writes it.
 *
 * The notation only has to know enough of CDDL to name things and to open byte strings that carry CBOR, so a type
 * expression is one of a handful of forms, combined with "/" into a choice:
 *
 *   - a production name, "header_map", declared in the Schema;
 *   - "bstr .cbor header_map": a byte string whose content is CBOR, shown as << ... >>;
 *   - "[+ COSE_Signature]" or "[* bstr]": an array whose items are all of one type;
 *   - "#6.18(COSE_Sign1)": a tag number around a type;
 *   - a text literal, "\"Signature1\"", which a choice or a first field can be discriminated on;
 *   - a name of the CDDL prelude, "bstr", "int", "nil", "any", used to tell alternatives apart, with or without a
 *     control operator after it: "bstr .size 0" is "bstr";
 *   - anything else, which reads as "any".
 *
 * A name that the Schema does not declare also reads as "any", so a type can name a production declared later.
 *
 * @see \CBOR\Test\Diagnostic\TypeTest
 * @see https://www.rfc-editor.org/rfc/rfc8610
 */
final class Type
{
    public const ANY = 'any';

    public const PRIMITIVE = 'primitive';

    public const LITERAL = 'literal';

    public const NAME = 'name';

    public const EMBEDDED = 'embedded';

    public const ARRAY_OF = 'array_of';

    public const TAGGED = 'tagged';

    public const CHOICE = 'choice';

    private const PRIMITIVES = [
        'any',
        'bstr',
        'bytes',
        'tstr',
        'text',
        'int',
        'uint',
        'nint',
        'nil',
        'null',
        'bool',
        'true',
        'false',
        'float',
        'float16',
        'float32',
        'float64',
        'undefined',
        'array',
        'map',
    ];

    /**
     * How deep a choice looks into a list to tell its alternatives apart: the first item of the list, and the first
     * item of that one. Enough for "COSE_Countersignature / [+ COSE_Countersignature]", where a single one opens with
     * a byte string and the array form opens with a list.
     */
    private const LOOKAHEAD = 2;

    /**
     * @param list<Type> $alternatives
     */
    private function __construct(
        private string $kind,
        private ?string $value = null,
        private ?self $inner = null,
        private ?int $number = null,
        private array $alternatives = []
    ) {
    }

    public static function any(): self
    {
        return new self(self::ANY);
    }

    public static function name(string $production): self
    {
        return new self(self::NAME, $production);
    }

    public static function parse(string $expression): self
    {
        $alternatives = [];
        foreach (self::splitChoice($expression) as $alternative) {
            $alternatives[] = self::parseAlternative(trim($alternative));
        }
        if (count($alternatives) === 1) {
            return $alternatives[0];
        }

        return new self(self::CHOICE, null, null, null, $alternatives);
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * The production name, the primitive name or the literal text, depending on the kind.
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * The embedded, item or tagged type, depending on the kind.
     */
    public function getInner(): ?self
    {
        return $this->inner;
    }

    public function getTagNumber(): ?int
    {
        return $this->number;
    }

    /**
     * @return list<Type>
     */
    public function getAlternatives(): array
    {
        return $this->alternatives;
    }

    /**
     * The alternative of a choice that the item matches, in the order written; the type itself when it is not a
     * choice, null when no alternative accepts the item.
     */
    public function resolve(CBORObject $item, Schema $schema): ?self
    {
        if ($this->kind !== self::CHOICE) {
            return $this->accepts($item, $schema, self::LOOKAHEAD) ? $this : null;
        }
        foreach ($this->alternatives as $alternative) {
            if ($alternative->accepts($item, $schema, self::LOOKAHEAD)) {
                return $alternative;
            }
        }

        return null;
    }

    /**
     * Whether an item can be read as this type, looking $depth levels into lists to tell array productions apart.
     */
    public function accepts(CBORObject $item, Schema $schema, int $depth): bool
    {
        switch ($this->kind) {
            case self::ANY:
                return true;
            case self::TAGGED:
                return $item instanceof Tag && $item->getTagNumber() === $this->number;
            case self::CHOICE:
                foreach ($this->alternatives as $alternative) {
                    if ($alternative->accepts($item, $schema, $depth)) {
                        return true;
                    }
                }

                return false;
        }
        // A tagged item is read through its tag for every other kind: "COSE_Countersignature" accepts 19([...]).
        while ($item instanceof Tag) {
            $item = $item->getValue();
        }
        switch ($this->kind) {
            case self::PRIMITIVE:
                return self::isPrimitive((string) $this->value, $item);
            case self::LITERAL:
                return self::isText($item) && $item->getValue() === $this->value;
            case self::EMBEDDED:
                return self::isBytes($item);
            case self::ARRAY_OF:
                if (! self::isList($item)) {
                    return false;
                }
                $first = self::firstItemOf($item);

                return $depth <= 0 || $first === null || $this->inner === null || $this->inner->accepts($first, $schema, $depth - 1);
            case self::NAME:
                return self::acceptsProduction((string) $this->value, $item, $schema, $depth);
        }

        return true;
    }

    private static function acceptsProduction(string $name, CBORObject $item, Schema $schema, int $depth): bool
    {
        if ($schema->isRegistry($name)) {
            return $item instanceof UnsignedIntegerObject || $item instanceof NegativeIntegerObject;
        }
        if ($schema->isMap($name)) {
            return $item instanceof MapObject || $item instanceof IndefiniteLengthMapObject;
        }
        if (! $schema->isArray($name)) {
            // Not declared: reads as "any".
            return true;
        }
        if (! self::isList($item)) {
            return false;
        }
        $fields = $schema->getFields($name);
        $first = self::firstItemOf($item);
        if ($depth <= 0 || $first === null || $fields === []) {
            return true;
        }

        return $fields[0][1]->accepts($first, $schema, $depth - 1);
    }

    private static function isPrimitive(string $name, CBORObject $item): bool
    {
        return match ($name) {
            'any' => true,
            'bstr', 'bytes' => self::isBytes($item),
            'tstr', 'text' => self::isText($item),
            'int' => $item instanceof UnsignedIntegerObject || $item instanceof NegativeIntegerObject,
            'uint' => $item instanceof UnsignedIntegerObject,
            'nint' => $item instanceof NegativeIntegerObject,
            'nil', 'null' => $item instanceof NullObject,
            'bool' => $item instanceof TrueObject || $item instanceof FalseObject,
            'true' => $item instanceof TrueObject,
            'false' => $item instanceof FalseObject,
            'undefined' => $item instanceof UndefinedObject,
            'float', 'float16', 'float32', 'float64' => $item instanceof HalfPrecisionFloatObject || $item instanceof SinglePrecisionFloatObject || $item instanceof DoublePrecisionFloatObject,
            'array' => self::isList($item),
            'map' => $item instanceof MapObject || $item instanceof IndefiniteLengthMapObject,
            default => true,
        };
    }

    private static function parseAlternative(string $expression): self
    {
        if ($expression === '') {
            throw new InvalidArgumentException('A type expression cannot be empty.');
        }
        if (preg_match('/^"((?:[^"\\\\]|\\\\.)*)"$/', $expression, $matches) === 1) {
            return new self(self::LITERAL, stripcslashes($matches[1]));
        }
        if (preg_match('/^bstr\s+\.cbor\s+(.+)$/s', $expression, $matches) === 1) {
            return new self(self::EMBEDDED, null, self::parse($matches[1]));
        }
        if (preg_match('/^\[\s*[+*]\s*(.+?)\s*\]$/s', $expression, $matches) === 1) {
            return new self(self::ARRAY_OF, null, self::parse($matches[1]));
        }
        if (preg_match('/^#6\.(\d+)\((.+)\)$/s', $expression, $matches) === 1) {
            return new self(self::TAGGED, null, self::parse($matches[2]), (int) $matches[1]);
        }
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_\-.]*)(\s.*)?$/s', $expression, $matches) === 1) {
            $name = $matches[1];
            if (in_array($name, self::PRIMITIVES, true)) {
                return $name === 'any' ? self::any() : new self(self::PRIMITIVE, $name);
            }
            if (! isset($matches[2])) {
                return new self(self::NAME, $name);
            }
        }

        return self::any();
    }

    /**
     * Splits a choice on the "/" that sit outside brackets, parentheses and quotes.
     *
     * @return list<string>
     */
    private static function splitChoice(string $expression): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $quoted = false;
        $length = strlen($expression);
        for ($i = 0; $i < $length; ++$i) {
            $char = $expression[$i];
            if ($quoted) {
                $current .= $char;
                if ($char === '\\' && $i + 1 < $length) {
                    $current .= $expression[++$i];
                } elseif ($char === '"') {
                    $quoted = false;
                }
                continue;
            }
            switch ($char) {
                case '"':
                    $quoted = true;
                    break;
                case '[':
                case '(':
                case '{':
                    ++$depth;
                    break;
                case ']':
                case ')':
                case '}':
                    --$depth;
                    break;
                case '/':
                    if ($depth === 0) {
                        $parts[] = $current;
                        $current = '';
                        continue 2;
                    }
                    break;
            }
            $current .= $char;
        }
        if ($depth !== 0 || $quoted) {
            throw new InvalidArgumentException(sprintf('Unbalanced type expression "%s".', $expression));
        }
        $parts[] = $current;

        return $parts;
    }

    /**
     * @phpstan-assert-if-true ListObject|IndefiniteLengthListObject $item
     */
    private static function isList(CBORObject $item): bool
    {
        return $item instanceof ListObject || $item instanceof IndefiniteLengthListObject;
    }

    /**
     * @phpstan-assert-if-true ByteStringObject|IndefiniteLengthByteStringObject $item
     */
    private static function isBytes(CBORObject $item): bool
    {
        return $item instanceof ByteStringObject || $item instanceof IndefiniteLengthByteStringObject;
    }

    /**
     * @phpstan-assert-if-true TextStringObject|IndefiniteLengthTextStringObject $item
     */
    private static function isText(CBORObject $item): bool
    {
        return $item instanceof TextStringObject || $item instanceof IndefiniteLengthTextStringObject;
    }

    private static function firstItemOf(CBORObject $list): ?CBORObject
    {
        if (! self::isList($list) || count($list) === 0) {
            return null;
        }
        foreach ($list as $item) {
            return $item;
        }

        return null;
    }
}
