# CBOR for PHP

[![CI](https://github.com/Spomky-Labs/cbor-php/actions/workflows/ci.yml/badge.svg)](https://github.com/Spomky-Labs/cbor-php/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/spomky-labs/cbor-php/v)](https://packagist.org/packages/spomky-labs/cbor-php)
[![Total Downloads](https://poser.pugx.org/spomky-labs/cbor-php/downloads)](https://packagist.org/packages/spomky-labs/cbor-php)
[![License](https://poser.pugx.org/spomky-labs/cbor-php/license)](https://packagist.org/packages/spomky-labs/cbor-php)

A comprehensive PHP library for encoding and decoding **CBOR** (Concise Binary Object Representation) data according to [RFC 8949](https://tools.ietf.org/html/rfc8949).

## Features

- ✅ Full support for all CBOR major types (0-7)
- ✅ Extensible tag system with built-in support for common tags
- ✅ Streaming decoder for efficient memory usage
- ✅ Type-safe API with modern PHP 8.0+ features
- ✅ Comprehensive support for indefinite-length objects
- ✅ Built-in normalization to PHP native types

## Installation

```bash
composer require spomky-labs/cbor-php
```

**Requirements:**
- PHP 8.0 or higher
- ext-mbstring
- brick/math

**Optional but recommended:**
- ext-gmp or ext-bcmath for improved performance with large integers
- ext-bcmath for Big Float and Decimal Fraction support

This project follows [semantic versioning](http://semver.org/) strictly.

## Quick Start

```php
<?php

use CBOR\Decoder;
use CBOR\MapObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;

// Encoding: Create a CBOR object
$map = MapObject::create()
    ->add(TextStringObject::create('name'), TextStringObject::create('John Doe'))
    ->add(TextStringObject::create('age'), UnsignedIntegerObject::create(30));

$encoded = (string) $map;

// Decoding: Parse CBOR data
$decoder = Decoder::create();
$decoded = $decoder->decode(StringStream::create($encoded));

// Normalize to native PHP types
$data = $decoded->normalize();
// ['name' => 'John Doe', 'age' => '30']
```

## Documentation

📖 **[cbor.spomky-labs.com](https://cbor.spomky-labs.com/3.4/)** - the full documentation: the API, the tags, the guides and the Symfony bundle

### Quick Links

- **[Tags Reference](https://cbor.spomky-labs.com/3.4/tags/built-in/)** - Complete guide to the 70+ supported CBOR tags
- **[Creating Custom Tags](https://cbor.spomky-labs.com/3.4/tags/writing-a-tag/)** - Implement your own tags for domain-specific needs
- **[API Reference](https://cbor.spomky-labs.com/3.4/guide/encoding/)** - Encoding and decoding API
- **[Examples](https://cbor.spomky-labs.com/3.4/examples/webauthn/)** - WebAuthn, COSE, IoT, and more

### Major Types Overview

This library supports all CBOR major types defined in RFC 8949:

| Major Type | Description | Classes |
|------------|-------------|---------|
| 0 | Unsigned Integer | `UnsignedIntegerObject` |
| 1 | Negative Integer | `NegativeIntegerObject` |
| 2 | Byte String | `ByteStringObject`, `IndefiniteLengthByteStringObject` |
| 3 | Text String | `TextStringObject`, `IndefiniteLengthTextStringObject` |
| 4 | Array | `ListObject`, `IndefiniteLengthListObject` |
| 5 | Map | `MapObject`, `IndefiniteLengthMapObject` |
| 6 | Tag | `Tag` and subclasses - [See Tags Reference](https://cbor.spomky-labs.com/3.4/tags/built-in/) |
| 7 | Other | `TrueObject`, `FalseObject`, `NullObject`, etc. |

**Common API:**
- All objects have a static `create()` method for instantiation
- All objects can be converted to binary: `(string) $object`
- Many objects implement `Normalizable` to convert to native PHP types

### Basic Usage Examples

For complete documentation with all examples, see the [**Documentation**](https://cbor.spomky-labs.com/3.4/).

#### Working with Basic Types

```php
use CBOR\UnsignedIntegerObject;
use CBOR\TextStringObject;
use CBOR\ListObject;
use CBOR\MapObject;

// Integers
$number = UnsignedIntegerObject::create(42);

// Strings
$text = TextStringObject::create('Hello World');

// Arrays
$list = ListObject::create([
    UnsignedIntegerObject::create(1),
    TextStringObject::create('two'),
]);

// Maps/Objects
$map = MapObject::create()
    ->add(TextStringObject::create('key'), TextStringObject::create('value'));
```

#### Working with Tags

**📖 For complete tags documentation, see [Tags Reference](https://cbor.spomky-labs.com/3.4/tags/built-in/)**

```php
use CBOR\Tag\TimestampTag;
use CBOR\Tag\DecimalFractionTag;
use CBOR\UnsignedIntegerObject;

// Timestamps
$timestamp = TimestampTag::create(UnsignedIntegerObject::create(time()));
$dateTime = $timestamp->normalize(); // DateTimeImmutable

// Decimal fractions
$decimal = DecimalFractionTag::createFromFloat(3.14159);
echo $decimal->normalize(); // "3.14159"
```

**Supported Tags:**
- Date/Time (Tags 0, 1) and dates without a time (Tags 100, 1004)
- Big Numbers (Tags 2, 3), Decimal/Binary Fractions (Tags 4, 5) and Rationals (Tag 30)
- Encoding hints (Tags 21, 22, 23), embedded CBOR (Tags 24, 63)
- URIs, MIME and UUIDs (Tags 32, 36, 37, 257)
- COSE structures and CBOR Web Tokens (Tags 16, 17, 18, 61, 96, 97, 98)
- Typed and multi-dimensional arrays (Tags 40, 41, 64-87, 1040)
- IP and network addresses (Tags 52, 54, 260, 261)
- [And more...](https://cbor.spomky-labs.com/3.4/tags/built-in/)

**Create your own:** See [Creating Custom Tags](https://cbor.spomky-labs.com/3.4/tags/writing-a-tag/) guide.

### Complete Example

```php
use CBOR\Decoder;
use CBOR\MapObject;
use CBOR\ListObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use CBOR\StringStream;
use CBOR\Tag\TimestampTag;

// Build a complex nested structure
$data = MapObject::create()
    ->add(
        TextStringObject::create('user'),
        MapObject::create()
            ->add(TextStringObject::create('name'), TextStringObject::create('Alice'))
            ->add(TextStringObject::create('age'), UnsignedIntegerObject::create(30))
    )
    ->add(
        TextStringObject::create('scores'),
        ListObject::create([
            UnsignedIntegerObject::create(95),
            UnsignedIntegerObject::create(87),
        ])
    )
    ->add(
        TextStringObject::create('timestamp'),
        TimestampTag::create(UnsignedIntegerObject::create(time()))
    );

// Encode to binary
$encoded = (string) $data;

// Decode back
$decoder = Decoder::create();
$decoded = $decoder->decode(StringStream::create($encoded));

// Convert to native PHP types
$phpData = $decoded->normalize();
```

For more examples including WebAuthn, COSE, IoT scenarios, and advanced usage, see the [complete documentation](https://cbor.spomky-labs.com/3.4/examples/webauthn/).

## Contributing

Contributions are welcome! Here's how you can help:

- Report bugs and request features via [GitHub Issues](https://github.com/Spomky-Labs/cbor-php/issues)
- Fix [issues labeled "help wanted"](https://github.com/Spomky-Labs/cbor-php/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22)
- Improve documentation
- Submit pull requests

Please read our [contribution guidelines](.github/CONTRIBUTING.md) before submitting.

## Support

If you find this project valuable, consider supporting its development:

- [Become a GitHub Sponsor](https://github.com/sponsors/Spomky)
- [Support via Patreon](https://www.patreon.com/FlorentMorselli)

## License

This project is released under the [MIT License](LICENSE).

---

**Maintained by:** [Florent Morselli](https://github.com/Spomky) and [contributors](https://github.com/Spomky-Labs/cbor-php/contributors)
