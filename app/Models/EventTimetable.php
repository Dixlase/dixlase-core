<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventTimetable extends Model
{
    use SoftDeletes;
    protected $table = 'event_timetables';
    protected $fillable = [
        'title',
        'start',
        'end',
        'created_by',
    ];
}
