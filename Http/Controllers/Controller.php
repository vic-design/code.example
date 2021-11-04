<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Traits\ActionHook;
use App\Services\DataTypeRequestService;
use App\Services\DataTypeQueryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
    use ActionHook;

    /**
     * @var bool
     */
    public bool $queryBuilder = true;

    /**
     * @var string
     */
    private string $method;

    /**
     * @var DataTypeQueryService
     */
    public DataTypeQueryService $queryService;

    /**
     * @var DataTypeRequestService
     */
    public DataTypeRequestService $requestService;

    public function __construct(Request $request,
                                DataTypeQueryService $queryService,
                                DataTypeRequestService $requestService)
    {
        $this->queryService = $queryService;
        $this->requestService = $requestService;

        if ($this->queryBuilder && $request->route()) {
            $this->queryService->queryBuilder();
        }

    }

    public function callAction($method, $parameters)
    {
        $this->method = $method;
        return $this->{$method}(...array_values($parameters));
    }

}
