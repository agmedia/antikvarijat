<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CorvusWalletsLocalizationRepairTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code');
            $table->string('key');
            $table->text('value');
            $table->boolean('json')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('payment_method')->nullable();
            $table->string('payment_code')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('settings');

        parent::tearDown();
    }

    public function testRepairReplacesNullStringsAndAffectedOrderSnapshots(): void
    {
        DB::table('settings')->insert([
            'code' => 'payment',
            'key' => 'list.corvus_wallets',
            'value' => json_encode([[
                'title' => 'Apple Pay / Google Pay',
                'title_en' => 'null',
                'code' => 'corvus_wallets',
                'status' => false,
                'sort_order' => 77,
                'custom' => 'preserved',
                'data' => [
                    'price' => 1.5,
                    'short_description' => 'Brzo plaćanje',
                    'short_description_en' => null,
                    'description' => 'Plaćanje putem Corvusa.',
                    'description_en' => ' NULL ',
                ],
            ]]),
            'json' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->insert([
            ['id' => 26042, 'payment_method' => 'null', 'payment_code' => 'corvus_wallets'],
            ['id' => 26046, 'payment_method' => ' NULL ', 'payment_code' => 'corvus_wallets'],
            ['id' => 26047, 'payment_method' => 'null', 'payment_code' => 'corvus'],
            ['id' => 26048, 'payment_method' => 'Wallet custom', 'payment_code' => 'corvus_wallets'],
        ]);

        $this->runRepairMigration();

        $setting = json_decode(DB::table('settings')->value('value'), true)[0];

        $this->assertSame('Apple Pay / Google Pay', $setting['title_en']);
        $this->assertSame('Fast and secure payment with Apple Pay or Google Pay', $setting['data']['short_description_en']);
        $this->assertSame('Pay with Apple Pay or Google Pay on the secure CorvusPay page.', $setting['data']['description_en']);
        $this->assertSame('Brzo plaćanje', $setting['data']['short_description']);
        $this->assertSame(1.5, $setting['data']['price']);
        $this->assertSame('preserved', $setting['custom']);
        $this->assertFalse($setting['status']);
        $this->assertSame(77, $setting['sort_order']);
        $this->assertSame('corvus', $setting['data']['credential_source']);

        $this->assertSame('Apple Pay / Google Pay', DB::table('orders')->where('id', 26042)->value('payment_method'));
        $this->assertSame('Apple Pay / Google Pay', DB::table('orders')->where('id', 26046)->value('payment_method'));
        $this->assertSame('null', DB::table('orders')->where('id', 26047)->value('payment_method'));
        $this->assertSame('Wallet custom', DB::table('orders')->where('id', 26048)->value('payment_method'));

        $this->runRepairMigration();
        $this->assertSame('Apple Pay / Google Pay', DB::table('orders')->where('id', 26042)->value('payment_method'));
    }

    public function testRepairRefusesToOverwriteMalformedWalletSettings(): void
    {
        DB::table('settings')->insert([
            'code' => 'payment',
            'key' => 'list.corvus_wallets',
            'value' => '{invalid-json',
            'json' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $this->runRepairMigration();
            $this->fail('The repair should reject malformed wallet settings.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Corvus wallets setting contains invalid JSON and was not changed.',
                $exception->getMessage()
            );
        }

        $this->assertSame('{invalid-json', DB::table('settings')->value('value'));
    }

    private function runRepairMigration(): void
    {
        require_once database_path('migrations/2026_08_28_093000_repair_corvus_wallets_null_strings.php');

        (new \RepairCorvusWalletsNullStrings())->up();
    }
}
