<?php

declare(strict_types=1);

namespace Cikmov;

use Cikmov\Internal\Analyser;

final class Cikmov
{
    public static function analyse(string $input, int $minConfidenceToApply = 85): Result
    {
        return Analyser::analyse($input, $minConfidenceToApply);
    }
}
