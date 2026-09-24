<?php

declare(strict_types=1);

namespace Dirthara\Routing\Exception;

use UnitEnum;

use function count;
use function is_array;
use function is_string;
use function addcslashes;
use function array_merge;
use function get_debug_type;

trait HasExceptionContext
{
    /**
     * @var array<string, mixed>
     */
    public protected(set) array $context = [] {
        get {
            return $this->context;
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function addContext(array $context): static
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    private static function printable(string $value): string
    {
        return addcslashes($value, characters: "\0..\37\177");
    }

    private static function printableName(string|UnitEnum $name): string
    {
        return $name instanceof UnitEnum ? $name::class . '::' . $name->name : self::printable($name);
    }

    private static function printableAction(mixed $action): string
    {
        if (is_string($action)) {
            return self::printable($action);
        }

        if (is_array($action) && count($action) === 2 && is_string($action[1] ?? null)) {
            $target = is_string($action[0] ?? null) ? $action[0] : get_debug_type($action[0] ?? null);

            return self::printable($target . '::' . $action[1]);
        }

        return get_debug_type($action);
    }
}
