<?php

declare(strict_types=1);

namespace Cikmov\Internal;

use InvalidArgumentException;

final class PostcodeRules
{
    public const GIR_COMPACT = 'GIR0AA';
    public const GIR_CANONICAL = 'GIR 0AA';

    private const FORBIDDEN_INWARD_LETTERS = 'CIKMOV';
    private const FORBIDDEN_FIRST_OUTWARD_LETTERS = 'QVX';
    private const FORBIDDEN_SECOND_OUTWARD_LETTERS = 'IJZ';
    private const AA9A_ALLOWED_FINAL_LETTERS = 'ABEHMNPRVWXY';

    /**
     * @var array<string, list<string>>
     */
    private const OUTWARD_PATTERNS_BY_LENGTH = [
        2 => ['A9'],
        3 => ['A9A', 'A99', 'AA9'],
        4 => ['AA9A', 'AA99'],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const OUTWARD_PATTERN_TOKENS = [
        'A9' => ['L', 'N'],
        'A9A' => ['L', 'N', 'L'],
        'A99' => ['L', 'N', 'D'],
        'AA9' => ['L', 'L', 'N'],
        'AA9A' => ['L', 'L', 'N', 'L'],
        'AA99' => ['L', 'L', 'N', 'D'],
    ];

    /**
     * @var array<string, bool>
     */
    private const AREA_SET = [
        'AB' => true,
        'AL' => true,
        'B' => true,
        'BA' => true,
        'BB' => true,
        'BD' => true,
        'BF' => true,
        'BH' => true,
        'BL' => true,
        'BN' => true,
        'BR' => true,
        'BS' => true,
        'BT' => true,
        'BX' => true,
        'CA' => true,
        'CB' => true,
        'CF' => true,
        'CH' => true,
        'CM' => true,
        'CO' => true,
        'CR' => true,
        'CT' => true,
        'CV' => true,
        'CW' => true,
        'DA' => true,
        'DD' => true,
        'DE' => true,
        'DG' => true,
        'DH' => true,
        'DL' => true,
        'DN' => true,
        'DT' => true,
        'DY' => true,
        'E' => true,
        'EC' => true,
        'EH' => true,
        'EN' => true,
        'EX' => true,
        'FK' => true,
        'FY' => true,
        'G' => true,
        'GL' => true,
        'GU' => true,
        'GY' => true,
        'HA' => true,
        'HD' => true,
        'HG' => true,
        'HP' => true,
        'HR' => true,
        'HS' => true,
        'HU' => true,
        'HX' => true,
        'IG' => true,
        'IM' => true,
        'IP' => true,
        'IV' => true,
        'JE' => true,
        'KA' => true,
        'KT' => true,
        'KW' => true,
        'KY' => true,
        'L' => true,
        'LA' => true,
        'LD' => true,
        'LE' => true,
        'LL' => true,
        'LN' => true,
        'LS' => true,
        'LU' => true,
        'M' => true,
        'ME' => true,
        'MK' => true,
        'ML' => true,
        'N' => true,
        'NE' => true,
        'NG' => true,
        'NN' => true,
        'NP' => true,
        'NR' => true,
        'NW' => true,
        'OL' => true,
        'OX' => true,
        'PA' => true,
        'PE' => true,
        'PH' => true,
        'PL' => true,
        'PO' => true,
        'PR' => true,
        'RG' => true,
        'RH' => true,
        'RM' => true,
        'S' => true,
        'SA' => true,
        'SE' => true,
        'SG' => true,
        'SK' => true,
        'SL' => true,
        'SM' => true,
        'SN' => true,
        'SO' => true,
        'SP' => true,
        'SR' => true,
        'SS' => true,
        'ST' => true,
        'SW' => true,
        'SY' => true,
        'TA' => true,
        'TD' => true,
        'TF' => true,
        'TN' => true,
        'TQ' => true,
        'TR' => true,
        'TS' => true,
        'TW' => true,
        'UB' => true,
        'W' => true,
        'WA' => true,
        'WC' => true,
        'WD' => true,
        'WF' => true,
        'WN' => true,
        'WR' => true,
        'WS' => true,
        'WV' => true,
        'YO' => true,
        'ZE' => true,
    ];

    public static function compactFromInput(string $input): string
    {
        $normalized = strtoupper($input);
        $compact = preg_replace('/[^A-Z0-9]+/', '', $normalized);

        return $compact ?? '';
    }

    public static function displayFromCompact(string $compact): string
    {
        if ($compact === '') {
            return '';
        }

        if ($compact === self::GIR_COMPACT) {
            return self::GIR_CANONICAL;
        }

        $length = strlen($compact);
        if ($length < 5 || $length > 7) {
            return $compact;
        }

        return substr($compact, 0, $length - 3) . ' ' . substr($compact, -3);
    }

    public static function formatCompact(string $compact): string
    {
        if (!self::isValidCompact($compact)) {
            throw new InvalidArgumentException('Cannot format an invalid compact postcode.');
        }

        if ($compact === self::GIR_COMPACT) {
            return self::GIR_CANONICAL;
        }

        return substr($compact, 0, strlen($compact) - 3) . ' ' . substr($compact, -3);
    }

    /**
     * @return list<string>
     */
    public static function outwardPatternsForLength(int $outwardLength): array
    {
        return self::OUTWARD_PATTERNS_BY_LENGTH[$outwardLength] ?? [];
    }

    /**
     * @return list<string>
     */
    public static function outwardTokens(string $pattern): array
    {
        return self::OUTWARD_PATTERN_TOKENS[$pattern] ?? [];
    }

    public static function isValidCompact(string $compact): bool
    {
        if ($compact === self::GIR_COMPACT) {
            return true;
        }

        $length = strlen($compact);
        if ($length < 5 || $length > 7) {
            return false;
        }

        $outwardLength = $length - 3;
        $outward = substr($compact, 0, $outwardLength);
        $inward = substr($compact, -3);

        if (!self::isValidInward($inward)) {
            return false;
        }

        foreach (self::outwardPatternsForLength($outwardLength) as $pattern) {
            if (self::isValidOutwardForPattern($outward, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public static function isValidCompactForPattern(string $compact, string $outwardPattern): bool
    {
        if ($compact === self::GIR_COMPACT) {
            return false;
        }

        $tokens = self::outwardTokens($outwardPattern);
        if ($tokens === []) {
            return false;
        }

        $length = strlen($compact);
        if ($length !== count($tokens) + 3) {
            return false;
        }

        $outward = substr($compact, 0, count($tokens));
        $inward = substr($compact, -3);

        return self::isValidInward($inward) && self::isValidOutwardForPattern($outward, $outwardPattern);
    }

    private static function isValidInward(string $inward): bool
    {
        if (strlen($inward) !== 3) {
            return false;
        }

        if (!ctype_digit($inward[0])) {
            return false;
        }

        if (!ctype_alpha($inward[1]) || !ctype_alpha($inward[2])) {
            return false;
        }

        if (str_contains(self::FORBIDDEN_INWARD_LETTERS, $inward[1])) {
            return false;
        }

        if (str_contains(self::FORBIDDEN_INWARD_LETTERS, $inward[2])) {
            return false;
        }

        return true;
    }

    private static function isValidOutwardForPattern(string $outward, string $pattern): bool
    {
        $tokens = self::outwardTokens($pattern);
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

        if (str_contains(self::FORBIDDEN_FIRST_OUTWARD_LETTERS, $outward[0])) {
            return false;
        }

        if (($tokens[1] ?? null) === 'L' && str_contains(self::FORBIDDEN_SECOND_OUTWARD_LETTERS, $outward[1])) {
            return false;
        }

        $areaLength = str_starts_with($pattern, 'AA') ? 2 : 1;
        $area = substr($outward, 0, $areaLength);
        if (!isset(self::AREA_SET[$area])) {
            return false;
        }

        if ($pattern === 'AA9A' && !self::isValidAa9aOutward($outward)) {
            return false;
        }

        return true;
    }

    private static function isValidAa9aOutward(string $outward): bool
    {
        $area = substr($outward, 0, 2);
        $districtDigit = $outward[2];
        $districtLetter = $outward[3];

        if (!str_contains(self::AA9A_ALLOWED_FINAL_LETTERS, $districtLetter)) {
            return false;
        }

        return match ($area) {
            'EC' => in_array($districtDigit, ['1', '2', '3', '4'], true),
            'SW' => $districtDigit === '1',
            'WC' => in_array($districtDigit, ['1', '2'], true),
            'NW' => $districtDigit === '1' && $districtLetter === 'W',
            'SE' => $districtDigit === '1' && $districtLetter === 'P',
            default => false,
        };
    }
}
