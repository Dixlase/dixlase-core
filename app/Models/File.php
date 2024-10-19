<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use SoftDeletes;
    protected $table = 'files';
    protected $fillable = [
        'file_name',
        'file_type',
        'uploaded_by',
    ];
}
