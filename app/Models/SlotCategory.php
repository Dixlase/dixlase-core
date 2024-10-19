<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlotCategory extends Model
{
    use SoftDeletes;
    protected $table = 'slot_categories';
    protected $fillable = [
        'slot_id',
        'description',
        'capacity',
    ];
}
