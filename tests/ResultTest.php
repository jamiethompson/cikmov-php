<?php

declare(strict_types=1);

namespace Cikmov\Tests;

use Cikmov\Result;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase
{
    public function testResultAcceptsValidState(): void
    {
        $result = new Result(
            input: 'ec1a1al',
            normalizedInput: 'EC1A 1AL',
            inputWasValid: true,
            bestCandidate: 'EC1A 1AL',
            confidence: 100,
            appliedPostcode: 'EC1A 1AL',
            alternatives: []
        );

        self::assertSame('EC1A 1AL', $result->appliedPostcode);
    }

    public function testConfidenceMustBeWithinRange(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Result(
            input: 'x',
            normalizedInput: 'X',
            inputWasValid: false,
            bestCandidate: null,
            confidence: 101,
            appliedPostcode: null,
            alternatives: []
        );
    }

    public function testNormalizedInputMustBeUppercase(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Result(
            input: 'x',
            normalizedInput: 'Ec1A 1AL',
            inputWasValid: false,
            bestCandidate: null,
            confidence: 0,
            appliedPostcode: null,
            alternatives: []
        );
    }

    public function testValidInputMustHaveFullConfidence(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Result(
            input: 'EC1A 1AL',
            normalizedInput: 'EC1A 1AL',
            inputWasValid: true,
            bestCandidate: 'EC1A 1AL',
            confidence: 99,
            appliedPostcode: 'EC1A 1AL',
            alternatives: []
        );
    }

    public function testAppliedPostcodeRequiresBestCandidate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Result(
            input: 'EC1A IAL',
            normalizedInput: 'EC1A IAL',
            inputWasValid: false,
            bestCandidate: null,
            confidence: 90,
            appliedPostcode: 'EC1A 1AL',
            alternatives: []
        );
    }

    public function testAlternativesMustBeUnique(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Result(
            input: 'B01 8TH',
            normalizedInput: 'B01 8TH',
            inputWasValid: false,
            bestCandidate: 'BD1 8TH',
            confidence: 84,
            appliedPostcode: null,
            alternatives: ['BL1 8TH', 'BL1 8TH']
        );
    }

    public function testAlternativesCannotContainBestCandidate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Result(
            input: 'B01 8TH',
            normalizedInput: 'B01 8TH',
            inputWasValid: false,
            bestCandidate: 'BD1 8TH',
            confidence: 84,
            appliedPostcode: null,
            alternatives: ['BD1 8TH']
        );
    }

    public function testCanonicalShapeIsEnforcedForAlternatives(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Result(
            input: 'x',
            normalizedInput: 'X',
            inputWasValid: false,
            bestCandidate: 'EC1A 1AL',
            confidence: 80,
            appliedPostcode: null,
            alternatives: ['not-a-postcode']
        );
    }
}
