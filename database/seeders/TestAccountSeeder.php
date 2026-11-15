<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Company;
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

    private const LOCAL_PANEL_PASSWORD = 'aprov123';

    private const MASTER_ACCOUNT = ['name' => 'Administração APROV', 'email' => 'master@aprov.com'];

    private const COMPANY_EMAIL = 'empresa@aprov.com';

    private const COMPANY_NAME = 'Cozinha da Vó Marlene Comidas Caseiras e Congelados Artesanais';

    public function run(): void
    {
        DB::transaction(function () {
            $this->seedCustomer();
            $this->seedPanelAccounts();
        });
    }

    private function seedCustomer(): void
    {
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
    }

    private function seedPanelAccounts(): void
    {
        $password = $this->panelPassword();

        if ($password === null) {
            $this->command?->warn('PANEL_TEST_PASSWORD vazio: contas de teste dos painéis não foram criadas.');

            return;
        }

        User::updateOrCreate(
            ['email' => self::MASTER_ACCOUNT['email']],
            ['name' => self::MASTER_ACCOUNT['name'], 'password' => $password, 'role' => UserRole::Master],
        );

        $company = Company::with('user')->where('name', self::COMPANY_NAME)->first();
        $company?->user->update(['email' => self::COMPANY_EMAIL, 'password' => $password]);
    }

    private function panelPassword(): ?string
    {
        $configured = config('aprov.panel_test_password');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return app()->isProduction() ? null : self::LOCAL_PANEL_PASSWORD;
    }
}
