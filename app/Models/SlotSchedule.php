<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlotSchedule extends Model
{
    use SoftDeletes;
    protected $table = 'slot_schedules';
    protected $fillable = [
        'slot_id',
    ];
}
