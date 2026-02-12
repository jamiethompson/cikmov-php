<?php

declare(strict_types=1);

namespace Cikmov;

use InvalidArgumentException;

final class Result
{
    /**
     * @param list<string> $alternatives
     */
    public function __construct(
        public readonly string $input,
        public readonly string $normalizedInput,
        public readonly bool $inputWasValid,
        public readonly ?string $bestCandidate,
        public readonly int $confidence,
        public readonly ?string $appliedPostcode,
        public readonly array $alternatives
    ) {
        $this->assertInvariants();
    }

    private function assertInvariants(): void
    {
        if ($this->confidence < 0 || $this->confidence > 100) {
            throw new InvalidArgumentException('Confidence must be between 0 and 100.');
        }

        if ($this->normalizedInput !== strtoupper($this->normalizedInput)) {
            throw new InvalidArgumentException('Normalized input must be uppercase.');
        }

        if ($this->inputWasValid && $this->confidence !== 100) {
            throw new InvalidArgumentException('Valid input must have 100 confidence.');
        }

        if ($this->inputWasValid && $this->bestCandidate === null) {
            throw new InvalidArgumentException('Valid input must have a best candidate.');
        }

        if ($this->inputWasValid && $this->appliedPostcode !== $this->bestCandidate) {
            throw new InvalidArgumentException('Valid input must apply the canonical candidate.');
        }

        if ($this->bestCandidate === null && $this->appliedPostcode !== null) {
            throw new InvalidArgumentException('Cannot apply a postcode without a best candidate.');
        }

        if ($this->bestCandidate !== null && !self::isCanonicalPostcode($this->bestCandidate)) {
            throw new InvalidArgumentException('Best candidate must be canonical.');
        }

        if ($this->appliedPostcode !== null && !self::isCanonicalPostcode($this->appliedPostcode)) {
            throw new InvalidArgumentException('Applied postcode must be canonical.');
        }

        $uniqueAlternatives = array_values(array_unique($this->alternatives));
        if (count($uniqueAlternatives) !== count($this->alternatives)) {
            throw new InvalidArgumentException('Alternatives must be unique.');
        }

        if ($this->bestCandidate !== null && in_array($this->bestCandidate, $this->alternatives, true)) {
            throw new InvalidArgumentException('Alternatives must not include the best candidate.');
        }

        foreach ($this->alternatives as $alternative) {
            if (!is_string($alternative) || !self::isCanonicalPostcode($alternative)) {
                throw new InvalidArgumentException('Each alternative must be a canonical postcode.');
            }
        }
    }

    private static function isCanonicalPostcode(string $postcode): bool
    {
        if ($postcode === 'GIR 0AA') {
            return true;
        }

        return (bool) preg_match('/^[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][A-Z]{2}$/', $postcode);
    }
}
