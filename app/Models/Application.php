<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Event;
use App\Models\ApplicationDetail;

class Application extends Model
{
    use SoftDeletes;
    protected $table = 'applications';

    protected $fillable = [
        'user_id',
        'last_name',
        'first_name',
        'last_name_kana',
        'first_name_kana',
        'email',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function applicationDetails()
    {
        return $this->hasMany(ApplicationDetail::class);
    }
}
