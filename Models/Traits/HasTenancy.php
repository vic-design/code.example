<?php


namespace App\Models\Traits;


use App\Models\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTenancy
{

    public static function bootHasTenancy()
    {
        static::addGlobalScope('tenancy', function (Builder $builder) {
            $builder->whereHas('tenancy', function ($query) {
                if (!\MyCompany::allCompanies()) {
                    $query->where('company_id', \MyCompany::id());
                }
            });
        });

        static::created(function (Model $model) {
            $model->tenancy()->create([
                'company_id' => \MyCompany::id(),
            ]);
        });

        static::deleting(function (Model $model) {
            $model->tenancy()->delete();
        });
    }

    public function tenancy(): MorphMany
    {
        return $this->morphMany(Tenancy::class, 'model');
    }

    public function company(): MorphMany
    {
        return $this->tenancy()
            ->join('companies', 'company_id', 'companies.id');
    }

}
