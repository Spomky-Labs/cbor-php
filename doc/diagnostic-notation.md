# Diagnostic Notation

`CBOR\Diagnostic\DiagnosticNotation` writes a CBOR item the way RFC 8949 section 8 does: the *diagnostic notation*
that the RFC test vectors, the IANA registries and the appendices of the COSE RFCs use to show what bytes mean.

```php
use CBOR\Decoder;
use CBOR\Diagnostic\DiagnosticNotation;
use CBOR\StringStream;

$item = Decoder::create()->decode(StringStream::create(hex2bin('9f018202039f0405ffff')));

echo DiagnosticNotation::create()->singleLine()->encode($item);
// [_ 1, [2, 3], [_ 4, 5]]
```

One item per line by default, `singleLine()` for the form of the test vectors:

```php
echo DiagnosticNotation::create()->encode($item);
// [_
//   1,
//   [
//     2,
//     3
//   ],
//   [_
//     4,
//     5
//   ]
// ]
```

## What the notation looks like

| Item | Notation |
|---|---|
| Integers | `1`, `-1`, `18446744073709551615` |
| Floats | `1.5`, `1.0e+300`, `Infinity`, `-Infinity`, `NaN`, always with a fraction: `1.0` |
| Byte string | `h'01020304'`, or `'text'` with `withBytesAsText()` when the bytes are printable |
| Text string | `"IETF"`, `"\"\\"`, escaped as JSON does |
| Indefinite-length string | `(_ h'0102', h'030405')`, `(_ "strea", "ming")` |
| Array, map | `[1, [2, 3]]`, `{"a": 1, "b": [2, 3]}`; indefinite-length: `[_ 1, 2]`, `{_ "a": 1}` |
| Tag | `1(1363896240)`, `2(h'010000000000000000')` |
| Simple values | `true`, `false`, `null`, `undefined`, `simple(16)` |
| Embedded CBOR | `<< {1: -7} >>`, for a byte string a schema says carries CBOR |

The encoder is immutable: `singleLine()`, `withBytesAsText()` and `withIndent()` return a new one.

`withBytesAsText()` is off by default on purpose. RFC 8949 section 8.1 allows `'text'` for a byte string whose
content is text, and RFC 9052 Appendix C writes its payloads that way, but bytes that happen to be printable would
be misread: `h'6449455446'` of RFC 8949 Appendix A spells `dIETF`.

## Annotating with CDDL

Given a `Schema`, the notation does what the appendices of the COSE RFCs do by hand: it names every item after the
CDDL production it instantiates, opens the byte strings that carry CBOR, spells the value of a registry, and quotes
the productions above the document. RFC 9052 Appendix C.2.1 comes out as the appendix prints it:

```php
use CBOR\Diagnostic\Schema;

$schema = Schema::create()
    ->addTag(18, 'COSE_Sign1', 'COSE_Sign1_Tagged = #6.18(COSE_Sign1)')
    ->addArray('COSE_Sign1', [
        'protected' => 'bstr .cbor header_map',
        'unprotected' => 'header_map',
        'payload' => 'bstr / nil',
        'signature' => 'bstr',
    ], 'COSE_Sign1 = [ Headers, payload : bstr / nil, signature : bstr ]')
    ->addMap('header_map', [
        1 => ['alg', 'alg-id'],
        4 => 'kid',
    ], 'header_map = { * label => values }')
    ->addRegistry('alg-id', [-7 => 'ES256']);

$message = Decoder::create()->decode(StringStream::create(hex2bin('d28443a10126a10442313154546869732069732074686520636f6e74656e742e5840...')));

echo DiagnosticNotation::create($schema)->withBytesAsText()->render($message);
```

```
; COSE_Sign1_Tagged = #6.18(COSE_Sign1)
; COSE_Sign1 = [ Headers, payload : bstr / nil, signature : bstr ]
; header_map = { * label => values }
18([
  / protected h'a10126' / << {
    / alg / 1: -7 / ES256 /
  } >>,
  / unprotected / {
    / kid / 4: '11'
  },
  / payload / 'This is the content.',
  / signature / h'8eb33e4c...'
])
```

