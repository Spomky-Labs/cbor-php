<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR;

final class StringStream implements Stream
{
    /**
     * @var resource
     */
    private $resource;

    /**
     * StringStream constructor.
     *
     * @param string $data
     */
    public function __construct(string $data)
    {
        $this->resource = fopen('php://memory', 'r+');
        if (is_bool($this->resource)) {
            throw new \InvalidArgumentException('Unable to crate a stream using this string.');
        }
        fwrite($this->resource, $data);
        rewind($this->resource);
    }

    public function read(int $length): string
    {
        if (0 === $length) {
            return '';
        }
        $data = fread($this->resource, $length);
        if (mb_strlen($data, '8bit') !== $length) {
            throw new \InvalidArgumentException(sprintf('Out of range. Expected: %d, read: %d.', $length, mb_strlen($data, '8bit')));
        }

        return $data;
    }
}
