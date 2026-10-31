<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestAccountSeeder extends Seeder
{
    private const USERNAME = 'mari.cavalcanti';

    private const ACCOUNT = [
        'name' => 'Mariana Albuquerque Cavalcanti de Menezes',
        'email' => 'mariana@aprov.com',
        'password' => 'aprov123',
        'role' => UserRole::Customer,
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $customer = Customer::with('user')->where('username', self::USERNAME)->first();

            if ($customer) {
                $customer->user->update(self::ACCOUNT);

                return;
            }

            User::create(self::ACCOUNT)->customer()->create([
                'username' => self::USERNAME,
                'cpf' => '52998224725',
                'birth_date' => '2000-03-14',
            ]);
        });
    }
}
