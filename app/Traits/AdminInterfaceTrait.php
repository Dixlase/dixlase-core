<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;

trait AdminInterfaceTrait
{
    // 変数宣言
    protected $siteName;
    protected $heading = '';
    protected $viewParams = [];
    protected $routeName = '';
    protected $settings = [];

    /**
     * 初期化処理
     */
    public function initialize()
    {
        if (!Schema::hasTable('base_settings')) {
            return;
        }

        $this->getSiteName();
        $this->getBaseSettings();
        $this->setRouteName();
        $this->setHeading();
    }

    protected function getSiteName()
    {
        $this->siteName = env('APP_NAME') 
            ?? config('app.name') 
            ?? DB::table('base_settings')->where('name', 'site_name')->value('value')
            ?? 'Dixlase';
        $this->viewParams['site_name'] = $this->siteName;
    }

    protected function getBaseSettings()
    {
        $this->settings = DB::table('base_settings')->get()->keyBy('name')->toArray();
        $this->viewParams['settings'] = $this->settings;
    }

    protected function setRouteName()
    {
        $this->routeName = Route::currentRouteName();
        $this->viewParams['route_name'] = $this->routeName;
    }

    protected function setHeading()
    {
        $routeName = Route::currentRouteName();
        $keys = explode('.', $routeName);
        array_shift($keys);

        $headingKey = implode('.', $keys) . '.heading';
        $heading = Lang::get('admin.' . $headingKey);

        $this->heading = $heading ?: 'No Heading';
        $this->viewParams['heading'] = $this->heading;
    }
}
