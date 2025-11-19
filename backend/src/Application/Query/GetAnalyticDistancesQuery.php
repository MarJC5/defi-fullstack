<?php

declare(strict_types=1);

namespace App\Application\Query;

class GetAnalyticDistancesQuery
{
    public function __construct(
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly string $groupBy = 'none'
    ) {}
}
