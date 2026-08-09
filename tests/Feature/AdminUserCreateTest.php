<?php

namespace Tests\Feature;

use App\Http\Controllers\Back\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUserCreateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('roles', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }

    public function test_create_form_receives_available_roles(): void
    {
        DB::table('roles')->insert([
            'name' => 'customer',
            'title' => 'Kupac',
        ]);

        $admin = \Mockery::mock(User::class)->makePartial();
        $admin->shouldReceive('can')->with('*')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($admin);

        $view = app(UserController::class)->create();

        $this->assertTrue($view->getData()['roles']->contains('name', 'customer'));
    }
}
