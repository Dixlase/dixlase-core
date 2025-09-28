<?php

namespace App\Console\Commands;

use App\Models\MemberLoginAttempt;
use App\Models\TrustedDevice;
use App\Models\MembersTwoFactorToken;
use App\Models\Member;
use App\Models\Media;
use Database\Factories\MemberPasswordResetTokenFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTestMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:generate-test-media 
                            {--type=all : Type of test data to generate (all, login_attempts, password_reset_tokens, trusted_devices, two_factor_tokens, cache, sessions, media)}
                            {--count=50 : Number of records to generate}
                            {--old-ratio=0.3 : Ratio of old records (for cleanup testing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test media files and other test data for development';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $count = (int) $this->option('count');
        $oldRatio = (float) $this->option('old-ratio');
        
        $oldCount = (int) ($count * $oldRatio);
        $recentCount = $count - $oldCount;

        $this->info("Generating {$count} test records (Old: {$oldCount}, Recent: {$recentCount})...");

        switch ($type) {
            case 'all':
                $this->generateLoginAttempts($count, $oldRatio);
                $this->generatePasswordResetTokens($count, $oldRatio);
                $this->generateTrustedDevices($count, $oldRatio);
                $this->generateTwoFactorTokens($count, $oldRatio);
                $this->generateCacheData($count);
                $this->generateSessionData($count, $oldRatio);
                $this->generateMediaData($count, $oldRatio);
                break;
            case 'login_attempts':
                $this->generateLoginAttempts($count, $oldRatio);
                break;
            case 'password_reset_tokens':
                $this->generatePasswordResetTokens($count, $oldRatio);
                break;
            case 'trusted_devices':
                $this->generateTrustedDevices($count, $oldRatio);
                break;
            case 'two_factor_tokens':
                $this->generateTwoFactorTokens($count, $oldRatio);
                break;
            case 'cache':
                $this->generateCacheData($count);
                break;
            case 'sessions':
                $this->generateSessionData($count, $oldRatio);
                break;
            case 'media':
                $this->generateMediaData($count, $oldRatio);
                break;
            default:
                $this->error("Invalid type: {$type}");
                return 1;
        }

        $this->info('Test data generation completed!');
        return 0;
    }

    private function generateLoginAttempts(int $count): void
    {
        $this->line("Creating {$count} login attempts...");
        
        // Get existing members or create a few if none exist
        $members = \App\Models\Member::limit(10)->get();
        if ($members->isEmpty()) {
            $members = \App\Models\Member::factory()->count(3)->create();
        }
        
        $oldCount = (int) ($count * $this->option('old-ratio'));
        $recentCount = $count - $oldCount;
        
        // Create old login attempts using existing members
        MemberLoginAttempt::factory()
            ->count($oldCount)
            ->old()
            ->state(function () use ($members) {
                return ['identifier' => $members->random()->email];
            })
            ->create();
            
        // Create recent login attempts using existing members
        MemberLoginAttempt::factory()
            ->count($recentCount)
            ->recent()
            ->state(function () use ($members) {
                return ['identifier' => $members->random()->email];
            })
            ->create();
            
        $this->info("✓ Created {$count} login attempt records");
    }

    private function generatePasswordResetTokens(int $count, float $oldRatio): void
    {
        $oldCount = (int) ($count * $this->option('old-ratio'));
        $recentCount = $count - $oldCount;

        $this->line("Creating {$count} password reset tokens...");
        
        // Get existing members or create a few if none exist
        $members = \App\Models\Member::limit(10)->get();
        if ($members->isEmpty()) {
            $members = \App\Models\Member::factory()->count(3)->create();
        }
        
        // Create old tokens using unique fake emails
        for ($i = 0; $i < $oldCount; $i++) {
            DB::table('members_password_reset_tokens')->insert([
                'email' => fake()->unique()->safeEmail(),
                'token' => \Illuminate\Support\Str::random(64),
                'created_at' => fake()->dateTimeBetween('-35 days', '-30 days'),
            ]);
        }
        
        // Create recent tokens using unique fake emails
        for ($i = 0; $i < $recentCount; $i++) {
            DB::table('members_password_reset_tokens')->insert([
                'email' => fake()->unique()->safeEmail(),
                'token' => \Illuminate\Support\Str::random(64),
                'created_at' => fake()->dateTimeBetween('-7 days', 'now'),
            ]);
        }
            
        $this->info("✓ Created {$count} password reset token records");
    }

    private function generateTrustedDevices(int $count, float $oldRatio): void
    {
        $oldCount = (int) ($count * $this->option('old-ratio'));
        $recentCount = $count - $oldCount;

        $this->line("Creating {$count} trusted devices...");
        
        // Get existing members or create a few if none exist
        $members = \App\Models\Member::limit(10)->get();
        if ($members->isEmpty()) {
            $members = \App\Models\Member::factory()->count(3)->create();
        }
        
        // Create old trusted devices using existing members
        TrustedDevice::factory()
            ->count($oldCount)
            ->old()
            ->state(function () use ($members) {
                return ['member_id' => $members->random()->id];
            })
            ->create();
            
        // Create recent trusted devices using existing members
        TrustedDevice::factory()
            ->count($recentCount)
            ->recent()
            ->state(function () use ($members) {
                return ['member_id' => $members->random()->id];
            })
            ->create();
            
        $this->info("✓ Created {$count} trusted device records");
    }

    private function generateTwoFactorTokens(int $count, float $oldRatio): void
    {
        $oldCount = (int) ($count * $this->option('old-ratio'));
        $recentCount = $count - $oldCount;

        $this->line("Creating {$count} two-factor tokens...");
        
        // Get existing members or create a few if none exist
        $members = \App\Models\Member::limit(10)->get();
        if ($members->isEmpty()) {
            $members = \App\Models\Member::factory()->count(3)->create();
        }
        
        // Create old two-factor tokens using existing members
        MembersTwoFactorToken::factory()
            ->count($oldCount)
            ->old()
            ->state(function () use ($members) {
                return ['member_id' => $members->random()->id];
            })
            ->create();
            
        // Create valid two-factor tokens using existing members
        MembersTwoFactorToken::factory()
            ->count($recentCount)
            ->valid()
            ->state(function () use ($members) {
                return ['member_id' => $members->random()->id];
            })
            ->create();
            
        $this->info("✓ Created {$count} two-factor token records");
    }

    private function generateCacheData(int $count): void
    {
        $this->line("Creating {$count} cache entries...");
        
        $expiredCount = (int) ($count * 0.4);
        $validCount = $count - $expiredCount;
        
        // Create expired cache entries
        for ($i = 0; $i < $expiredCount; $i++) {
            DB::table('cache')->insert([
                'key' => 'test_' . \Illuminate\Support\Str::random(10),
                'value' => serialize(['test' => 'data', 'timestamp' => time()]),
                'expiration' => time() - rand(3600, 86400), // Expired 1 hour to 1 day ago
            ]);
        }
        
        // Create valid cache entries
        for ($i = 0; $i < $validCount; $i++) {
            DB::table('cache')->insert([
                'key' => 'test_' . \Illuminate\Support\Str::random(10),
                'value' => serialize(['test' => 'data', 'timestamp' => time()]),
                'expiration' => time() + rand(3600, 86400), // Expires in 1 hour to 1 day
            ]);
        }
        
        $this->info("✓ Created {$count} cache entries");
    }

    private function generateSessionData(int $count, float $oldRatio): void
    {
        $this->line("Creating {$count} session records...");
        
        $oldCount = (int) ($count * $oldRatio);
        $recentCount = $count - $oldCount;
        
        // Create old sessions (older than 7 days)
        for ($i = 0; $i < $oldCount; $i++) {
            DB::table('sessions')->insert([
                'id' => \Illuminate\Support\Str::random(40),
                'user_id' => null,
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'payload' => base64_encode(serialize(['test' => 'session_data'])),
                'last_activity' => fake()->dateTimeBetween('-30 days', '-8 days')->getTimestamp(),
            ]);
        }
        
        // Create recent sessions (within 7 days)
        for ($i = 0; $i < $recentCount; $i++) {
            DB::table('sessions')->insert([
                'id' => \Illuminate\Support\Str::random(40),
                'user_id' => null,
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'payload' => base64_encode(serialize(['test' => 'session_data'])),
                'last_activity' => fake()->dateTimeBetween('-6 days', 'now')->getTimestamp(),
            ]);
        }
        
        $this->info("✓ Created {$count} session records");
    }

    private function generateMediaData(int $count, float $oldRatio): void
    {
        $oldCount = (int) ($count * $oldRatio);
        $recentCount = $count - $oldCount;

        $this->line("Creating {$count} media files...");
        
        // Get existing members or create a few if none exist
        $members = \App\Models\Member::limit(10)->get();
        if ($members->isEmpty()) {
            $members = \App\Models\Member::factory()->count(3)->create();
        }
        
        // Create old media files (日本語・英語ランダム)
        $oldJapaneseCount = (int) ($oldCount * 0.6); // 60%を日本語
        $oldEnglishCount = $oldCount - $oldJapaneseCount;
        
        // 古い日本語メディア
        Media::factory()
            ->count($oldJapaneseCount)
            ->japanese()
            ->old()
            ->state(function () use ($members) {
                return ['uploaded_by' => $members->random()->id];
            })
            ->create();
            
        // 古い英語メディア
        Media::factory()
            ->count($oldEnglishCount)
            ->english()
            ->old()
            ->state(function () use ($members) {
                return ['uploaded_by' => $members->random()->id];
            })
            ->create();
        
        // Create recent media files (日本語・英語ランダム)
        $recentJapaneseCount = (int) ($recentCount * 0.6); // 60%を日本語
        $recentEnglishCount = $recentCount - $recentJapaneseCount;
        
        // 最近の日本語メディア
        Media::factory()
            ->count($recentJapaneseCount)
            ->japanese()
            ->recent()
            ->state(function () use ($members) {
                return ['uploaded_by' => $members->random()->id];
            })
            ->create();
            
        // 最近の英語メディア
        Media::factory()
            ->count($recentEnglishCount)
            ->english()
            ->recent()
            ->state(function () use ($members) {
                return ['uploaded_by' => $members->random()->id];
            })
            ->create();
            
        $this->info("✓ Created {$count} media file records (Japanese: " . ($oldJapaneseCount + $recentJapaneseCount) . ", English: " . ($oldEnglishCount + $recentEnglishCount) . ")");
    }
}
