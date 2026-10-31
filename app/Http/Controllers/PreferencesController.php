<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePreferencesRequest;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;

class PreferencesController extends Controller
{
    public function update(SavePreferencesRequest $request): JsonResponse
    {
        $customer = $request->user()->customer;

        $customer->update([
            'preferred_categories' => $request->validated('categories'),
            'discovery_source' => $request->validated('discoverySource'),
        ]);
        $customer->followedCompanies()->sync($request->validated('companyIds'));

        return $this->sendResponse(new CustomerResource($customer), 'Preferências salvas.');
    }
}
