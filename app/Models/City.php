<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'state_id'
    ];

    protected $cast = [
        'name'     => 'string',
        'state_id' => 'integer',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
