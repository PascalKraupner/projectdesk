<?php

namespace App\Http\Controllers;

use App\Services\ClientService;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clientService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Client/Index', [
            'clients' => $this->clientService->all(),
        ]);
    }
}
