<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Slot extends Model
{
    use SoftDeletes;
    protected $table = 'slots';
    protected $fillable = [
        'title',
        'content',
        'status',
        'created_by',
        'updated_by'
    ];
}
