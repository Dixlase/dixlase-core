<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SecuritySetting;
use App\Captcha\GoogleRecaptchaDriver;
use App\Helpers\CaptchaHelper;
use Illuminate\Http\Request;

class TestCaptchaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'captcha:test {--token= : reCAPTCHA token to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Google reCAPTCHA configuration and validation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Google reCAPTCHA Configuration...');
        
        // Check if CAPTCHA is enabled
        $captchaEnabled = SecuritySetting::get('captcha_enabled', false);
        $captchaDriver = SecuritySetting::get('captcha_driver', 'google');
        
        $this->info("CAPTCHA Enabled: " . ($captchaEnabled ? 'Yes' : 'No'));
        $this->info("CAPTCHA Driver: " . $captchaDriver);
        
        if (!$captchaEnabled || !in_array($captchaDriver, ['google', 'google_enterprise'])) {
            $this->warn('Google reCAPTCHA is not enabled or not selected as driver.');
            return;
        }
        
        // Check configuration
        $captchaDriver = SecuritySetting::get('captcha_driver', 'google');
        $useEnterprise = $captchaDriver === 'google_enterprise';
        
        if ($captchaDriver === 'google_enterprise') {
            $siteKey = CaptchaHelper::getSiteKey();
            $secretKey = CaptchaHelper::getSecretKey();
            $version = 'v3'; // Enterprise always uses v3
        } else {
            $siteKey = CaptchaHelper::getSiteKey();
            $secretKey = CaptchaHelper::getSecretKey();
            $version = CaptchaHelper::getGoogleVersion();
        }
        
        $projectId = CaptchaHelper::getGoogleProjectId();
        $minScore = CaptchaHelper::getGoogleMinScore();
        
        $this->info("Site Key: " . ($siteKey ? 'Set (' . substr($siteKey, 0, 10) . '...)' : 'Not set'));
        $this->info("Secret Key: " . ($secretKey ? 'Set (' . substr($secretKey, 0, 10) . '...)' : 'Not set'));
        $this->info("Use Enterprise API: " . ($useEnterprise ? 'Yes' : 'No'));
        $this->info("Project ID: " . ($projectId ?: 'Not set'));
        $this->info("Version: " . $version);
        $this->info("Min Score: " . $minScore);
        
        if (empty($siteKey) || empty($secretKey)) {
            $this->error('Site key or secret key is not configured.');
            return;
        }
        
        if ($useEnterprise && empty($projectId)) {
            $this->error('Enterprise API is enabled but project ID is not set.');
            return;
        }
        
        // Test driver initialization
        try {
            $driver = new GoogleRecaptchaDriver();
            $this->info('Driver initialized successfully.');
            
            // Check if driver is enabled
            if ($driver->isEnabled()) {
                $this->info('Driver is enabled and ready to use.');
            } else {
                $this->warn('Driver is not enabled (missing configuration).');
            }
            
            // Test token validation if provided
            $token = $this->option('token');
            if ($token) {
                $this->info('Testing token validation...');
                
                // Create a mock request
                $request = new Request();
                $request->merge(['g-recaptcha-response' => $token]);
                $request->server->set('REMOTE_ADDR', '127.0.0.1');
                $request->headers->set('User-Agent', 'Test Command');
                
                $result = $driver->verify($request);
                
                if ($result->isValid()) {
                    $this->info('✅ Token validation successful!');
                    $this->info('Score: ' . ($result->getScore() ?? 'N/A'));
                    $this->info('Action: ' . ($result->getAction() ?? 'N/A'));
                } else {
                    $this->error('❌ Token validation failed.');
                    $this->error('Errors: ' . implode(', ', $result->getErrors()));
                }
            } else {
                $this->comment('To test token validation, use: --token=YOUR_RECAPTCHA_TOKEN');
            }
            
        } catch (\Exception $e) {
            $this->error('Error testing driver: ' . $e->getMessage());
        }
        
        $this->info('Test completed.');
    }
}
