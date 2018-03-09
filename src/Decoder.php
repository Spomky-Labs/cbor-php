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

final class Decoder
{
    private $stream;

    /**
     * Decoder constructor.
     *
     * @param \resource $stream
     */
    public function __construct($stream)
    {
        $this->stream = $stream;
    }

    /**
     * @return CBORObject
     */
    public function decode(): CBORObject
    {
        return $this->process();
    }

    /**
     * @param bool $breakable
     *
     * @return CBORObject
     */
    private function process(bool $breakable = false): CBORObject
    {
        $ib = ord($this->take(1));
        $mt = $ib >> 5;
        $ai = $ib & 0b00011111;
        $val = null;
        switch ($ai) {
            case 0b00011000: //24
            case 0b00011001: //25
            case 0b00011010: //26
            case 0b00011011: //27
                $val = $this->take(pow($ai - 0b00011000 + 1, 2));
                break;
            case 0b00011100: //28
            case 0b00011101: //29
            case 0b00011110: //30
                throw new \InvalidArgumentException('Cannot stream the data');
            case 0b00011111: //31
                return $this->processInfinite($mt, $breakable);
        }

        return $this->processFinite($mt, $ai, $val);
    }

    /**
     * @param int         $mt
     * @param int         $ai
     * @param null|string $val
     *
     * @return CBORObject
     */
    private function processFinite(int $mt, int $ai, ?string $val): CBORObject
    {
        switch ($mt) {
            // case 0, 1, 7 do not have content; just use val
            case 0b000: //0
                return UnsignedIntegerObject::create($ai, $val);
            case 0b001: //1
                return SignedIntegerObject::create($ai, $val);
            case 0b111: //7
                return OtherObject::create($ai, $val);
            case 0b010: //2
                $length = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));

                return ByteStringObject::create($ai, $this->take($length));
            case 0b011: //3
                $length = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));

                return TextStringObject::create($ai, $this->take($length));
            case 0b100: //4
                $list = [];
                $nbItems = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));
                for ($i = 0; $i < $nbItems; $i++) {
                    $list[] = $this->process();
                }

                return ListObject::create($ai, $val, $list);
            case 0b101: //5
                $list = [];
                $nbItems = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));
                for ($i = 0; $i < $nbItems; $i++) {
                    $list[] = MapItem::create($this->process(), $this->process());
                }

                return MapObject::create($ai, $val, $list);
            case 0b110: //6
                return TagObject::create($ai, $val, $this->process());
            default:
                throw new \RuntimeException('Unsupported major type'); // Should never append
        }
    }

    /**
     * @param int  $mt
     * @param bool $breakable
     *
     * @return CBORObject
     */
    private function processInfinite(int $mt, bool $breakable): CBORObject
    {
        switch ($mt) {
            case 0b010: //2
                $val = [];
                while ($it = $this->process(true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = $it;
                }

                return ByteStringWithChunkObject::create(0b00011111, $val);
            case 0b011: //3
                $val = [];
                while ($it = $this->process(true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = $it;
                }

                return TextStringWithChunkObject::create(0b00011111, $val);
            case 0b100: //4
                $val = [];
                while ($it = $this->process(true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = $it;
                }

                return ListObject::create(0b00011111, null, $val);
            case 0b101: //5
                $val = [];
                while ($it = $this->process(true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = MapItem::create($it, $this->process());
                }

                return MapObject::create(0b00011111, null, $val);
            case 0b111: //7
                if ($breakable) {
                    return OtherObject::create(0b00011111, null);
                } else {
                    throw new \InvalidArgumentException('Cannot stream the data');
                }
            default:
                throw new \InvalidArgumentException('Cannot stream the data');
        }
    }

    /**
     * @param int $length
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private function take(int $length): string
    {
        if (0 === $length) {
            return '';
        }
        $data = fread($this->stream, $length);
        if (!is_string($data)) {
            throw new \InvalidArgumentException('Cannot stream the data');
        }

        return $data;
    }

    /**
     * @param CBORObject $object
     *
     * @return bool
     */
    private function isBreak(CBORObject $object): bool
    {
        return $object->getMajorType() === 0b111 && $object->getAdditionalInformation() === 0b00011111;
    }
}
