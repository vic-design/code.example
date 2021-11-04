<?php


namespace App\Models\Traits;


use Illuminate\Database\Eloquent\Builder;

trait HasRequiredScopes
{
    public function initializeHasRequiredScopes()
    {
        static::addGlobalScope('requiredScopes', function (Builder $builder) {
            $builder->scopes($this->getRequiredScopes());
        });
    }
}
