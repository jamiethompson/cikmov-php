# cikmov

Deterministic UK postcode analysis and correction for format-level validation.

## Why This Library Exists

UK postcodes are not free-form text. They are a constrained grammar designed for human use and machine sorting.

The system was designed to:

- support mechanical and automated sorting
- reduce transcription errors
- avoid visually ambiguous character patterns
- encode geography hierarchically
- remain human-readable

`cikmov` models these constraints directly in code and uses deterministic candidate generation plus rule filtering instead of fuzzy matching.

## Constraint-Based, Not Fuzzy

This library intentionally does **not** use probabilistic matching, distance metrics, or fuzzy heuristics.

Why:

- postcode structure is finite and strongly constrained
- invalid candidates can be eliminated deterministically
- behaviour stays explainable, reproducible, and testable
- correction risk is lower when every decision is rule-backed

## Public API

```php
<?php

use Cikmov\Cikmov;

$result = Cikmov::analyse('ec1a ial');
```

Single public entrypoint:

```php
Cikmov::analyse(string $input, int $minConfidenceToApply = 85): Result;
```

No configuration object is exposed in v1.

## Result Object

`Result` is a final immutable value object using `public readonly` properties.

Fields:

- `input`: original raw input
- `normalizedInput`: uppercase normalized form used during analysis
- `inputWasValid`: whether normalized input was already structurally valid
- `bestCandidate`: highest scoring canonical candidate, if any
- `confidence`: numeric confidence (`0-100`)
- `appliedPostcode`: applied correction when confidence meets threshold
- `alternatives`: other high-ranked canonical candidates for ambiguity reporting

Defensive invariants are enforced in the constructor (confidence bounds, canonical formatting, uniqueness rules, consistency between flags and values).

## Grammar Rules Enforced

A postcode is treated as:

```text
[outward] [inward]
```

### Inward unit

Pattern:

```text
digit letter letter
```

Rules:

- first inward character must be `0-9`
- last two inward characters must be `A-Z`
- last two inward characters must not contain `C I K M O V`

The CIKMOV exclusion exists because those letters are visually error-prone in the inward unit.

### Outward formats

Allowed structural forms:

```text
A9
A9A
A99
AA9
AA9A
AA99
```

Additional rules:

- first outward letter cannot be `Q V X`
- second outward letter (when present) cannot be `I J Z`
- first outward digit is constrained to `1-9` (no leading zero district)
- area prefix must exist in the embedded official area list

### AA9A special restrictions

`AA9A` is geographically constrained, not globally available.

Rules:

- fourth outward character must be one of: `A B E H M N P R V W X Y`
- allowed area/district combinations:
  - `EC` with district `1-4`
  - `SW` with district `1`
  - `WC` with district `1-2`
  - `NW` only `NW1W`
  - `SE` only `SE1P`

Why restricted:

- this pattern reflects specific London district conventions rather than a general pattern.

### Special recognised code

- `GIR 0AA` is explicitly recognised as valid format.

### Non-geographic area prefixes

Included as valid area prefixes:

- `BF`
- `BX`

## Area Prefix Enforcement

Area prefix validation is mandatory in v1. There is no bypass flag.

Why:

- format validity should reflect real structural postcode grammar
- optional bypass weakens deterministic correctness and increases false positives

## Deterministic Scoring Model

Candidate generation is positional:

1. normalize input (`uppercase`, remove non-alphanumeric separators/noise)
2. generate substitutions only where character class mismatches occur (digit/letter confusion maps)
3. prune candidates that violate grammar constraints
4. score surviving candidates numerically (`0-100`)
5. select highest score deterministically (score desc, lexical tiebreak)

Scoring policy:

- edits in outward positions are penalized more than inward positions
- this reflects higher structural significance of outward geography encoding
- ambiguity lowers confidence further

Why outward edits are penalized more:

- outward errors are more likely to alter geographic interpretation
- inward unit is designed for finer routing granularity and tolerates fewer distinct transformations

## Threshold Policy

Correction is applied only when:

```text
confidence >= minConfidenceToApply
```

Default threshold is `85`.

Recommended guidance:

- `90-95`: conservative, lower false positives
- `85`: balanced default
- `70-80`: aggressive correction, more candidate acceptance

## Format Validity vs Existence Validation

This library validates and corrects **format grammar only**.

It does not:

- verify that a postcode is currently allocated
- verify that an address is deliverable
- query any external dataset/API

Why out of scope:

- keeps behaviour deterministic and offline
- avoids stale or jurisdiction-specific allocation data dependency
- maintains cross-language portability of the core algorithm

## Examples

### 1) Valid input

```php
$result = Cikmov::analyse('EC1A 1AL');
// inputWasValid: true
// bestCandidate: "EC1A 1AL"
// confidence: 100
// appliedPostcode: "EC1A 1AL"
// alternatives: []
```

### 2) Deterministic correction

```php
$result = Cikmov::analyse('EC1A IAL');
// bestCandidate: "EC1A 1AL"
// confidence: 96
// appliedPostcode: "EC1A 1AL" (default threshold 85)
```

### 3) Ambiguous correction

```php
$result = Cikmov::analyse('B01 8TH');
// bestCandidate: e.g. "BD1 8TH"
// alternatives: non-empty
// confidence: reduced because near competing candidates exist
// appliedPostcode may be null if confidence falls below threshold
```

### 4) Rejection

```php
$result = Cikmov::analyse('!!!!');
// bestCandidate: null
// confidence: 0
// appliedPostcode: null
```

### 5) CIKMOV rejection

```php
$result = Cikmov::analyse('EC1A 1AI');
// invalid due inward forbidden letter I
// no correction is applied
```

## Embedded Postcode Areas

The full area set is embedded and enforced:

`AB AL B BA BB BD BF BH BL BN BR BS BT BX CA CB CF CH CM CO CR CT CV CW DA DD DE DG DH DL DN DT DY E EC EH EN EX FK FY G GL GU GY HA HD HG HP HR HS HU HX IG IM IP IV JE KA KT KW KY L LA LD LE LL LN LS LU M ME MK ML N NE NG NN NP NR NW OL OX PA PE PH PL PO PR RG RH RM S SA SE SG SK SL SM SN SO SP SR SS ST SW SY TA TD TF TN TQ TR TS TW UB W WA WC WD WF WN WR WS WV YO ZE`

## Testing

The PHPUnit suite covers:

- grammar and normalization behaviour
- correction behaviour and scoring outcomes
- ambiguity and alternatives
- AA9A positive/negative constraints
- area enforcement
- CIKMOV exclusion
- invalid input rejection
- `GIR 0AA` handling
- Northern Ireland format handling
- idempotency
- `Result` invariants

