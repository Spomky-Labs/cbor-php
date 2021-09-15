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
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;

/**
 * @phpstan-implements IteratorAggregate<int, MapItem>
 * @final
 */
class IndefiniteLengthMapObject extends AbstractCBORObject implements Countable, IteratorAggregate
{
    private const MAJOR_TYPE = self::MAJOR_TYPE_MAP;
    private const ADDITIONAL_INFORMATION = self::LENGTH_INDEFINITE;

    /**
     * @var MapItem[]
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
            $result .= (string) $object->getKey();
            $result .= (string) $object->getValue();
        }
        $bin = hex2bin('FF');
        if (false === $bin) {
            throw new InvalidArgumentException('Unable to convert the data');
        }
        $result .= $bin;

        return $result;
    }

    public function append(CBORObject $key, CBORObject $value): self
    {
        $this->data[] = new MapItem($key, $value);

        return $this;
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return Iterator<int, MapItem>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->data);
    }

    /**
     * @return mixed[]
     */
    public function getNormalizedData(bool $ignoreTags = false): array
    {
        $result = [];
        foreach ($this->data as $object) {
            $result[$object->getKey()->getNormalizedData($ignoreTags)] = $object->getValue()->getNormalizedData($ignoreTags);
        }

        return $result;
    }
}
