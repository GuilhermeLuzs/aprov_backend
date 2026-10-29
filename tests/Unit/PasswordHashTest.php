<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordHashTest extends TestCase
{
    public function test_password_is_stored_as_bcrypt_hash(): void
    {
        $user = new User(['password' => 'aprov123']);

        $this->assertNotSame('aprov123', $user->getAttributes()['password']);
        $this->assertStringStartsWith('$2y$', $user->getAttributes()['password']);
        $this->assertTrue(Hash::check('aprov123', $user->password));
    }
}
