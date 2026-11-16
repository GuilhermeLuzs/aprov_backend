<?php

namespace App\Http\Resources;

use App\Models\Company;
use App\Models\User;
use App\Support\PublicFileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class PanelAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $company = $this->company;

        return [
            'id' => $this->id,
            'role' => $this->role,
            'name' => $this->name,
            'email' => $this->email,
            'company' => $company ? self::company($company) : null,
        ];
    }

    public static function company(Company $company): array
    {
        return [
            'id' => $company->id,
            'cnpj' => $company->cnpj,
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'zipCode' => $company->zip_code,
            'street' => $company->street,
            'number' => $company->number,
            'complement' => $company->complement,
            'district' => $company->district,
            'city' => $company->city,
            'state' => $company->state,
            'niche' => $company->niche,
            'modality' => $company->modality,
            'size' => $company->size,
            'description' => $company->description,
            'logoUrl' => PublicFileUrl::of($company->logo_url),
            'responsibleName' => $company->responsible_name,
            'responsiblePhone' => $company->responsible_phone,
        ];
    }
}
