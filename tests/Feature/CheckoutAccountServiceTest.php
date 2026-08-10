<?php

namespace Tests\Feature;

use App\Services\CheckoutAccountService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CheckoutAccountServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
    }

    public function test_it_creates_a_customer_account_from_checkout_details(): void
    {
        $user = app(CheckoutAccountService::class)->create([
            'fname' => 'Ana',
            'lname' => 'Anić',
            'email' => 'ANA@example.test',
            'phone' => '0911234567',
            'address' => 'Ilica 1',
            'zip' => '10000',
            'city' => 'Zagreb',
            'state' => 'Croatia',
            'company' => 'Primjer d.o.o.',
            'oib' => '12345678901',
        ], 'sigurna-lozinka');

        $this->assertSame('Ana Anić', $user->name);
        $this->assertSame('ana@example.test', $user->email);
        $this->assertTrue(Hash::check('sigurna-lozinka', $user->password));

        $this->assertDatabaseHas('user_details', [
            'user_id' => $user->id,
            'fname' => 'Ana',
            'lname' => 'Anić',
            'address' => 'Ilica 1',
            'zip' => '10000',
            'city' => 'Zagreb',
            'role' => 'customer',
        ]);

        $this->assertDatabaseHas('roles', ['name' => 'customer']);
        $this->assertDatabaseHas('assigned_roles', [
            'entity_id' => $user->id,
            'entity_type' => get_class($user),
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->text('profile_photo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('fname');
            $table->string('lname')->nullable();
            $table->string('address')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('oib')->nullable();
            $table->string('avatar')->nullable();
            $table->longText('bio')->nullable();
            $table->string('social')->nullable();
            $table->string('role');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->unsignedInteger('level')->nullable();
            $table->integer('scope')->nullable();
            $table->timestamps();
        });

        Schema::create('assigned_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_type');
            $table->unsignedBigInteger('restricted_to_id')->nullable();
            $table->string('restricted_to_type')->nullable();
            $table->integer('scope')->nullable();
        });
    }
}
