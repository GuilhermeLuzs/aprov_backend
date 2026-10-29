<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::create([
            'name' => 'Mariana Albuquerque Cavalcanti de Menezes',
            'email' => 'mariana@aprov.com',
            'password' => 'aprov123',
            'role' => UserRole::Client,
        ]);

        $user->client()->create([
            'username' => 'mari.cavalcanti',
            'cpf' => '52998224725',
            'birth_date' => '2000-03-14',
        ]);

        $this->call(CompanySeeder::class);
        $this->call(CatalogSeeder::class);
    }
}
