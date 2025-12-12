<?php

namespace App\Http\Controllers\Admin\Members;

class AdminMemberSettingsController extends \App\Http\Controllers\Admin\Settings\Members\AdminMemberSettingsController
{
    public function password()
    {
        request()->merge(['settings_section' => 'password']);

        return parent::index();
    }

    public function session()
    {
        request()->merge(['settings_section' => 'session']);

        return parent::index();
    }

    public function authentication()
    {
        request()->merge(['settings_section' => 'authentication']);

        return parent::index();
    }
}
