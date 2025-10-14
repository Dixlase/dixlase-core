<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class ShortcodeServiceProvider extends ServiceProvider
{
    protected $shortcodes = [

    ];

    public function register()
    {
        $this->app->singleton('shortcode', function ($app) {
            return new \App\Services\ShortcodeManager($app);
        });
    }

    public function boot()
    {
        // ショートコードを登録
        $shortcode = $this->app['shortcode'];
        foreach ($this->shortcodes as $tag => $class) {
            $shortcode->add($tag, $class);
        }

        // Bladeディレクティブの登録
        Blade::directive('shortcode', function ($expression) {
            return "<?php echo shortcode_parse($expression); ?>";
        });
    }
}
