<?php

declare(strict_types=1);

namespace Cikmov\Tests;

use Cikmov\Cikmov;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CikmovTest extends TestCase
{
    #[DataProvider('validRealWorldExamplesProvider')]
    public function testAlreadyValidExamplesAreAccepted(string $input, string $canonical): void
    {
        $result = Cikmov::analyse($input);

        self::assertTrue($result->inputWasValid);
        self::assertSame($canonical, $result->bestCandidate);
        self::assertSame($canonical, $result->appliedPostcode);
        self::assertSame(100, $result->confidence);
        self::assertSame([], $result->alternatives);
    }

    /**
     * @return iterable<string, array{string,string}>
     */
    public static function validRealWorldExamplesProvider(): iterable
    {
        yield 'EC1A' => ['EC1A 1AL', 'EC1A 1AL'];
        yield 'W12' => ['W12 7RJ', 'W12 7RJ'];
        yield 'SW1A' => ['SW1A 1AA', 'SW1A 1AA'];
        yield 'WC2H' => ['WC2H 7LT', 'WC2H 7LT'];
        yield 'YO1' => ['YO1 7HB', 'YO1 7HB'];
        yield 'B33' => ['B33 8TH', 'B33 8TH'];
        yield 'CR2' => ['CR2 6XH', 'CR2 6XH'];
        yield 'DN55' => ['DN55 1PT', 'DN55 1PT'];
        yield 'M1' => ['M1 1AE', 'M1 1AE'];
        yield 'W1A' => ['W1A 0AX', 'W1A 0AX'];
        yield 'BT1' => ['BT1 5GS', 'BT1 5GS'];
        yield 'GY1' => ['GY1 1AA', 'GY1 1AA'];
        yield 'JE2' => ['JE2 4WW', 'JE2 4WW'];
        yield 'IM1' => ['IM1 1AA', 'IM1 1AA'];
        yield 'BF1' => ['BF1 0AA', 'BF1 0AA'];
        yield 'BX1' => ['BX1 1LT', 'BX1 1LT'];
    }

    #[DataProvider('missingSpaceProvider')]
    public function testMissingSpaceIsNormalised(string $input, string $canonical): void
    {
        $result = Cikmov::analyse($input);

        self::assertTrue($result->inputWasValid);
        self::assertSame($canonical, $result->appliedPostcode);
        self::assertSame(100, $result->confidence);
    }

    /**
     * @return iterable<string, array{string,string}>
     */
    public static function missingSpaceProvider(): iterable
    {
        yield 'EC1A1AL' => ['EC1A1AL', 'EC1A 1AL'];
        yield 'YO17HB' => ['YO17HB', 'YO1 7HB'];
        yield 'B338TH' => ['B338TH', 'B33 8TH'];
    }

    #[DataProvider('lowercaseAndNoiseProvider')]
    public function testLowercaseAndNoiseAreNormalised(string $input, string $canonical): void
    {
        $result = Cikmov::analyse($input);

        self::assertTrue($result->inputWasValid);
        self::assertSame($canonical, $result->appliedPostcode);
        self::assertSame(100, $result->confidence);
    }

    /**
     * @return iterable<string, array{string,string}>
     */
    public static function lowercaseAndNoiseProvider(): iterable
    {
        yield 'lowercase' => ['ec1a 1al', 'EC1A 1AL'];
        yield 'leading spaces' => ['  sw1a1aa', 'SW1A 1AA'];
        yield 'dash noise' => ['wc2h-7lt', 'WC2H 7LT'];
        yield 'extra spaces' => ['yo1   7hb', 'YO1 7HB'];
    }

    #[DataProvider('inwardDigitConfusionProvider')]
    public function testInwardDigitConfusionsAreCorrected(string $input, string $expected): void
    {
        $result = Cikmov::analyse($input);

        self::assertFalse($result->inputWasValid);
        self::assertSame($expected, $result->bestCandidate);
        self::assertSame($expected, $result->appliedPostcode);
        self::assertGreaterThanOrEqual(85, $result->confidence);
    }

    /**
     * @return iterable<string, array{string,string}>
     */
    public static function inwardDigitConfusionProvider(): iterable
    {
        yield 'I->1' => ['EC1A IAL', 'EC1A 1AL'];
        yield 'L->1' => ['EC1A LAL', 'EC1A 1AL'];
        yield 'O->0' => ['EC1A OAL', 'EC1A 0AL'];
        yield 'S->5' => ['EC1A SAL', 'EC1A 5AL'];
        yield 'Z->2' => ['EC1A ZAL', 'EC1A 2AL'];
        yield 'B->8' => ['EC1A BAL', 'EC1A 8AL'];
        yield 'G->6' => ['EC1A GAL', 'EC1A 6AL'];
    }

    #[DataProvider('inwardLetterConfusionProvider')]
    public function testInwardLetterConfusionsAreCorrected(string $input, string $expected): void
    {
        $result = Cikmov::analyse($input);

        self::assertFalse($result->inputWasValid);
        self::assertSame($expected, $result->bestCandidate);
        self::assertSame($expected, $result->appliedPostcode);
    }

    /**
     * @return iterable<string, array{string,string}>
     */
    public static function inwardLetterConfusionProvider(): iterable
    {
        yield '8->B' => ['EC1A 18L', 'EC1A 1BL'];
        yield '2->Z' => ['EC1A 12Z', 'EC1A 1ZZ'];
        yield '6->G' => ['EC1A 16G', 'EC1A 1GG'];
    }

    #[DataProvider('cikmovViolationProvider')]
    public function testCikmovViolationsAreRejected(string $input): void
    {
        $result = Cikmov::analyse($input);

        self::assertFalse($result->inputWasValid);
        self::assertNull($result->bestCandidate);
        self::assertNull($result->appliedPostcode);
        self::assertSame(0, $result->confidence);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function cikmovViolationProvider(): iterable
    {
        yield 'I forbidden' => ['EC1A 1AI'];
        yield 'O forbidden' => ['EC1A 1AO'];
        yield 'K forbidden' => ['EC1A 1AK'];
        yield 'M forbidden' => ['EC1A 1AM'];
        yield 'V forbidden' => ['EC1A 1AV'];
        yield 'C forbidden' => ['EC1A 1AC'];
    }

    #[DataProvider('deterministicCollapseProvider')]
    public function testDeterministicCollapseCases(string $input, string $expected): void
    {
        $result = Cikmov::analyse($input);

        self::assertSame($expected, $result->bestCandidate);
        self::assertSame($expected, $result->appliedPostcode);
        self::assertGreaterThanOrEqual(90, $result->confidence);
    }

    /**
     * @return iterable<string, array{string,string}>
     */
    public static function deterministicCollapseProvider(): iterable
    {
        yield 'EC1A 1A1' => ['EC1A 1A1', 'EC1A 1AL'];
        yield 'YO1 7H1' => ['YO1 7H1', 'YO1 7HL'];
    }

    #[DataProvider('aa9aValidProvider')]
    public function testAa9aValidExamples(string $input): void
    {
        $result = Cikmov::analyse($input);

        self::assertTrue($result->inputWasValid);
        self::assertSame(100, $result->confidence);
        self::assertSame($input, $result->appliedPostcode);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function aa9aValidProvider(): iterable
    {
        yield 'EC1A' => ['EC1A 1AA'];
        yield 'EC2Y' => ['EC2Y 4AB'];
        yield 'EC4M' => ['EC4M 7AX'];
        yield 'SW1A' => ['SW1A 0AA'];
        yield 'WC1H' => ['WC1H 9LT'];
        yield 'WC2R' => ['WC2R 5DN'];
        yield 'NW1W' => ['NW1W 4AB'];
        yield 'SE1P' => ['SE1P 5AA'];
    }

    #[DataProvider('aa9aInvalidProvider')]
    public function testAa9aInvalidExamples(string $input): void
    {
        $result = Cikmov::analyse($input);

        self::assertFalse($result->inputWasValid);
        self::assertNull($result->bestCandidate);
        self::assertNull($result->appliedPostcode);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function aa9aInvalidProvider(): iterable
    {
        yield 'AB1A disallowed area' => ['AB1A 1AA'];
        yield 'EC5A invalid district' => ['EC5A 1AA'];
        yield 'SW2A invalid district' => ['SW2A 1AA'];
        yield 'NW1A not NW1W' => ['NW1A 1AA'];
        yield 'SE1A not SE1P' => ['SE1A 1AA'];
        yield 'EC1Q invalid AA9A letter' => ['EC1Q 1AA'];
        yield 'WC3A invalid district in allowed area' => ['WC3A 1AA'];
        yield 'BF1A valid final letter but wrong area' => ['BF1A 1AA'];
        yield 'EC1T valid area invalid AA9A final letter' => ['EC1T 1AA'];
    }

    #[DataProvider('areaConfusionFixProvider')]
    public function testAreaConfusionFixes(string $input, string $expectedBest): void
    {
        $result = Cikmov::analyse($input);

        self::assertSame($expectedBest, $result->bestCandidate);
        self::assertGreaterThanOrEqual(80, $result->confidence);
    }

    /**
     * @return iterable<string, array{string,string}>
     */
    public static function areaConfusionFixProvider(): iterable
    {
        yield 'Y01' => ['Y01 7HB', 'YO1 7HB'];
        yield 'C01' => ['C01 2BB', 'CO1 2BB'];
        yield 'S01' => ['S01 1AA', 'SO1 1AA'];
    }

    #[DataProvider('ambiguousProvider')]
    public function testAmbiguousCandidatesReturnAlternatives(string $input): void
    {
        $result = Cikmov::analyse($input);

        self::assertNotNull($result->bestCandidate);
        self::assertNotSame([], $result->alternatives);
        self::assertLessThan(95, $result->confidence);
        self::assertLessThan(100, $result->confidence);
        self::assertSame(count($result->alternatives), count(array_unique($result->alternatives)));
        self::assertNotContains($result->bestCandidate, $result->alternatives);

        foreach ($result->alternatives as $alternative) {
            self::assertMatchesRegularExpression('/^[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][A-Z]{2}$/', $alternative);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function ambiguousProvider(): iterable
    {
        yield 'S01' => ['S01 1AA'];
        yield 'B01' => ['B01 8TH'];
    }

    #[DataProvider('invalidInputProvider')]
    public function testInvalidInputsAreRejected(string $input): void
    {
        $result = Cikmov::analyse($input);

        self::assertFalse($result->inputWasValid);
        self::assertNull($result->bestCandidate);
        self::assertNull($result->appliedPostcode);
        self::assertSame(0, $result->confidence);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidInputProvider(): iterable
    {
        yield 'digits only' => ['123456'];
        yield 'letters only' => ['ABCDE'];
        yield 'symbols only' => ['!!!!'];
        yield 'short malformed' => ['AA 11'];
        yield 'class mismatch' => ['A1A A1A'];
        yield 'invalid area' => ['ZZ99 9ZZ'];
        yield 'structural inward mismatch' => ['NE14 ABJ'];
    }

    #[DataProvider('areaInvalidButFormatLikeProvider')]
    public function testAreaInvalidButFormatLikeInputsAreRejected(string $input): void
    {
        $result = Cikmov::analyse($input);

        self::assertFalse($result->inputWasValid);
        self::assertNull($result->bestCandidate);
        self::assertNull($result->appliedPostcode);
        self::assertSame(0, $result->confidence);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function areaInvalidButFormatLikeProvider(): iterable
    {
        yield 'ZZ area' => ['ZZ1 1AA'];
        yield 'CJ area' => ['CJ1 1AA'];
        yield 'J area' => ['J1 1AA'];
        yield 'BJ69 area' => ['BJ69 4ME'];
    }

    #[DataProvider('forbiddenOutwardLetterProvider')]
    public function testForbiddenOutwardLettersAreRejected(string $input): void
    {
        $result = Cikmov::analyse($input);

        self::assertFalse($result->inputWasValid);
        self::assertNull($result->bestCandidate);
        self::assertNull($result->appliedPostcode);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function forbiddenOutwardLetterProvider(): iterable
    {
        yield 'forbidden first Q' => ['Q1 1AA'];
        yield 'forbidden first V' => ['V1 1AA'];
        yield 'forbidden first X' => ['X1 1AA'];
        yield 'forbidden second I' => ['AI1 1AA'];
        yield 'forbidden second J' => ['AJ1 1AA'];
        yield 'forbidden second Z' => ['AZ1 1AA'];
    }

    #[DataProvider('leadingZeroDistrictProvider')]
    public function testLeadingZeroDistrictDigitIsRejected(string $input): void
    {
        $result = Cikmov::analyse($input);

        self::assertFalse($result->inputWasValid);
        self::assertNull($result->bestCandidate);
        self::assertNull($result->appliedPostcode);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function leadingZeroDistrictProvider(): iterable
    {
        yield 'single letter area' => ['B0 1AA'];
        yield 'double letter area' => ['AB0 1AA'];
    }

    public function testGirSpecialCaseIsAccepted(): void
    {
        $result = Cikmov::analyse('GIR 0AA');

        self::assertTrue($result->inputWasValid);
        self::assertSame('GIR 0AA', $result->appliedPostcode);
        self::assertSame(100, $result->confidence);
    }

    #[DataProvider('northernIrelandProvider')]
    public function testNorthernIrelandExamples(string $input): void
    {
        $result = Cikmov::analyse($input);

        self::assertTrue($result->inputWasValid);
        self::assertSame(100, $result->confidence);
        self::assertSame($input, $result->appliedPostcode);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function northernIrelandProvider(): iterable
    {
        yield 'BT1' => ['BT1 5GS'];
        yield 'BT12' => ['BT12 6AA'];
    }

    #[DataProvider('idempotencyProvider')]
    public function testAnalysisIsIdempotent(string $input): void
    {
        $first = Cikmov::analyse($input);
        $second = Cikmov::analyse($input);

        self::assertEquals($first, $second);

        if ($first->appliedPostcode !== null) {
            $third = Cikmov::analyse($first->appliedPostcode);
            self::assertTrue($third->inputWasValid);
            self::assertSame($first->appliedPostcode, $third->appliedPostcode);
            self::assertSame(100, $third->confidence);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function idempotencyProvider(): iterable
    {
        yield 'valid input' => ['EC1A 1AL'];
        yield 'correction input' => ['EC1A IAL'];
        yield 'area confusion correction' => ['Y01 7HB'];
        yield 'ambiguous input' => ['B01 8TH'];
        yield 'rejected input' => ['!!!!'];
    }

    public function testThresholdCanPreventApplication(): void
    {
        $result = Cikmov::analyse('EC1A IAL', 99);

        self::assertSame('EC1A 1AL', $result->bestCandidate);
        self::assertNull($result->appliedPostcode);
    }

    public function testAlternativesAreCappedAtFive(): void
    {
        $result = Cikmov::analyse('8BG GFT', 0);

        self::assertCount(5, $result->alternatives);
    }

    public function testAmbiguityCanStillApplyWhenThresholdIsLowered(): void
    {
        $result = Cikmov::analyse('S01 1AA', 80);

        self::assertNotSame([], $result->alternatives);
        self::assertNotNull($result->appliedPostcode);
    }

    public function testTopTieCanApplyWhenThresholdAllowsIt(): void
    {
        $defaultThresholdResult = Cikmov::analyse('W5J 10T');
        self::assertNotSame([], $defaultThresholdResult->alternatives);
        self::assertNull($defaultThresholdResult->appliedPostcode);

        $loweredThresholdResult = Cikmov::analyse('W5J 10T', 79);
        self::assertNotNull($loweredThresholdResult->appliedPostcode);
    }

    public function testThresholdRangeValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Cikmov::analyse('EC1A 1AL', 101);
    }
}
