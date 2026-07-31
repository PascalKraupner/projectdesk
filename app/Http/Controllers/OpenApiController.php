<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OpenApiController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        return response()->file(
            resource_path('api/openapi.yaml'),
            ['Content-Type' => 'application/yaml'],
        );
    }
}
