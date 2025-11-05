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
      'comments',
      'area',
      'gender'
    ];

    protected $appends = ['full_name'];

    /**
     * Set the telephone attribute with normalization
     */
    public function setTelephoneAttribute($value)
    {
        $this->attributes['telephone'] = $this->normalizePhoneNumber($value);
    }

    /**
     * Normalize phone number to a consistent format
     * Removes spaces, dashes, and parentheses
     * Converts to international format with +30 prefix for Greek numbers
     */
    private function normalizePhoneNumber($phone)
    {
        if (empty($phone)) {
            return $phone;
        }

        // Remove all spaces, dashes, parentheses, and other non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // If it starts with 00, replace with +
        if (substr($phone, 0, 2) === '00') {
            $phone = '+' . substr($phone, 2);
        }
        
        // If it starts with +30, keep it as is
        if (substr($phone, 0, 3) === '+30') {
            return $phone;
        }
        
        // If it starts with 30, add +
        if (substr($phone, 0, 2) === '30') {
            return '+' . $phone;
        }
        
        // If it starts with 6 or 2 and is 10 digits (Greek mobile/landline), add +30
        if (preg_match('/^[62]\d{9}$/', $phone)) {
            return '+30' . $phone;
        }
        
        // If it doesn't start with +, assume it needs +30 prefix (Greek number)
        if (substr($phone, 0, 1) !== '+') {
            return '+30' . $phone;
        }
        
        return $phone;
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
