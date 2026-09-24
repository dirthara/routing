<?php

declare(strict_types=1);

namespace Dirthara\Routing\Exception;

use UnitEnum;

use function addcslashes;
use function array_merge;

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
}
