<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePreferencesRequest;
use App\Http\Resources\ClientResource;
use Illuminate\Http\JsonResponse;

class PreferencesController extends Controller
{
    public function update(SavePreferencesRequest $request): JsonResponse
    {
        $client = $request->user()->client;

        $client->update([
            'preferred_categories' => $request->validated('categories'),
            'discovery_source' => $request->validated('discoverySource'),
            'followed_company_ids' => $request->validated('companyIds'),
        ]);

        return $this->sendResponse(new ClientResource($client), 'Preferências salvas.');
    }
}
