<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingSystem extends Model
{
    protected $table = 'setting_systems';
    protected $fillable = [
        'title',
    ];
}
