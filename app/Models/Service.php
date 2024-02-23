<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'duration', 'cost', 'sort_name'];

    public function bookings()
    {
        return $this->belongsToMany(Booking::class);
    }
}
