<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminLoggedinController extends AdminController
{
    protected $member;

    public function __construct()
    {
        parent::__construct();
        //$this->setMember();
    }

    protected function setMember()
    {
        //$this->member = Auth::guard('member')->user();
        //Log::info($this->member);
        //$this->viewParams['member'] = $this->member;
    }
}
