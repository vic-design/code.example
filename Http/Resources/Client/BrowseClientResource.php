<?php

namespace App\Http\Resources\Api\V2\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class BrowseClientResource extends JsonResource
{
    public function toArray($request)
    {
        $data = collect(parent::toArray($request))
            ->only([
                'id', 'name', 'last_name', 'patronymic', 'phone', 'email',
                'active', 'has_contract', 'last_login', 'created_at', 'company'
            ])->all();

        \Arr::set($data, 'company.0.id', \Arr::get($data, 'company.0.company_id'));

        \Arr::forget($data, [
            'company.0.model_id',
            'company.0.company_id'
        ]);

        return $data;
    }
}
