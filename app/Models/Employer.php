<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'hiring_date',
        'firing_date',
        'color',
        'order'
    ];

    protected $dates = ['deleted_at'];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function schedule()
    {
        return $this->hasMany(Schedule::class);
    }
}
