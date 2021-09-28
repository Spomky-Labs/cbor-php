<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR;

use ArrayIterator;
use function count;
use Countable;
use Iterator;
use IteratorAggregate;

/**
 * @phpstan-implements IteratorAggregate<int, CBORObject>
 * @final
 */
class IndefiniteLengthListObject extends AbstractCBORObject implements Countable, IteratorAggregate, Normalizable
{
    private const MAJOR_TYPE = self::MAJOR_TYPE_LIST;
    private const ADDITIONAL_INFORMATION = self::LENGTH_INDEFINITE;

    /**
     * @var CBORObject[]
     */
    private $data = [];

    public function __construct()
    {
        parent::__construct(self::MAJOR_TYPE, self::ADDITIONAL_INFORMATION);
    }

    public static function create(): self
    {
        return new self();
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        foreach ($this->data as $object) {
            $result .= (string) $object;
        }
        $result .= "\xFF";

        return $result;
    }

    /**
     * @return mixed[]
     */
    public function normalize(): array
    {
        return array_map(static function (CBORObject $object) {
            return $object instanceof Normalizable ? $object->normalize() : $object;
        }, $this->data);
    }

    /**
     * @deprecated The method will be removed on v3.0. Please use CBOR\Normalizable interface
     *
     * @return mixed[]
     */
    public function getNormalizedData(bool $ignoreTags = false): array
    {
        return $this->normalize();
    }

    public function add(CBORObject $item): self
    {
        $this->data[] = $item;

        return $this;
    }

    /**
     * @deprecated The method will be removed on v3.0. No replacement
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return Iterator<int, CBORObject>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->data);
    }
}
