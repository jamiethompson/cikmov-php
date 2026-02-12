<?php

declare(strict_types=1);

namespace Cikmov\Tests;

use Cikmov\Internal\PostcodeRules;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PostcodeRulesTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const array ALLOWED_AREAS = [
        'AB', 'AL', 'B', 'BA', 'BB', 'BD', 'BF', 'BH', 'BL', 'BN', 'BR', 'BS', 'BT', 'BX', 'CA', 'CB', 'CF',
        'CH', 'CM', 'CO', 'CR', 'CT', 'CV', 'CW', 'DA', 'DD', 'DE', 'DG', 'DH', 'DL', 'DN', 'DT', 'DY', 'E',
        'EC', 'EH', 'EN', 'EX', 'FK', 'FY', 'G', 'GL', 'GU', 'GY', 'HA', 'HD', 'HG', 'HP', 'HR', 'HS', 'HU',
        'HX', 'IG', 'IM', 'IP', 'IV', 'JE', 'KA', 'KT', 'KW', 'KY', 'L', 'LA', 'LD', 'LE', 'LL', 'LN', 'LS',
        'LU', 'M', 'ME', 'MK', 'ML', 'N', 'NE', 'NG', 'NN', 'NP', 'NR', 'NW', 'OL', 'OX', 'PA', 'PE', 'PH',
        'PL', 'PO', 'PR', 'RG', 'RH', 'RM', 'S', 'SA', 'SE', 'SG', 'SK', 'SL', 'SM', 'SN', 'SO', 'SP', 'SR',
        'SS', 'ST', 'SW', 'SY', 'TA', 'TD', 'TF', 'TN', 'TQ', 'TR', 'TS', 'TW', 'UB', 'W', 'WA', 'WC', 'WD',
        'WF', 'WN', 'WR', 'WS', 'WV', 'YO', 'ZE',
    ];

    public function testAllEmbeddedAreasValidateAsCompactPostcodes(): void
    {
        foreach (self::ALLOWED_AREAS as $area) {
            self::assertTrue(PostcodeRules::isValidCompact($area . '11AA'), $area);
        }
    }

    public function testUnknownSingleLetterAreasAreRejected(): void
    {
        $allowedSingles = array_values(array_filter(self::ALLOWED_AREAS, static fn (string $area): bool => strlen($area) === 1));

        foreach (range('A', 'Z') as $firstLetter) {
            if (in_array($firstLetter, $allowedSingles, true)) {
                continue;
            }

            self::assertFalse(PostcodeRules::isValidCompact($firstLetter . '11AA'), $firstLetter);
        }
    }

    public function testUnknownTwoLetterAreasAreRejected(): void
    {
        $allowedSet = array_fill_keys(self::ALLOWED_AREAS, true);

        foreach (range('A', 'Z') as $firstLetter) {
            foreach (range('A', 'Z') as $secondLetter) {
                $area = $firstLetter . $secondLetter;
                if (isset($allowedSet[$area])) {
                    continue;
                }

                self::assertFalse(PostcodeRules::isValidCompact($area . '11AA'), $area);
            }
        }
    }

    public function testFormatCompactThrowsForInvalidCompactInput(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PostcodeRules::formatCompact('ABCDE');
    }

    public function testFormatCompactHandlesGir(): void
    {
        self::assertSame('GIR 0AA', PostcodeRules::formatCompact('GIR0AA'));
    }

    public function testCompactFromInputStripsNoise(): void
    {
        self::assertSame('WC2H7LT', PostcodeRules::compactFromInput("  wc2h-7lt\t"));
    }

    public function testDisplayFromCompactSpacingRules(): void
    {
        self::assertSame('', PostcodeRules::displayFromCompact(''));
        self::assertSame('AB12', PostcodeRules::displayFromCompact('AB12'));
        self::assertSame('EC1A 1AL', PostcodeRules::displayFromCompact('EC1A1AL'));
        self::assertSame('GIR 0AA', PostcodeRules::displayFromCompact('GIR0AA'));
        self::assertSame('ABCDEFGHI', PostcodeRules::displayFromCompact('ABCDEFGHI'));
    }
}
