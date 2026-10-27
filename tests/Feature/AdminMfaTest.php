<?php

namespace Tests\Feature;

use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class AdminMfaTest extends TestCase
{
    use CreatesStoreData, RefreshDatabase;

    public function test_admin_can_complete_totp_challenge(): void
    {
        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $admin = $this->admin();
        $admin->forceFill(['mfa_enabled' => true, 'mfa_secret' => $secret])->save();

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('admin.mfa.challenge'));

        $this->actingAs($admin)->post(route('admin.mfa.challenge.verify'), [
            'code' => $totp->code($secret),
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(session('admin_mfa_passed'));
    }

    public function test_invalid_totp_code_is_rejected(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['mfa_enabled' => true, 'mfa_secret' => 'JBSWY3DPEHPK3PXP'])->save();

        $this->actingAs($admin)->from(route('admin.mfa.challenge'))->post(route('admin.mfa.challenge.verify'), [
            'code' => '000000',
        ])->assertRedirect(route('admin.mfa.challenge'))
            ->assertSessionHasErrors('code');
    }
}
