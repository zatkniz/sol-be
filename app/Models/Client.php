<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
      'last_name',
      'first_name',
      'telephone',
      'email',
      'address',
      'comments'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
