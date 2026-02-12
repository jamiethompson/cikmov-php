<?php

declare(strict_types=1);

namespace Cikmov\Internal;

use Cikmov\Result;
use InvalidArgumentException;

final class Analyser
{
    private const OUTWARD_SUBSTITUTION_BASE_PENALTY = 8;
    private const INWARD_SUBSTITUTION_BASE_PENALTY = 4;
    private const TIE_AMBIGUITY_PENALTY = 15;
    private const NEAR_AMBIGUITY_PENALTY = 6;
    private const ALTERNATIVE_SCORE_WINDOW = 4;
    private const MAX_ALTERNATIVES = 5;

    /**
     * @var array<string, array<string, int>>
     */
    private const DIGIT_TO_LETTERS = [
        '0' => ['O' => 0, 'D' => 2, 'Q' => 2, 'L' => 3],
        '1' => ['I' => 0, 'L' => 0],
        '2' => ['Z' => 0],
        '3' => ['B' => 2],
        '4' => ['A' => 2],
        '5' => ['S' => 0],
        '6' => ['G' => 0],
        '7' => ['T' => 1],
        '8' => ['B' => 0],
        '9' => ['G' => 2],
    ];

    /**
     * @var array<string, array<string, int>>
     */
    private const LETTER_TO_DIGITS = [
        'B' => ['8' => 0, '3' => 2],
        'G' => ['6' => 0, '9' => 2],
        'I' => ['1' => 0],
        'L' => ['1' => 0],
        'O' => ['0' => 0],
        'S' => ['5' => 0],
        'Z' => ['2' => 0],
    ];

    public static function analyse(string $input, int $minConfidenceToApply): Result
    {
        if ($minConfidenceToApply < 0 || $minConfidenceToApply > 100) {
            throw new InvalidArgumentException('minConfidenceToApply must be between 0 and 100.');
        }

        $compact = PostcodeRules::compactFromInput($input);
        $normalizedInput = PostcodeRules::displayFromCompact($compact);

        if ($compact === '') {
            return new Result(
                input: $input,
                normalizedInput: $normalizedInput,
                inputWasValid: false,
                bestCandidate: null,
                confidence: 0,
                appliedPostcode: null,
                alternatives: []
            );
        }

        if (PostcodeRules::isValidCompact($compact)) {
            $canonical = PostcodeRules::formatCompact($compact);

            return new Result(
                input: $input,
                normalizedInput: $canonical,
                inputWasValid: true,
                bestCandidate: $canonical,
                confidence: 100,
                appliedPostcode: $canonical,
                alternatives: []
            );
        }

        if (!preg_match('/[A-Z]/', $compact) || !preg_match('/[0-9]/', $compact)) {
            return new Result(
                input: $input,
                normalizedInput: $normalizedInput,
                inputWasValid: false,
                bestCandidate: null,
                confidence: 0,
                appliedPostcode: null,
                alternatives: []
            );
        }

        $candidates = self::generateCandidates($compact);
        if ($candidates === []) {
            return new Result(
                input: $input,
                normalizedInput: $normalizedInput,
                inputWasValid: false,
                bestCandidate: null,
                confidence: 0,
                appliedPostcode: null,
                alternatives: []
            );
        }

        $ranked = [];
        foreach ($candidates as $candidateCompact => $score) {
            $ranked[] = [
                'compact' => $candidateCompact,
                'canonical' => PostcodeRules::formatCompact($candidateCompact),
                'score' => $score,
            ];
        }

        usort(
            $ranked,
            static fn (array $left, array $right): int =>
                ($right['score'] <=> $left['score']) ?: strcmp($left['canonical'], $right['canonical'])
        );

        $best = $ranked[0];
        $topScore = $best['score'];
        $alternatives = [];
        $hasTopTie = false;
        $hasNearAmbiguity = false;

        for ($index = 1, $count = count($ranked); $index < $count; $index++) {
            $candidate = $ranked[$index];
            $scoreDelta = $topScore - $candidate['score'];

            if ($scoreDelta === 0) {
                $hasTopTie = true;
                $alternatives[] = $candidate['canonical'];
                continue;
            }

            if ($scoreDelta <= self::ALTERNATIVE_SCORE_WINDOW) {
                $hasNearAmbiguity = true;
                $alternatives[] = $candidate['canonical'];
            }
        }

        $alternatives = array_values(array_unique($alternatives));
        if (count($alternatives) > self::MAX_ALTERNATIVES) {
            $alternatives = array_slice($alternatives, 0, self::MAX_ALTERNATIVES);
        }

        $confidence = $topScore;
        if ($hasTopTie) {
            $confidence -= self::TIE_AMBIGUITY_PENALTY;
        } elseif ($hasNearAmbiguity) {
            $confidence -= self::NEAR_AMBIGUITY_PENALTY;
        }

        $confidence = max(0, min(100, $confidence));
        $appliedPostcode = $confidence >= $minConfidenceToApply ? $best['canonical'] : null;

        return new Result(
            input: $input,
            normalizedInput: $normalizedInput,
            inputWasValid: false,
            bestCandidate: $best['canonical'],
            confidence: $confidence,
            appliedPostcode: $appliedPostcode,
            alternatives: $alternatives
        );
    }

