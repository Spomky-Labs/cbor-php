# Threat model

This file steers the [OSS Scanner](https://github.com/anthropics/oss-scanner). Vulnerabilities found by other means
are reported as described in [SECURITY.md](../SECURITY.md).

## What this project does

`spomky-labs/cbor-php` encodes and decodes CBOR (RFC 8949) in PHP. It has no framework dependency.

- `Decoder` (`src/Decoder.php`) reads one data item from a `Stream` and builds a tree of `CBORObject`: one class per
  major type in `src/` and `src/OtherObject`, one class per registered tag in `src/Tag` and `src/Tag/TypedArray`,
  and `GenericTag` for a tag number it does not know. `TagManager` maps tag numbers to classes.
- `normalize()` (the `Normalizable` interface) turns a decoded tree into native PHP values: arrays, numeric strings,
  floats, `DateTimeImmutable` objects and so on.
- Every object writes itself back as CBOR when cast to a string. `Encoder` turns PHP values into CBOR, and the
  `create()` factories of each class build items by hand.
- `src/Diagnostic` writes a decoded item in diagnostic notation (RFC 8949 section 8), optionally annotated with the
  CDDL of a `Schema`.

Its main consumer is `web-auth/webauthn-lib`, the server side of WebAuthn. The attestation objects, COSE public keys
and authenticator extensions it decodes are CBOR written by the browser or the authenticator, so a hostile document
reaches this library from an unauthenticated request.

## Where untrusted input enters

The attacker controls the bytes handed to `Decoder::decode()`: any document, well-formed or not, as large as the
application accepts. Everything that runs on them is attack surface:

- The head parser in `Decoder::process()`, `processFinite()` and `processInfinite()`: initial bytes, 1 to 8 byte
  arguments (`Utils::binToInt()`), lengths, item counts, indefinite-length items and break codes, tag numbers, and
  the nesting depth checked against the `$maxDepth` of the decoder.
- `StringStream::read()`, which serves the bytes to the decoder.
- The constructors and `createFromLoadedData()` of the tag classes, which `TagManager::createObjectForValue()` calls
  during `decode()` to validate the tagged item, and `OtherObjectManager` for simple values and floats.
- The map key registry (`MapKeyRegistryTrait`, used by `MapObject` and `IndefiniteLengthMapObject`). It normalizes
  every key that is not a list or a map as the map is built, so the `normalize()` of any tag or scalar runs inside
  `decode()` when the item is used as a key.
- `normalize()` on any object of the tree. Applications call it on the whole document: webauthn-lib normalizes the
  attestation object before validating anything in it.
- The accessors of decoded objects: `get()`, `has()` and iteration of lists and maps, `getValue()`, the typed array
  accessors, and the re-encoding of a decoded tree. `CBORSequenceTag::getSequence()` and
  `AbstractCoseTag::getProtectedHeaderAsMap()` decode the bytes of a byte string again.
- `DiagnosticNotation`, when an application renders a decoded document, for example to log it. With a `Schema`, it
  also decodes the byte strings the schema says carry CBOR.

What is trusted:

- The configuration of the decoder: the tag manager, the other object manager and the maximum depth. A tag class or
  a `Stream` implementation the application provides is its own code.
- The structure of what the application encodes. The contents of the strings and numbers it passes to `Encoder` or
  to the `create()` factories may come from an attacker; they must not change the structure of the document written.
- PHP, its extensions (`mbstring`, `gmp`, `bcmath`) and `brick/math`.

## Security properties that must hold

- Error contract: on any input, `decode()` and `normalize()` return or throw `InvalidArgumentException`. The only
  other exception is the documented `RuntimeException` of a tag that needs a missing extension (tags 4 and 5 without
  `ext-bcmath`). A `TypeError`, a `ValueError`, an `AssertionError`, a brick/math exception or any other throwable
  escaping from them is a defect, as are warnings and notices, which framework error handlers turn into exceptions.
- No input ends the process through a call stack exhaustion or a segmentation fault, nor through a memory exhaustion
  out of proportion to the size of the document; no `try`/`catch` can intercept them. Nesting deeper than the depth
  limit (`Decoder::DEFAULT_MAX_DEPTH`, 1000, or the one the application sets) is rejected with
  `InvalidArgumentException`.
- Time and memory stay in proportion to the size of the document, in `decode()` and in `normalize()`, with or without
  `ext-gmp` and `ext-bcmath`. The bounds documented in `doc/index.md` ("Bounding the Cost of a Hostile Document")
  hold: `DecimalFractionTag::MAX_ABSOLUTE_EXPONENT` and `BigFloatTag::MAX_ABSOLUTE_EXPONENT` (1024),
  `UnsignedBigIntegerTag::MAX_BYTE_LENGTH` and `NegativeBigIntegerTag::MAX_BYTE_LENGTH` (256).
- `decode()` reads exactly one data item and nothing after it. webauthn-lib reads the credential public key and then
  the extensions from the same stream, and checks itself that nothing follows.
- A decoded object writes back exactly the bytes it was decoded from. webauthn-lib keeps the re-encoded bytes of the
  COSE key, and `CBORSequenceTag::getSequence()` relies on the round trip to find where each item ends.
- A map holds every key of the document. Two distinct keys never collapse onto one entry, a duplicate key is
  rejected, and `get()`, `has()` and `normalize()` never return a value stored under another key. A key that cannot
  be told apart through a PHP array offset (a float, a boolean, null, a list, a map, or two keys of different major
  types resolving to the same offset) makes `normalize()` throw rather than pick one, as `doc/index.md` describes.
- A normalized value is the value the bytes encode: integers beyond `PHP_INT_MAX` are numeric strings and are never
  wrapped or truncated. As `doc/tags.md` states, a tag whose content does not match its definition is rejected with
  `InvalidArgumentException` rather than decoded into something it is not, and a date that does not exist is
  rejected rather than rolled over.
- Nothing in the document names code to run: `PerlObjectTag` and `LanguageIndependentObjectTag` return the class name
  and the arguments as data and instantiate nothing, and references (tags 25 and 29) are returned as indexes, not
  resolved.

## Components that matter most

- `src/Decoder.php`, `src/StringStream.php`, `src/Utils.php` and `src/IntegerPayloadTrait.php`: the head parser and
  the conversion of arguments to PHP integers.
- `src/MapKeyRegistryTrait.php`, `src/MapObject.php` and `src/IndefiniteLengthMapObject.php`: key identity, duplicate
  detection and the normalization of keys during `decode()`.
- `src/Tag/TagManager.php` and the tag classes whose constructor or `normalize()` computes something rather than
  wrapping the item: big numbers (`UnsignedBigIntegerTag`, `NegativeBigIntegerTag`), `DecimalFractionTag`,
  `BigFloatTag`, `RationalNumberTag`, dates and times (`DatetimeTag`, `TimestampTag`, `DateTag`, `DateStringTag`,
  `ExtendedTimeTag`), typed and multi-dimensional arrays (`src/Tag/TypedArray`, `RowMajorMultiDimensionalArrayTag`,
  `ColumnMajorMultiDimensionalArrayTag`), IP and network addresses, and `CBORSequenceTag`.
- `src/OtherObject`: the half, single and double precision floats and the simple values.
- The encoding side: `src/Encoder.php`, `src/LengthCalculator.php`, and the heads written by the `create()`
  factories and by `__toString()`.

Less important but in scope: `src/Diagnostic`, the list and string objects, the COSE and CWT tags, which check the
shape of their content and verify no signature. Deprecated code paths, such as `SelfDescribeCBORTag`, are in scope
as long as they ship.

## How to exercise it

The image has PHP 8.4 with `ext-gmp` and `ext-bcmath`, the dependencies installed in `vendor/`, and the QA tools of
the CI image on the `PATH`. Run the tests as the CI does, with the `phpunit-11` of the image; this repository does
not install PHPUnit in `vendor/`:

    composer exec -- phpunit-11 --configuration .ci-tools/phpunit.xml.dist
    composer exec -- phpunit-11 --configuration .ci-tools/phpunit.xml.dist --filter HostileDocumentTest

- A plain `composer require` gives neither `ext-gmp` nor `ext-bcmath`, and `brick/math` then falls back to its pure
  PHP calculator. `php -n` loads none of the shared extensions of the image (`mbstring` is built in) and reproduces
  that installation: `php -n /tools/phpunit-11 --configuration .ci-tools/phpunit.xml.dist --filter BigNumTagTest`.
  The tests that use tags 4 and 5 fail there by design, since these tags need `ext-bcmath`.
- The image sets `memory_limit=-1`. Pass `-d memory_limit=128M`, the PHP default, to see a memory exhaustion as a
  production server reports it.
- Tests live in `tests/` and its subdirectories, and extend `CBORTestCase`, whose `getDecoder()` returns a default
  decoder.
  `HostileDocumentTest`, `ExceptionContractTest`, `MapKeyConfusionTest`, `MaxDepthTest`, `UnboundedExponentTest` and
  `RobustnessTest` hold the regression tests of past reports and show how a reproducer is written. `VectorTest`
  replays the documents of `tests/vectors.json`.

## How we rate severity

- Critical: executing code, reading or writing files, or instantiating a class the document names, through
  `decode()`, `normalize()` or another method of a decoded object.
- High: a document that ends the process (memory exhaustion, call stack exhaustion, segmentation fault) or holds it
  for seconds, at a cost out of proportion to its size, in the default installation (neither `ext-gmp` nor
  `ext-bcmath`, the default depth limit). A difference between the bytes and the decoded tree, its normalized values
  or its re-encoding, with a demonstrated effect on a consumer that makes security decisions on them, such as a
  WebAuthn or COSE implementation. Contents given to the encoder that change the structure of the document written.
- Medium: an exception outside the error contract from `decode()` or `normalize()`, including on a valid document;
  a difference between the bytes and the decoded tree without a demonstrated effect on a consumer (two keys merged
  into one entry, a duplicate key accepted, a value wrapped or truncated); a cost out of proportion to the size of
  the document that stays catchable and needs a large document.
- Low: a defect reachable only through values the application chooses, when the encoded structure is not affected;
  a wrong diagnostic notation; a missing validity check without a demonstrated consequence; parts of the document
  echoed in exception messages.

For calibration, the published advisories were rated as follows:

- GHSA-pf28-pvhp-7mm5, unbounded exponent in tags 4 and 5, an 11 byte document exhausting memory with an
  uncatchable fatal error: high.
- GHSA-jfrf-557c-963v, quadratic map normalization, memory exhaustion through tag 4 and 5 map keys, quadratic big
  number conversion without `ext-gmp`, and two exceptions outside the error contract: high.
- GHSA-388j-mw2g-rx5f, map keys stored as bare PHP array offsets: a `TypeError` on list and map keys, and silent
  collisions between keys of different types: medium.
- GHSA-pjwm-422x-vvh5, exceptions outside the error contract on 8 byte headers and on empty big numbers, with an
  `assert()` used as validation: medium.

## Anything to leave alone

- Bugs inside `brick/math`, the Symfony polyfills, PHP or its extensions: they are separate projects. How this
  library calls them (an input it hands over unchecked, a slow path it lets a document trigger) is in scope.
- `tests/`, `doc/`, `.ci-tools/`, `.github/` and `castor.php`.
- Memory and time that grow linearly with the document. The object model costs about a hundred times the bytes it
  decodes (a list of one million small integers, 1 MB on the wire, takes about 120 MB once decoded), and bounding the
  size of the documents it accepts is the application's job.
- What RFC 8949 leaves to the application: checking that the stream holds nothing after the item, resolving string
  and value references, and the validity checks a generic decoder may skip (section 5.3), such as text strings that
  are not valid UTF-8 or heads not in preferred serialization, which are kept as they are so that the round trip
  stays exact. They are in scope when they break one of the properties above.
- A depth limit the application raises above the default, a tag class or a `Stream` implementation it provides.

## Reports

Include a reproducer written as a PHPUnit test in `tests/`, the document it decodes in hexadecimal, what it needs
when it is not the default installation (extensions, `memory_limit`, PHP version), and a patch against the scanned
branch. The maintainers port the fix to the maintained branches.
