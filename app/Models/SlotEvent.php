<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlotEvent extends Model
{
    use SoftDeletes;
    protected $table = 'slot_events';
    protected $fillable = [
        'slot_id',
        'event_id',
        'status',
        'created_by',
        'updated_by'
    ];
}
