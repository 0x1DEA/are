<?php

namespace App\Mixins;

use Illuminate\Database\Schema\Blueprint;

/**
 * @mixin Blueprint
 */
class BlueprintMixin
{
    public function name(): callable
    {
        return function (): void {
            $this->string('name_first');
            $this->string('name_middle')->nullable();
            $this->string('name_last');
        };
    }

    public function address(): callable
    {
        return function (string $prefix = 'address', bool $nullable = false): void {
            $this->string($prefix.'_number')->nullable($nullable);
            $this->string($prefix.'_direction')->nullable($nullable);
            $this->string($prefix.'_street')->nullable($nullable);
            $this->string($prefix.'_street_suffix')->nullable($nullable);
            $this->string($prefix.'_unit')->nullable($nullable);
            $this->string($prefix.'_city')->nullable($nullable);
            $this->string($prefix.'_state')->nullable($nullable);
            $this->string($prefix.'_postal')->nullable($nullable);
        };
    }
}
