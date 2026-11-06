<?php

declare(strict_types=1);

namespace CBOR\Diagnostic;

use function array_key_exists;
use function count;
use InvalidArgumentException;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;

/**
 * What the notation knows about a document beyond CBOR: the productions of a CDDL specification, and which of them
 * a tag number, a position or a label stands for.
 *
 * A schema is a handful of declarations, each named after the production of the RFC it comes from and carrying the
 * CDDL text so that a rendering can quote it:
 *
 * ```php
 * $schema = Schema::create()
 *     ->addArray('COSE_Sign1', [
 *         'protected' => 'bstr .cbor header_map',
 *         'unprotected' => 'header_map',
 *         'payload' => 'bstr / nil',
 *         'signature' => 'bstr',
 *     ], 'COSE_Sign1 = [ Headers, payload : bstr / nil, signature : bstr ]')
 *     ->addTag(18, 'COSE_Sign1', 'COSE_Sign1_Tagged = #6.18(COSE_Sign1)')
 *     ->addMap('header_map', [
 *         1 => ['alg', 'alg-id'],
 *         4 => ['kid', 'bstr'],
 *     ])
 *     ->addRegistry('alg-id', [-7 => 'ES256']);
 * ```
 *
 * Three kinds of production. An array names its items in order; a map names its entries by label and says what each
 * value is; a registry names the values of an integer, the way an IANA registry does. The type of a field is a CDDL
 * type expression, see {@see Type}. Names are resolved when a document is rendered, so a production can refer to one
 * declared after it, or to itself; a name that is never declared reads as "any".
 *
 * @see \CBOR\Test\Diagnostic\SchemaTest
 */
final class Schema
{
    /**
     * @var array<string, array{list<array{string, Type}>, string|null}> name => [fields, cddl]
     */
    private array $arrays = [];

    /**
     * @var array<string, array{array<int|string, array{string, Type}>, string|null}> name => [entries, cddl]
     */
    private array $maps = [];

    /**
     * @var array<string, array{array<int, string>, string|null}> name => [names, cddl]
     */
    private array $registries = [];

    /**
     * @var array<int, array{string, string|null}> tag number => [production, cddl]
     */
    private array $tags = [];

    public static function create(): self
    {
        return new self();
    }

    /**
     * An array production: the name of each item, in order, and its type.
     *
     * @param array<string, string> $fields field name => type expression
     * @param string|null           $cddl   the production as the RFC writes it, quoted by the rendering
     */
    public function addArray(string $name, array $fields, ?string $cddl = null): self
    {
        $this->assertUndeclared($name);
        $parsed = [];
        foreach ($fields as $field => $type) {
            if (! is_string($field) || $field === '' || ! is_string($type)) {
                throw new InvalidArgumentException(sprintf('The fields of "%s" shall map a field name to a type expression.', $name));
            }
            $parsed[] = [$field, Type::parse($type)];
        }
        $this->arrays[$name] = [$parsed, $cddl];

        return $this;
    }

    /**
     * A map production: for each label, the name of the entry and the type of its value. The type may be left out:
     * `4 => 'kid'` is `4 => ['kid', 'any']`.
     *
     * @param array<int|string, string|array{string, string}> $entries label => name, or label => [name, type expression]
     */
    public function addMap(string $name, array $entries, ?string $cddl = null): self
    {
        $this->assertUndeclared($name);
        $parsed = [];
        foreach ($entries as $label => $entry) {
            if (is_string($entry)) {
                $entry = [$entry, 'any'];
            }
            if (! is_array($entry) || count($entry) !== 2 || ! is_string($entry[0]) || ! is_string($entry[1])) {
                throw new InvalidArgumentException(sprintf('The entries of "%s" shall map a label to a name, or to a [name, type expression] pair.', $name));
            }
            $parsed[$label] = [$entry[0], Type::parse($entry[1])];
        }
        $this->maps[$name] = [$parsed, $cddl];

        return $this;
    }

    /**
     * A registry: the name each integer value stands for, written after it as a comment: `-7 / ES256 /`.
     *
     * @param array<int, string> $names value => name
     */
    public function addRegistry(string $name, array $names, ?string $cddl = null): self
    {
        $this->assertUndeclared($name);
        foreach ($names as $value => $label) {
            if (! is_int($value) || ! is_string($label)) {
                throw new InvalidArgumentException(sprintf('The registry "%s" shall map integers to names.', $name));
            }
        }
        $this->registries[$name] = [$names, $cddl];

        return $this;
    }

    /**
     * The production a tag number wraps: a document that opens with this tag is read as that production.
     */
    public function addTag(int $number, string $production, ?string $cddl = null): self
    {
        if ($number < 0) {
            throw new InvalidArgumentException('A tag number is a non-negative integer.');
        }
        $this->tags[$number] = [$production, $cddl];

        return $this;
    }

    public function has(string $name): bool
    {
        return $this->isArray($name) || $this->isMap($name) || $this->isRegistry($name);
    }

    public function isArray(string $name): bool
    {
        return array_key_exists($name, $this->arrays);
    }

    public function isMap(string $name): bool
    {
        return array_key_exists($name, $this->maps);
    }

    public function isRegistry(string $name): bool
    {
        return array_key_exists($name, $this->registries);
    }

    /**
     * @return list<array{string, Type}> the [name, type] of each field of an array production, in order
     */
    public function getFields(string $name): array
    {
        return $this->arrays[$name][0] ?? [];
    }

    /**
     * @return array{string, Type}|null the [name, type] of the entry a map production declares under a label
     */
    public function getEntry(string $name, int|string $label): ?array
    {
        return $this->maps[$name][0][$label] ?? null;
    }

    public function getRegistryName(string $name, int $value): ?string
    {
        return $this->registries[$name][0][$value] ?? null;
    }

    /**
     * The production a tag number was declared to wrap, if any.
     */
    public function getTagProduction(int $number): ?string
    {
        return $this->tags[$number][0] ?? null;
    }

    /**
     * The CDDL a production or a tag was declared with, if any.
     */
    public function getCddl(string $name): ?string
    {
        return $this->arrays[$name][1] ?? $this->maps[$name][1] ?? $this->registries[$name][1] ?? null;
    }

    public function getTagCddl(int $number): ?string
    {
        return $this->tags[$number][1] ?? null;
    }

    /**
     * The array productions whose first field is a text literal, keyed by that literal: a bare list opening with
     * "Signature1" is a Sig_structure. This is how the structures of RFC 9052 section 4.4 are told apart, since
     * nothing but their context string identifies them.
     *
     * @return array<string, string> literal => production
     */
    public function getProductionsByFirstLiteral(): array
    {
        $productions = [];
        foreach ($this->arrays as $name => [$fields]) {
            if ($fields === []) {
                continue;
            }
            $first = $fields[0][1];
            if ($first->getKind() === Type::LITERAL) {
                $productions[(string) $first->getValue()] ??= $name;
            }
        }

        return $productions;
    }

    private function assertUndeclared(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('A production name cannot be empty.');
        }
        if ($this->has($name)) {
            throw new InvalidArgumentException(sprintf('The production "%s" is already declared.', $name));
        }
    }
}
