CBOR for PHP
============

![Build Status](https://github.com/Spomky-Labs/cbor-php/workflows/Unit%20and%20Functional%20Tests/badge.svg)
![Build Status](https://github.com/Spomky-Labs/cbor-php/workflows/Mutation%20Testing/badge.svg)

![Coding Standards](https://github.com/Spomky-Labs/cbor-php/workflows/Coding%20Standards/badge.svg)
![Static Analyze](https://github.com/Spomky-Labs/cbor-php/workflows/Static%20Analyze/badge.svg)

[![Latest Stable Version](https://poser.pugx.org/Spomky-Labs/cbor-php/v)](//packagist.org/packages/Spomky-Labs/cbor-php)
[![Total Downloads](https://poser.pugx.org/Spomky-Labs/cbor-php/downloads)](//packagist.org/packages/Spomky-Labs/cbor-php)
[![Latest Unstable Version](https://poser.pugx.org/Spomky-Labs/cbor-php/v/unstable)](//packagist.org/packages/Spomky-Labs/cbor-php)
[![License](https://poser.pugx.org/Spomky-Labs/cbor-php/license)](//packagist.org/packages/Spomky-Labs/cbor-php)

# Scope

This library will help you to decode and create objects using the Concise Binary Object Representation (CBOR - [RFC7049](https://tools.ietf.org/html/rfc7049)).

# Installation

Install the library with Composer: `composer require spomky-labs/cbor-php`.

This project follows the [semantic versioning](http://semver.org/) strictly.

# Support

I bring solutions to your problems and answer your questions.

If you really love that project, and the work I have done or if you want I prioritize your issues, then you can help me out for a couple of :beers: or more!

[Become a sponsor](https://github.com/sponsors/Spomky)

Or

[![Become a Patreon](https://c5.patreon.com/external/logo/become_a_patron_button.png)](https://www.patreon.com/FlorentMorselli)

# Documentation

## Object Creation

This library supports all Major Types defined in the RFC7049 and has capabilities to support any kind of Tags (Major Type 6) and Other Objects (Major Type 7).

Each object have at least:

* a static method `create`. This method will correctly instantiate the object.
* can be converted into a binary string: `$object->__toString();` or `(string) $object`.
* a method `getNormalizedData($ignoreTags = false)` that converts the object into its normalized representation. Tags can be ignored with the first argument set to `true`.

### Positive Integer (Major Type 0)

```php
<?php

use CBOR\PositiveIntegerObject;

$object = PositiveIntegerObject::create(10);
$object = PositiveIntegerObject::create(1000);
$object = PositiveIntegerObject::create(10000);
$object = PositiveIntegerObject::createFromHex('0AFFEBFF');

echo bin2hex((string)$object); // 1a0affebff
```

**Note: the method `getNormalizedData()` will always return integers as strings. This is needed to avoid lack of 64 bits integer support on PHP**

### Negative Integer (Major Type 1)

```php
<?php

use CBOR\NegativeIntegerObject;

$object = NegativeIntegerObject::create(-10);
$object = NegativeIntegerObject::create(-1000);
$object = NegativeIntegerObject::create(-10000);
```

**Note: the method `getNormalizedData()` will always return integers as strings. This is needed to avoid lack of 64 bits integer support on PHP**

### Byte String / Indefinite Length Byte String (Major Type 2)

Byte String and Indefinite Length Byte String objects have the same major type but are handled by two different classes in this library.

```php
<?php

use CBOR\ByteStringObject; // Byte String
use CBOR\IndefiniteLengthByteStringObject; // Indefinite Length Byte String

// Create a Byte String with value "Hello"
$object = new ByteStringObject('Hello');

// Create an Indefinite Length Byte String with value "Hello" ("He" + "" + "ll" + "o")
$object = new IndefiniteLengthByteStringObject();
$object->append('He');
$object->append('');
$object->append('ll');
$object->append('o');
```

### Text String / Indefinite Length Text String (Major Type 3)

Text String and Indefinite Length Text String objects have the same major type but are handled by two different classes in this library.

```php
<?php

use CBOR\TextStringObject; // Text String
use CBOR\TextStringWithChunkObject; // Indefinite Length Text String

// Create a Text String with value "(｡◕‿◕｡)⚡"
$object = new TextStringObject('(｡◕‿◕｡)⚡');

// Create an Indefinite Length Text String with value "(｡◕‿◕｡)⚡" ("(｡◕" + "" + "‿◕" + "｡)⚡")
$object = new TextStringWithChunkObject();
$object->append('(｡◕');
$object->append('');
$object->append('‿◕');
$object->append('｡)⚡');
```

### List / Indefinite Length List (Major Type 4)

List and Indefinite Length List objects have the same major type but are handled by two different classes in this library.
Items in the List object can be any of CBOR Object type.

```php
<?php

use CBOR\ListObject; // List
use CBOR\IndefiniteLengthListObject; // Infinite List
use CBOR\TextStringObject;
use CBOR\PositiveIntegerObject;

// Create a List with a single item
$object = new ListObject();
$object->add(new TextStringObject('(｡◕‿◕｡)⚡'));

// Create an Infinite List with several items
$object = new IndefiniteLengthListObject();
$object->add(new TextStringObject('(｡◕‿◕｡)⚡'));
$object->add(PositiveIntegerObject::create(25));
```

### Map / Indefinite Length Map (Major Type 5)

Map and Indefinite Length Map objects have the same major type but are handled by two different classes in this library.
Keys and values in the Map object can be any of CBOR Object type.

**However, be really careful with keys. Please follow the recommendation hereunder:**

* Keys should not be duplicated
* Keys should be of type Positive or Negative Integer, (Indefinite Length)Byte String or (Indefinite Length)Text String. Other types may cause errors.

```php
<?php

use CBOR\MapObject; // Map
use CBOR\IndefiniteLengthMapObject; // Infinite Map
use CBOR\ByteStringObject;
use CBOR\TextStringObject;
use CBOR\PositiveIntegerObject;
use CBOR\NegativeIntegerObject;

// Create a Map with a single item
$object = new MapObject();
$object->add(PositiveIntegerObject::create(25),new TextStringObject('(｡◕‿◕｡)⚡'));

// Create an Infinite Map with several items
$object = new IndefiniteLengthMapObject();
$object->append(new ByteStringObject('A'), NegativeIntegerObject::create(-652));
$object->append(PositiveIntegerObject::create(25), new TextStringObject('(｡◕‿◕｡)⚡'));
```

### Tags (Major Type 6)

This library can support any kind of tags.
It comes with some of the thew described in the specification:

* Base 16 encoding
* Base 64 encoding
* Base 64 Url Safe encoding
* Big Float
* Decimal Fraction
* Epoch
* Timestamp
* Positive Big Integer
* Negative Big Integer

You can easily create your own tag by extending the abstract class `CBOR\TagObject`.
This library provides a `CBOR\Tag\GenericTag` class that can be used for any other unknown/unsupported tags.

```php
<?php

use CBOR\Tag\TimestampTag;
use CBOR\PositiveIntegerObject;

// Create an unsigned object that represents the current timestamp
$object = PositiveIntegerObject::create(time()); // e.g. 1525873787

//We tag the object with the Timestamp Tag
$taggedObject = TimestampTag::create($object); // Returns a \DateTimeImmutable object with timestamp at 1525873787
```

### Other Objects (Major Type 7)

This library can support any kind of "other objects".
It comes with some of the thew described in the specification:

* False
* True
* Null
* Undefined
* Half Precision Float
* Single Precision Float
* Double Precision Float
* Simple Value

You can easily create your own object by extending the abstract class `CBOR\OtherObject`.
This library provides a `CBOR\OtherObject\GenericTag` class that can be used for any other unknown/unsupported objects.

**Because PHP does not support an 'undefined' object, the normalization method will return `'undefined'`.**

```php
<?php

use CBOR\OtherObject\FalseObject;
use CBOR\OtherObject\NullObject;
use CBOR\OtherObject\UndefinedObject;

$object = new FalseObject();
$object->getNormalizedData(); // false

$object = new NullObject();
$object->getNormalizedData(); // null

$object = new UndefinedObject();
$object->getNormalizedData(); // 'undefined'
```

## Example

```php
<?php

use CBOR\MapObject;
use CBOR\TextStringObject;
use CBOR\ListObject;
use CBOR\NegativeIntegerObject;
use CBOR\PositiveIntegerObject;
use CBOR\OtherObject\TrueObject;
use CBOR\OtherObject\FalseObject;
use CBOR\OtherObject\NullObject;
use CBOR\Tag\DecimalFractionTag;
use CBOR\Tag\TimestampTag;

$object = new MapObject();
$object->add(
    new TextStringObject('(｡◕‿◕｡)⚡'),
    new ListObject([
        new TrueObject(),
        new FalseObject(),
        new DecimalFractionTag(
            new ListObject([
                NegativeIntegerObject::create(-2),
                PositiveIntegerObject::create(1234),
            ])
        ),
    ])
);

$object->add(
    PositiveIntegerObject::create(2000),
    new NullObject()
);
$object->add(
    new TextStringObject('date'),
    TimestampTag::create(
        PositiveIntegerObject::create(1577836800)
    )
);
```

The encoded result will be `0xa37428efbda1e29795e280bfe29795efbda129e29aa183f5f4c482211904d21907d0f66464617465c11a5e0be100`.
The normalized result is:

```php
array:3 [
  "(｡◕‿◕｡)⚡" => array:3 [
    0 => true
    1 => false
    2 => "12.34"
  ]
  2000 => null
  "date" => DateTimeImmutable @1577836800 {
    date: 2020-01-01 00:00:00.0 +00:00
  }
]

```


## Object Loading

If you want to load a CBOR encoded string, you just have to instantiate a `CBOR\Decoder` class.
This class needs the following arguments:

* A `Tag` manager: this manager will be able to identify the tags associated to the data and create it accordingly if the tag ID is supported.
* An `Other Object` manager: this manager will be able to identify all other objects and create it accordingly if supported.

```php
<?php

use CBOR\Decoder;
use CBOR\OtherObject;
use CBOR\Tag;

$otherObjectManager = new OtherObject\OtherObjectManager();
$otherObjectManager->add(OtherObject\SimpleObject::class);
$otherObjectManager->add(OtherObject\FalseObject::class);
$otherObjectManager->add(OtherObject\TrueObject::class);
$otherObjectManager->add(OtherObject\NullObject::class);
$otherObjectManager->add(OtherObject\UndefinedObject::class);
$otherObjectManager->add(OtherObject\SimpleValueObject::class);
$otherObjectManager->add(OtherObject\HalfPrecisionFloatObject::class);
$otherObjectManager->add(OtherObject\SinglePrecisionFloatObject::class);
$otherObjectManager->add(OtherObject\DoublePrecisionFloatObject::class);

$tagManager = new Tag\TagObjectManager();
$tagManager->add(Tag\DatetimeTag::class);
$tagManager->add(Tag\TimestampTag::class);
$tagManager->add(Tag\PositiveBigIntegerTag::class);
$tagManager->add(Tag\NegativeBigIntegerTag::class);
$tagManager->add(Tag\DecimalFractionTag::class);
$tagManager->add(Tag\BigFloatTag::class);
$tagManager->add(Tag\Base64UrlEncodingTag::class);
$tagManager->add(Tag\Base64EncodingTag::class);
$tagManager->add(Tag\Base16EncodingTag::class);

$decoder = new Decoder($tagManager, $otherObjectManager);
```

Then, the decoder will read the data you want to load.
The data has to be handled by an object that implements the `CBOR\Stream` interface.
This library provides a `CBOR\StringStream` class to stream the string.

```php
<?php

use CBOR\StringStream;

// CBOR object (in hex for the example)
$data = hex2bin('fb3fd5555555555555');

// String Stream
$stream = new StringStream($data);

// Load the data
$object = $decoder->decode($stream); // Return a CBOR\OtherObject\DoublePrecisionFloatObject class with normalized value ~0.3333 (1/3)
```

# Contributing

Requests for new features, bug fixed and all other ideas to make this project useful are welcome.
The best contribution you could provide is by fixing the [opened issues where help is wanted](https://github.com/Spomky-Labs/cbor-php/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22).

Please report all issues in [the main repository](https://github.com/Spomky-Labs/cbor-php/issues).

Please make sure to [follow these best practices](.github/CONTRIBUTING.md).

# Security Issues

If you discover a security vulnerability within the project, please **don't use the bug tracker and don't publish it publicly**.
Instead, all security issues must be sent to security [at] spomky-labs.com. 

# Licence

This project is release under [MIT licence](LICENSE).
