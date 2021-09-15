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

use InvalidArgumentException;

/**
 * @final
 */
class IndefiniteLengthByteStringObject extends AbstractCBORObject
{
    private const MAJOR_TYPE = self::MAJOR_TYPE_BYTE_STRING;
    private const ADDITIONAL_INFORMATION = self::LENGTH_INDEFINITE;

    /**
     * @var ByteStringObject[]
     */
    private $chunks = [];

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
        foreach ($this->chunks as $chunk) {
            $result .= $chunk->__toString();
        }
        $bin = hex2bin('FF');
        if (false === $bin) {
            throw new InvalidArgumentException('Unable to convert the data');
        }
        $result .= $bin;

        return $result;
    }

    public function add(ByteStringObject $chunk): self
    {
        $this->chunks[] = $chunk;

        return $this;
    }

    public function append(string $chunk): self
    {
        $this->add(new ByteStringObject($chunk));

        return $this;
    }

    public function getValue(): string
    {
        $result = '';
        foreach ($this->chunks as $chunk) {
            $result .= $chunk->getValue();
        }

        return $result;
    }

    public function getLength(): int
    {
        $length = 0;
        foreach ($this->chunks as $chunk) {
            $length += $chunk->getLength();
        }

        return $length;
    }

    /**
     * @deprecated The method will be removed on v3.0. No replacement
     */
    public function getNormalizedData(bool $ignoreTags = false): string
    {
        $result = '';
        foreach ($this->chunks as $chunk) {
            $result .= $chunk->getNormalizedData($ignoreTags);
        }

        return $result;
    }
}
