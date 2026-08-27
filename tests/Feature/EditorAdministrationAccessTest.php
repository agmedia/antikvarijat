<?php

namespace Tests\Feature;

use App\Models\User;
use Bouncer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;
use Tests\TestCase;

class EditorAdministrationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_only_sees_my_profile_among_restricted_navigation_items(): void
    {
        $editor = $this->userWithRole('editor');
        Bouncer::allow($editor)->everything();
        Bouncer::refresh();

        $response = $this->actingAs($editor)->get(route('profile.show'));

        $response->assertOk();
        $response->assertSeeText('Moj Profil');
        $response->assertDontSeeText('Korisnici');
        $response->assertDontSeeText('Widgets');
        $response->assertDontSeeText('Postavke');
        $response->assertDontSeeText('Očisti Cache');
        $response->assertDontSeeText('Održavanje ON');
        $response->assertDontSeeText('Održavanje OFF');
        $response->assertDontSeeText('Promjena korisničke uloge');
    }

    public function test_editor_cannot_open_users_widgets_or_settings_directly(): void
    {
        $editor = $this->userWithRole('editor');
        $this->actingAs($editor);

        $this->get(route('users'))->assertForbidden();
        $this->get(route('users.edit', ['user' => $editor]))->assertForbidden();
        $this->get(route('widgets'))->assertForbidden();
        $this->get(route('widget.api.get-links'))->assertForbidden();
        $this->get(route('api.index'))->assertForbidden();
        $this->get(route('shippings'))->assertForbidden();
        $this->get(route('cache'))->assertForbidden();
        $this->get(route('maintenance.on'))->assertForbidden();
        $this->get(route('maintenance.off'))->assertForbidden();

        foreach ([
            'widget.destroy',
            'api.api.import',
            'api.order.status.store',
            'api.payment.store',
            'api.shipping.store',
            'api.taxes.store',
            'api.currencies.store',
        ] as $routeName) {
            $this->post(route($routeName))->assertForbidden();
        }
    }

    public function test_editor_cannot_promote_themselves_with_the_user_update_endpoint(): void
    {
        $editor = $this->userWithRole('editor');
        Bouncer::role()->create(['name' => 'superadmin', 'title' => 'Super Administrator']);

        $this->actingAs($editor)
            ->patch(route('users.update', ['user' => $editor]), [
                'username' => $editor->name,
                'email' => $editor->email,
                'role' => 'superadmin',
            ])
            ->assertForbidden();

        Bouncer::refresh();
        $editor = $editor->fresh('details');

        $this->assertSame('editor', $editor->details->role);
        $this->assertTrue($editor->isAn('editor'));
        $this->assertFalse($editor->isAn('superadmin'));
    }

    public function test_editor_restrictions_apply_to_each_stored_role_source(): void
    {
        $detailsEditor = User::factory()->create();
        $detailsEditor->details()->create([
            'fname' => 'Details',
            'lname' => 'Editor',
            'role' => 'editor',
            'status' => true,
        ]);

        $this->actingAs($detailsEditor->fresh('details'))
            ->get(route('profile.show'))
            ->assertOk()
            ->assertDontSeeText('Korisnici')
            ->assertDontSeeText('Widgets')
            ->assertDontSeeText('Postavke')
            ->assertDontSeeText('Poklon bonovi');
        $this->get(route('users'))->assertForbidden();

        $bouncerEditor = User::factory()->create();
        $editorRole = Bouncer::role()->create(['name' => 'editor', 'title' => 'Editor']);
        Bouncer::assign($editorRole)->to($bouncerEditor);
        Bouncer::refresh();

        $this->actingAs($bouncerEditor)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertDontSeeText('Korisnici')
            ->assertDontSeeText('Widgets')
            ->assertDontSeeText('Postavke')
            ->assertDontSeeText('Poklon bonovi');
        $this->get(route('users'))->assertForbidden();
    }

    public function test_editor_bearer_token_cannot_bypass_the_admin_route_restriction(): void
    {
        $editor = $this->userWithRole('editor');
        $token = $editor->createToken('editor-restriction-test')->plainTextToken;

        $this->withToken($token)
            ->getJson(route('users'))
            ->assertForbidden();
    }

    public function test_profile_update_ignores_an_injected_role(): void
    {
        $editor = $this->userWithRole('editor');
        Bouncer::role()->create(['name' => 'superadmin', 'title' => 'Super Administrator']);
        $this->actingAs($editor);

        Livewire::test(UpdateProfileInformationForm::class)
            ->set('state', [
                'name' => 'Urednik Profil',
                'email' => 'urednik-profil@example.com',
                'role' => 'superadmin',
            ])
            ->call('updateProfileInformation');

        Bouncer::refresh();
        $editor = $editor->fresh('details');

        $this->assertSame('Urednik Profil', $editor->name);
        $this->assertSame('urednik-profil@example.com', $editor->email);
        $this->assertSame('editor', $editor->details->role);
        $this->assertTrue($editor->isAn('editor'));
        $this->assertFalse($editor->isAn('superadmin'));
    }

    public function test_administrator_still_sees_the_restricted_navigation_items(): void
    {
        $administrator = $this->userWithRole('admin');

        $response = $this->actingAs($administrator)->get(route('profile.show'));

        $response->assertOk();
        $response->assertSeeText('Moj Profil');
        $response->assertSeeText('Korisnici');
        $response->assertSeeText('Widgets');
        $response->assertSeeText('Postavke');
        $response->assertSeeText('Očisti Cache');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->details()->create([
            'fname' => ucfirst($role),
            'lname' => 'Test',
            'role' => $role,
            'status' => true,
        ]);

        $bouncerRole = Bouncer::role()->create([
            'name' => $role,
            'title' => ucfirst($role),
        ]);
        Bouncer::assign($bouncerRole)->to($user);
        Bouncer::refresh();

        return $user->fresh('details');
    }
}
