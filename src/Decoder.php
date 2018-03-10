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

use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagObjectManager;

final class Decoder
{
    /**
     * @var TagObjectManager
     */
    private $tagObjectManager;

    /**
     * @var OtherObjectManager
     */
    private $otherTypeManager;

    /**
     * Decoder constructor.
     *
     * @param TagObjectManager   $tagObjectManager
     * @param OtherObjectManager $otherTypeManager
     */
    public function __construct(TagObjectManager $tagObjectManager, OtherObjectManager $otherTypeManager)
    {
        $this->tagObjectManager = $tagObjectManager;
        $this->otherTypeManager = $otherTypeManager;
    }

    /**
     * @param \resource $stream
     *
     * @return CBORObject
     */
    public function decode($stream): CBORObject
    {
        return $this->process($stream);
    }

    /**
     * @param \resource $stream
     * @param bool      $breakable
     *
     * @return CBORObject
     */
    private function process($stream, bool $breakable = false): CBORObject
    {
        $ib = ord($this->take($stream, 1));
        $mt = $ib >> 5;
        $ai = $ib & 0b00011111;
        $val = null;
        switch ($ai) {
            case 0b00011000: //24
            case 0b00011001: //25
            case 0b00011010: //26
            case 0b00011011: //27
                $val = $this->take($stream, pow($ai - 0b00011000 + 1, 2));
                break;
            case 0b00011100: //28
            case 0b00011101: //29
            case 0b00011110: //30
                throw new \InvalidArgumentException('Cannot stream the data');
            case 0b00011111: //31
                return $this->processInfinite($stream, $mt, $breakable);
        }

        return $this->processFinite($stream, $mt, $ai, $val);
    }

    /**
     * @param \resource   $stream
     * @param int         $mt
     * @param int         $ai
     * @param null|string $val
     *
     * @return CBORObject
     */
    private function processFinite($stream, int $mt, int $ai, ?string $val): CBORObject
    {
        switch ($mt) {
            // case 0, 1, 7 do not have content; just use val
            case 0b000: //0
                return UnsignedIntegerObject::create($ai, $val);
            case 0b001: //1
                return SignedIntegerObject::create($ai, $val);
            case 0b111: //7
                return $this->otherTypeManager->createObjectForValue($ai, $val);
            case 0b010: //2
                $length = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));

                return ByteStringObject::create($ai, $val, $this->take($stream, $length));
            case 0b011: //3
                $length = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));

                return TextStringObject::create($ai, $val, $this->take($stream, $length));
            case 0b100: //4
                $list = [];
                $nbItems = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));
                for ($i = 0; $i < $nbItems; $i++) {
                    $list[] = $this->process($stream);
                }

                return ListObject::create($ai, $val, $list);
            case 0b101: //5
                $list = [];
                $nbItems = null === $val ? $ai : gmp_intval(gmp_init(bin2hex($val), 16));
                for ($i = 0; $i < $nbItems; $i++) {
                    $list[] = MapItem::create($this->process($stream), $this->process($stream));
                }

                return MapObject::create($ai, $val, $list);
            case 0b110: //6
                return $this->tagObjectManager->createObjectForValue($ai, $val, $this->process($stream));
            default:
                throw new \RuntimeException('Unsupported major type'); // Should never append
        }
    }

    /**
     * @param \resource $stream
     * @param int       $mt
     * @param bool      $breakable
     *
     * @return CBORObject
     */
    private function processInfinite($stream, int $mt, bool $breakable): CBORObject
    {
        switch ($mt) {
            case 0b010: //2
                $val = [];
                while ($it = $this->process($stream, true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = $it;
                }

                return ByteStringWithChunkObject::create($val);
            case 0b011: //3
                $val = [];
                while ($it = $this->process($stream, true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = $it;
                }

                return TextStringWithChunkObject::create($val);
            case 0b100: //4
                $val = [];
                while ($it = $this->process($stream, true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = $it;
                }

                return ListObject::create(0b00011111, null, $val);
            case 0b101: //5
                $val = [];
                while ($it = $this->process($stream, true)) {
                    if ($this->isBreak($it)) {
                        break;
                    }
                    $val[] = MapItem::create($it, $this->process($stream));
                }

                return MapObject::create(0b00011111, null, $val);
            case 0b111: //7
                if ($breakable) {
                    return $this->otherTypeManager->createObjectForValue(0b00011111, null);
                } else {
                    throw new \InvalidArgumentException('Cannot stream the data');
                }
            default:
                throw new \InvalidArgumentException('Cannot stream the data');
        }
    }

    /**
     * @param \resource $stream
     * @param int       $length
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private function take($stream, int $length): string
    {
        if (0 === $length) {
            return '';
        }
        $data = fread($stream, $length);
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
