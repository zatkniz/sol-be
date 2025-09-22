<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'duration',
        'cost',
        'date',
        'time',
        'comments',
        'comments_second',
        'employer_id',
        'requested',
        'secondary_employer_id',
        'requested_secondary',
        'secondary_duration',
        'came'
    ];

    protected $casts = [
        'date' => 'datetime',
        'came' => 'boolean',
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

    public function bookingServices()
    {
        return $this->hasMany(BookingService::class);
    }

    public function secondaryServices()
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

    public function employerSecondary()
    {
        return $this->belongsTo(Employer::class,
        'secondary_employer_id');
    }
}
