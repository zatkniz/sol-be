<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = ['employer_id', 'date', 'time_start', 'time_end', 'repo', 'allowance'];

    public function employer () {
        return $this->belongsTo(Employer::class);
    }
}
