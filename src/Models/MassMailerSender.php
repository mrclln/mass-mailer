<?php

namespace Mrclln\MassMailer\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MassMailerSender extends Model
{
    protected $table = 'mass_mailer_senders';

    protected $fillable = [
        'name',
        'email',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
