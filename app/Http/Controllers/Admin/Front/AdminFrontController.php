<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */


namespace App\Http\Controllers\Admin\Front;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Request;

class AdminFrontController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return view('admin::front/index', $this->viewParams);
    }

    public function design()
    {
        return view('admin::front/design', $this->viewParams);
    }

    public function settings()
    {
        return view('admin::front/settings', $this->viewParams);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }
}
