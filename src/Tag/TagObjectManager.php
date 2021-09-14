<?php

declare(strict_types=1);

namespace CBOR\Tag;

use function array_key_exists;
use CBOR\CBORObject;
use CBOR\TagObject;
use CBOR\Utils;
use InvalidArgumentException;

class TagObjectManager
{
    /**
     * @var string[]
     */
    private array $classes = [];

    public function add(string $class): void
    {
        if (!$class instanceof TagObject) {
            throw new InvalidArgumentException('Invalid class.');
        }
        if ($class::getTagId() < 0) {
            throw new InvalidArgumentException('Invalid tag ID.');
        }
        $this->classes[$class::getTagId()] = $class;
    }

    public function getClassForValue(int $value): string
    {
        return array_key_exists($value, $this->classes) ? $this->classes[$value] : GenericTag::class;
    }

    public function createObjectForValue(int $additionalInformation, ?string $data, CBORObject $object): TagObject
    {
        $value = $additionalInformation;
        if ($additionalInformation >= 24) {
            Utils::assertString($data, 'Invalid data');
            $value = Utils::binToInt($data);
        }
        /** @var TagObject $class */
        $class = $this->getClassForValue($value);

        return $class::createFromLoadedData($additionalInformation, $data, $object);
    }
}
