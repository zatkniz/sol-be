<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'user_id', 'duration', 'cost', 'date', 'time', 'comments', 'employer_id'];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }
}
