# AGENTS.md

Instructions for AI coding agents working on `jamiethompson/cikmov`.

The README is the authoritative description of behaviour and grammar rules.
This file defines constraints and change discipline.

---

## 1. Project Intent

`cikmov` is a deterministic UK postcode format analysis and correction library.

It performs:
- Structural grammar validation
- Deterministic candidate generation
- Rule-backed correction scoring

It does NOT perform:
- Allocation or existence validation
- External API lookups
- Probabilistic or fuzzy matching
- Heuristic guesswork

If behaviour is unclear, consult README.md.

---

## 2. Non-Negotiable Constraints

Agents must not:

- Introduce fuzzy matching (e.g. Levenshtein, distance metrics)
- Add probabilistic scoring
- Broaden grammar rules without explicit tests
- Loosen Result invariants
- Add public API surface casually

All behaviour must remain:

- Deterministic
- Reproducible
- Rule-backed
- Explainable

---

## 3. Public API Stability

Current public API:

Cikmov::analyse(string $input, int $minConfidenceToApply = 85): Result

Do not:

- Change method signature
- Rename classes
- Remove fields from Result
- Weaken constructor invariants

If a breaking change is required:

- Bump major version
- Update README.md
- Update AGENTS.md
- Use a `feat!:` Conventional Commit

---

## 4. Behaviour Changes

When modifying:

- Grammar rules
- Area prefix set
- AA9A constraints
- Scoring penalties
- Ambiguity handling
- Threshold semantics

You must:

1. Add or update PHPUnit tests first.
2. Ensure idempotency tests still pass.
3. Update README.md if behaviour changes.
4. Update AGENTS.md if constraints or intent changes.

No silent behavioural drift.

---

## 5. Testing Discipline

All behavioural changes must include tests.

Required coverage areas when relevant:

- Valid canonical inputs
- Invalid structural inputs
- Deterministic corrections
- Ambiguous scenarios
- Threshold behaviour
- Result invariant enforcement
- Idempotency

Run:

composer test

No change is complete without green tests.

---

## 6. Documentation Synchronisation Rules

If any of the following changes:

- Grammar constraints
- Area set
- Confusion maps
- Confidence model
- Public API
- Scope definition

Then:

- Update README.md to reflect current facts.
- Update AGENTS.md if constraints or expectations changed.
- Do not allow README and implementation to diverge.

Documentation drift is considered a defect.

---

## 7. Commit Discipline

All commits MUST:

- Use Conventional Commits syntax.
- Be descriptive.
- Reflect the actual change.

Examples:

- feat: add stricter AA9A district validation
- fix: prevent tie candidates from auto-applying
- test: add inward digit confusion edge cases
- refactor: simplify candidate scoring loop
- docs: clarify confidence threshold behaviour

Breaking changes must use:

feat!: description

Commits like “update”, “fix stuff”, or “changes” are unacceptable.

---

## 8. Files of Interest

- src/Cikmov.php – public entry point
- src/Result.php – immutable result object
- src/Internal/Analyser.php – correction engine
- src/Internal/PostcodeRules.php – grammar rules
- tests/ – behavioural test suite

Agents must not modify internal logic without reviewing tests.

---

## 9. Packaging Rules

If .gitattributes is used to exclude dev files:

- Maintain export-ignore rules.
- Ensure runtime install contains only required production files.
- Tag a new version when packaging-related changes occur.

---

## 10. When in Doubt

- Prefer rejecting ambiguous input over applying a risky correction.
- Prefer smaller change sets.
- Prefer explicit tests over assumed behaviour.
- Prefer determinism over cleverness.

The library’s value lies in strictness, not permissiveness.