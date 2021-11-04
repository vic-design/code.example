<?php


namespace App\Models\Traits;


use App\Services\DataTypeQueryService;

trait ProtectedAppends
{
    /**
     * @return array
     */
    protected function getArrayableAppends(): array
    {
        $service = app(DataTypeQueryService::class);
        return $service->getAppends($this);
    }
}
