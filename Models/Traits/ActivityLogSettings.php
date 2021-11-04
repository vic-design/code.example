<?php

namespace App\Models\Traits;


use App\Services\DataTypeQueryService;
use Spatie\Activitylog\Traits\LogsActivity;

trait ActivityLogSettings
{
    use LogsActivity;

    protected static array $ignoreChangedAttributes = ['created_at', 'updated_at'];
    protected static array $logAttributesToIgnore = ['created_at', 'updated_at'];
    protected static bool $logOnlyDirty = true;
    protected static bool $submitEmptyLogs = false;

    /**
     * @param string $eventName
     * @return string
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        return __("Этот объект был $eventName");
    }

    public function attributesToBeLogged(): array
    {
        return app(DataTypeQueryService::class)->visibleFields();
    }

}
