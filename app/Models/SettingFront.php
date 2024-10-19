<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingFront extends Model
{
    protected $table = 'setting_fronts';
    protected $fillable = [
        'title',
    ];
}
