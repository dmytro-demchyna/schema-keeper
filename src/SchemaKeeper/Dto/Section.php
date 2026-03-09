<?php

/**
 * This file is part of the SchemaKeeper package.
 * (c) Dmytro Demchyna <dmytro.demchyna@gmail.com>
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SchemaKeeper\Dto;

final class Section
{
    public const TABLES = 'tables';

    public const VIEWS = 'views';

    public const MATERIALIZED_VIEWS = 'materialized_views';

    public const TYPES = 'types';

    public const FUNCTIONS = 'functions';

    public const TRIGGERS = 'triggers';

    public const SEQUENCES = 'sequences';

    public const PROCEDURES = 'procedures';

    public static function all(): array
    {
        return [
            self::TABLES,
            self::VIEWS,
            self::MATERIALIZED_VIEWS,
            self::TYPES,
            self::FUNCTIONS,
            self::TRIGGERS,
            self::SEQUENCES,
            self::PROCEDURES,
        ];
    }

    public static function allExcept(array $sections): array
    {
        return array_values(array_diff(self::all(), $sections));
    }
}
