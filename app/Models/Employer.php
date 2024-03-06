<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'hiring_date',
        'firing_date',
        'color'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function schedule()
    {
        return $this->hasMany(Schedule::class);
    }
}
