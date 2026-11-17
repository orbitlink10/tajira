<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    public function test_customers_cannot_access_the_administration_area(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_roles_enforce_server_side_permissions(): void
    {
        $catalogue = $this->admin(User::ROLE_CATALOGUE_MANAGER);
        $content = $this->admin(User::ROLE_CONTENT_EDITOR);
        $orders = $this->admin(User::ROLE_ORDER_MANAGER);

        $this->actingAs($catalogue)->get('/admin/products')->assertOk();
        $this->actingAs($catalogue)->get('/admin/settings')->assertForbidden();

        $this->actingAs($content)->get('/admin/pages')->assertOk();
        $this->actingAs($content)->get('/admin/products')->assertForbidden();

        $this->actingAs($orders)->get('/admin/orders')->assertOk();
        $this->actingAs($orders)->get('/admin/products')->assertForbidden();
    }

    public function test_mfa_enabled_admin_must_complete_the_challenge(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['mfa_enabled' => true, 'mfa_secret' => 'JBSWY3DPEHPK3PXP'])->save();

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('admin.mfa.challenge'));
    }

    public function test_password_login_reaches_dashboard_when_mfa_enrollment_is_optional(): void
    {
        config(['tajira.security.require_admin_mfa' => false]);
        $admin = $this->admin();
        $admin->password = 'admin123';
        $admin->save();

        $this->post(route('admin.login'), [
            'email' => $admin->email,
            'password' => 'admin123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))->assertOk();
    }
}
