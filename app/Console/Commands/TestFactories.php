<?php

namespace App\Console\Commands;

use App\Models\MemberLoginAttempt;
use App\Models\MembersTrustedDevice;
use App\Models\MembersTwoFactorToken;
use App\Models\Member;
use Illuminate\Console\Command;

class TestFactories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:test-factories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test that all factories are properly configured';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing factory configurations...');

        try {
            // Test MemberLoginAttempt factory
            $loginAttempt = MemberLoginAttempt::factory()->make();
            $this->info('✓ MemberLoginAttempt factory works');
            $this->line("  - Generated data: {$loginAttempt->identifier}, {$loginAttempt->ip_address}");

            // Test TrustedDevice factory
            $trustedDevice = MembersTrustedDevice::factory()->make();
            $this->info('✓ TrustedDevice factory works');
            $this->line("  - Generated data: {$trustedDevice->device_name}, {$trustedDevice->token}");

            // Test MembersTwoFactorToken factory
            $twoFactorToken = MembersTwoFactorToken::factory()->make();
            $this->info('✓ MembersTwoFactorToken factory works');
            $this->line("  - Generated data: {$twoFactorToken->code}, {$twoFactorToken->expires_at->format('Y-m-d H:i:s')}");

            // Test Member factory (should already work)
            $member = Member::factory()->make();
            $this->info('✓ Member factory works');
            $this->line("  - Generated data: {$member->name}, {$member->email}");

            $this->info('');
            $this->info('All factories are properly configured! ✅');
            $this->info('You can now use: php artisan admin:generate-test-data');

        } catch (\Exception $e) {
            $this->error('Factory test failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
