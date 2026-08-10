<?php

namespace Tests\Feature;

use App\Helpers\Session\CheckoutSession;
use App\Http\Livewire\Front\Checkout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutAddressAutofillTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_checkout_replaces_a_guest_address_with_profile_data(): void
    {
        $user = User::factory()->create();
        $user->details()->create([
            'fname' => 'Ana',
            'lname' => 'Anić',
            'phone' => '0912345678',
            'address' => 'Ilica 1',
            'city' => 'Zagreb',
            'zip' => '10000',
            'state' => 'Croatia',
            'company' => '',
            'oib' => '',
            'role' => 'customer',
            'status' => true,
        ]);

        session([
            'checkout.address' => [
                'fname' => '',
                'lname' => '',
                'email' => '',
                'phone' => '',
                'address' => '',
                'city' => '',
                'zip' => '',
                'state' => 'Croatia',
                'company' => '',
                'oib' => '',
            ],
        ]);

        $this->actingAs($user);

        $checkout = new Checkout();
        $checkout->mount();

        $this->assertSame('Ana', $checkout->address['fname']);
        $this->assertSame('Anić', $checkout->address['lname']);
        $this->assertSame($user->email, $checkout->address['email']);
        $this->assertSame('Ilica 1', $checkout->address['address']);
        $this->assertSame($user->id, CheckoutSession::getAddressUserId());
    }

    public function test_entering_postal_code_fills_the_city_and_session(): void
    {
        $checkout = new Checkout();
        $checkout->address['state'] = 'Croatia';
        $checkout->address['zip'] = '51000';

        $checkout->updatedAddress('51000', 'zip');

        $this->assertSame('Rijeka', $checkout->address['city']);
        $this->assertSame('51000', CheckoutSession::getAddress()['zip']);
        $this->assertSame('Rijeka', CheckoutSession::getAddress()['city']);
    }

    public function test_entering_city_fills_the_postal_code_and_session(): void
    {
        $checkout = new Checkout();
        $checkout->address['state'] = 'Croatia';
        $checkout->address['city'] = 'Osijek';

        $checkout->updatedAddress('Osijek', 'city');

        $this->assertSame('31000', $checkout->address['zip']);
        $this->assertSame('Osijek', CheckoutSession::getAddress()['city']);
    }

    public function test_foreign_address_is_not_replaced_with_a_croatian_place(): void
    {
        $checkout = new Checkout();
        $checkout->address['state'] = 'Germany';
        $checkout->address['zip'] = '10000';
        $checkout->address['city'] = 'Berlin';

        $checkout->updatedAddress('10000', 'zip');

        $this->assertSame('10000', $checkout->address['zip']);
        $this->assertSame('Berlin', $checkout->address['city']);
    }

    public function test_disabling_r1_invoice_clears_company_data_and_session(): void
    {
        $checkout = new Checkout();
        $checkout->address['company'] = 'Test d.o.o.';
        $checkout->address['oib'] = '12345678901';

        $checkout->updatedR1Invoice(false);

        $this->assertFalse($checkout->r1_invoice);
        $this->assertSame('', $checkout->address['company']);
        $this->assertSame('', $checkout->address['oib']);
        $this->assertSame('', CheckoutSession::getAddress()['company']);
        $this->assertSame('', CheckoutSession::getAddress()['oib']);
    }
}
