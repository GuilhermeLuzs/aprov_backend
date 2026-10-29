<?php

namespace Database\Seeders;

use App\Enums\CompanyModality;
use App\Enums\CompanySize;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyCoin;
use App\Models\User;
use App\Support\FakeCnpj;
use App\Support\FakeCpf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = require database_path('data/companies_seed.php');

        foreach ($companies as $sequence => $data) {
            $slug = Str::slug($data['name']);

            $user = User::create([
                'name' => $data['name'],
                'email' => "contato@{$slug}.teste",
                'password' => 'empresa1234',
                'role' => UserRole::Company,
            ]);

            $company = Company::create([
                'user_id' => $user->id,
                'cnpj' => FakeCnpj::fromSequence($sequence + 1),
                'name' => $data['name'],
                'logo_url' => $data['logo_url'],
                'description' => $data['description'],
                'email' => $user->email,
                'phone' => '1140040000',
                'zip_code' => '01310100',
                'street' => 'Avenida Paulista',
                'number' => (string) (100 + $sequence),
                'district' => 'Bela Vista',
                'city' => 'São Paulo',
                'state' => 'SP',
                'responsible_name' => "Responsável {$data['name']}",
                'responsible_cpf' => FakeCpf::fromSequence($sequence + 1),
                'responsible_phone' => '11990000000',
                'niche' => $data['niche'],
                'modality' => CompanyModality::Hybrid,
                'size' => CompanySize::From11To50,
            ]);

            CompanyCoin::create([
                'company_id' => $company->id,
                'name' => $data['coin_name'],
                'color' => $data['coin_color'],
                'icon' => $data['coin_icon'],
            ]);
        }
    }
}