`render()` returns a `Rendering`: `getNotation()` is the notation alone, `getCddl()` the CDDL of the productions the
document instantiates, each once, in order of first use, and the string form is both, the CDDL as `;` comment lines.
`encode()` is the notation alone.

### Declaring productions

A schema is a handful of declarations, each named after the production of the RFC it comes from, and each carrying
the CDDL text so that a rendering can quote it. The text is documentation: the structure comes from the declaration.

| Declaration | What it is | How it renders |
|---|---|---|
| `addArray($name, $fields, $cddl)` | An array: the name and type of each item, in order | `[ / protected / ..., / unprotected / ... ]` |
| `addMap($name, $entries, $cddl)` | A map: for each label, the name of the entry and the type of its value | `{ / alg / 1: -7, / kid / 4: h'..' }` |
| `addRegistry($name, $names, $cddl)` | The name each integer value stands for | `-7 / ES256 /` |
| `addTag($number, $production, $cddl)` | The production a tag wraps | `18([ ... ])`, read as that production |

Fields and entries are typed with a **CDDL type expression**: enough of RFC 8610 to name things and to open byte
strings that carry CBOR. The forms, combined with `/` into a choice:

| Expression | Meaning |
|---|---|
| `header_map` | A production of the schema. A name that is never declared reads as `any`, so a production can refer to one declared after it, or to itself |
| `bstr .cbor header_map` | A byte string whose content is CBOR, shown as `<< ... >>` and read as the production |
| `[+ COSE_Signature]`, `[* bstr]` | An array whose items are all of one type; each item that is a production is named: `/ COSE_Signature / [` |
| `#6.19(COSE_Countersignature)` | A tag number around a type |
| `"Signature1"` | A text literal |
| `bstr`, `int`, `nil`, `any`, ... | A name of the CDDL prelude, with or without a control operator: `bstr .size 0` is `bstr` |
| `COSE_Countersignature / [+ COSE_Countersignature]` | A choice: the first alternative that accepts the item wins, and the item is named after it |

A choice is resolved on the shape of the item, looking one level into a list: `COSE_Countersignature` is an array
whose first item is a byte string, `[+ COSE_Countersignature]` one whose first item is a list, so RFC 9338's label
11, "COSE_Countersignature / [+ COSE_Countersignature]", is told apart without a tag on the wire.

Every type applies through a tag: a `COSE_Countersignature` field reads `19([...])` as one and writes the tag
number as it is (RFC 9338 section 3.1: "can be encoded as either tagged or untagged").

### Adding a production

Adding the productions of another RFC is three lines per production, copied from its CDDL. RFC 9338:

```php
$schema
    ->addTag(19, 'COSE_Countersignature', 'COSE_Countersignature_Tagged = #6.19(COSE_Countersignature)')
    ->addArray('COSE_Countersignature', [
        'protected' => 'bstr .cbor header_map',
        'unprotected' => 'header_map',
        'signature' => 'bstr',
    ], 'COSE_Countersignature = COSE_Signature')
    ->addMap('header_map', [
        // ...
        11 => ['Countersignature version 2', 'COSE_Countersignature / [+ COSE_Countersignature]'],
        12 => ['Countersignature0 version 2', 'bstr'],
    ]);
```

Names are resolved when a document is rendered, so declarations can come in any order. What the schema knows
nothing about is written as it is: an undeclared tag with its number, an undeclared label without a name, a value
that is not what its type expects as the plain item, a byte string that does not decode as bytes.

### What the notation cannot know

Nothing on the wire says what a bare array or map is. A tag says it, and so does a text literal declared as the
first field of an array production (a list opening with `"Signature1"` is a `Sig_structure`); for the rest, the
caller says:

```php
DiagnosticNotation::create($schema)->encode($map, 'COSE_Key');
```

## Reading the bytes back

The notation is meant to be read next to the hexadecimal it was produced from. The bytes of a byte string a schema
opens are kept in the comment, `/ protected h'a10126' / << { ... } >>`, so that what was signed and what it means
sit on the same line, the way RFC 9052 Appendix C shows a protected header.
