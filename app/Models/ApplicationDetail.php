<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class ApplicationDetail extends Model
{
    use SoftDeletes;
    protected $table = 'application_details';
    protected $fillable = [
        'user_id',
        'application_id',
        'slot_id',
        'slot_category_id',
    ];
}
