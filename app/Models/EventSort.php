<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventSort extends Model
{
    use SoftDeletes;
    protected $table = 'event_sorts';
    protected $fillable = [
        'event_id',
        'created_by',
    ];
}
