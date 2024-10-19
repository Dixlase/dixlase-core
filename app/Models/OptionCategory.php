<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OptionCategory extends Model
{
    use SoftDeletes;
    protected $table = 'option_categories';
    protected $fillable = [
        'option_id',
        'title',
        'created_by',
    ];
}