    /**
     * @return array<string, int>
     */
    private static function generateCandidates(string $compact): array
    {
        $length = strlen($compact);
        if ($length < 5 || $length > 7) {
            return [];
        }

        $outwardLength = $length - 3;
        $outwardInput = substr($compact, 0, $outwardLength);
        $inwardInput = substr($compact, -3);

        $patterns = PostcodeRules::outwardPatternsForLength($outwardLength);
        if ($patterns === []) {
            return [];
        }

        $classCompatiblePatterns = array_values(
            array_filter(
                $patterns,
                static fn (string $pattern): bool => self::isClassCompatibleOutward($outwardInput, $pattern)
            )
        );

        if ($classCompatiblePatterns !== []) {
            $patterns = $classCompatiblePatterns;
        }

        $scoresByCandidate = [];

        foreach ($patterns as $pattern) {
            $outwardTokens = PostcodeRules::outwardTokens($pattern);
            if ($outwardTokens === []) {
                continue;
            }

            $optionsByPosition = [];
            $isPatternViable = true;

            foreach ($outwardTokens as $position => $token) {
                $options = self::optionsForCharacter($outwardInput[$position], $token, true);
                if ($options === []) {
                    $isPatternViable = false;
                    break;
                }

                $optionsByPosition[] = $options;
            }

            if (!$isPatternViable) {
                continue;
            }

            $inwardTokens = ['D', 'L', 'L'];
            foreach ($inwardTokens as $position => $token) {
                $options = self::optionsForCharacter($inwardInput[$position], $token, false);
                if ($options === []) {
                    $isPatternViable = false;
                    break;
                }

                $optionsByPosition[] = $options;
            }

            if (!$isPatternViable) {
                continue;
            }

            self::walkCandidateOptions(
                optionsByPosition: $optionsByPosition,
                position: 0,
                partialCandidate: '',
                totalPenalty: 0,
                onCandidate: static function (string $candidate, int $penalty) use (&$scoresByCandidate, $pattern): void {
                    if (!PostcodeRules::isValidCompactForPattern($candidate, $pattern)) {
                        return;
                    }

                    $score = max(0, 100 - $penalty);
                    if (!isset($scoresByCandidate[$candidate]) || $score > $scoresByCandidate[$candidate]) {
                        $scoresByCandidate[$candidate] = $score;
                    }
                }
            );
        }

        return $scoresByCandidate;
    }

    private static function isClassCompatibleOutward(string $outward, string $pattern): bool
    {
        $tokens = PostcodeRules::outwardTokens($pattern);
        if ($tokens === [] || strlen($outward) !== count($tokens)) {
            return false;
        }

        foreach ($tokens as $position => $token) {
            $character = $outward[$position];

            if ($token === 'L' && !ctype_alpha($character)) {
                return false;
            }

            if ($token === 'D' && !ctype_digit($character)) {
                return false;
            }

            if ($token === 'N' && (!ctype_digit($character) || $character === '0')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<list<array{char:string,penalty:int}>> $optionsByPosition
     * @param callable(string,int):void $onCandidate
     */
    private static function walkCandidateOptions(
        array $optionsByPosition,
        int $position,
        string $partialCandidate,
        int $totalPenalty,
        callable $onCandidate
    ): void {
        if ($position === count($optionsByPosition)) {
            $onCandidate($partialCandidate, $totalPenalty);
            return;
        }

        foreach ($optionsByPosition[$position] as $option) {
            self::walkCandidateOptions(
                optionsByPosition: $optionsByPosition,
                position: $position + 1,
                partialCandidate: $partialCandidate . $option['char'],
                totalPenalty: $totalPenalty + $option['penalty'],
                onCandidate: $onCandidate
            );
        }
    }

    /**
     * @return list<array{char:string,penalty:int}>
     */
    private static function optionsForCharacter(string $character, string $expectedToken, bool $outward): array
    {
        $basePenalty = $outward ? self::OUTWARD_SUBSTITUTION_BASE_PENALTY : self::INWARD_SUBSTITUTION_BASE_PENALTY;
        $options = [];

        if ($expectedToken === 'L') {
            if (ctype_alpha($character)) {
                $options[] = ['char' => $character, 'penalty' => 0];
            }

            if (ctype_digit($character)) {
                $mappedLetters = self::DIGIT_TO_LETTERS[$character] ?? [];
                foreach ($mappedLetters as $replacement => $extraPenalty) {
                    $options[] = ['char' => $replacement, 'penalty' => $basePenalty + $extraPenalty];
                }
            }
        } else {
            if (ctype_digit($character) && ($expectedToken !== 'N' || $character !== '0')) {
                $options[] = ['char' => $character, 'penalty' => 0];
            }

            if (ctype_alpha($character)) {
                $mappedDigits = self::LETTER_TO_DIGITS[$character] ?? [];
                foreach ($mappedDigits as $replacement => $extraPenalty) {
                    if ($expectedToken === 'N' && $replacement === '0') {
                        continue;
                    }

                    $options[] = ['char' => $replacement, 'penalty' => $basePenalty + $extraPenalty];
                }
            }
        }

        $deduplicated = [];
        foreach ($options as $option) {
            $candidateCharacter = $option['char'];
            $candidatePenalty = $option['penalty'];

            if (!isset($deduplicated[$candidateCharacter]) || $candidatePenalty < $deduplicated[$candidateCharacter]) {
                $deduplicated[$candidateCharacter] = $candidatePenalty;
            }
        }

        $finalOptions = [];
        foreach ($deduplicated as $candidateCharacter => $candidatePenalty) {
            $finalOptions[] = ['char' => $candidateCharacter, 'penalty' => $candidatePenalty];
        }

        usort(
            $finalOptions,
            static fn (array $left, array $right): int =>
                ($left['penalty'] <=> $right['penalty']) ?: strcmp($left['char'], $right['char'])
        );

        return $finalOptions;
    }
}
