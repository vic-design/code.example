<?php

namespace App\Http\Controllers\Api\V2\Client;

use App\Enums\DataTypeActions;
use App\Http\Controllers\Api\V2\Controller;
use App\Http\Resources\Api\V2\Client\BrowseClientResource;
use App\Models\User;
use App\Policies\Api\V2\ClientPolicy;
use App\Services\DataTypeQueryService;
use App\Services\DataTypeRequestService;
use AppRoles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends Controller
{

    public bool $queryBuilder = false;


    public function __construct(Request $request,
                                DataTypeQueryService $queryService,
                                DataTypeRequestService $requestService)
    {
        parent::__construct($request, $queryService, $requestService);

        \Gate::policy(User::class, ClientPolicy::class);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->queryService->queryBuilder(DataTypeActions::Browse, 'users');

        $this->authorize(
            $this->queryService->action(),
            $this->queryService->model()
        );

        $model = $this->queryService->query()
            ->byRoleId(AppRoles::getAdministratorId())
            ->with('company:name,slug,company_id,model_id')
            ->paginate($request->get('limit'));

        return BrowseClientResource::collection($model)
            ->additional($this->queryService->dataTypeWrap())
            ->response();
    }

    /**
     * @param User $client
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function loginAsUser(User $client): JsonResponse
    {
        $this->authorize(
            'login',
            $client
        );

        \Impersonate::login($client);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param User $client
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function logoutFromUser(User $client): JsonResponse
    {
        $this->authorize(
            'logout',
            $client
        );

        \Impersonate::logout();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
