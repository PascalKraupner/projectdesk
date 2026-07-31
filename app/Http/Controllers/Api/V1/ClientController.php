<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\StoreClientRequest;
use App\Http\Requests\Api\V1\Client\UpdateClientRequest;
use App\Http\Requests\Api\V1\PaginationRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clientService,
    ) {}

    public function index(PaginationRequest $request): AnonymousResourceCollection
    {
        return ClientResource::collection($this->clientService->paginate($request->perPage()));
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->create($request->validated());

        return ClientResource::make($client)->response()->setStatusCode(201);
    }

    public function show(Client $client): ClientResource
    {
        return ClientResource::make($this->clientService->findWithTotals($client->id));
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        return ClientResource::make($this->clientService->update($client, $request->validated()));
    }

    public function destroy(Client $client): Response
    {
        $this->clientService->delete($client);

        return response()->noContent();
    }
}
