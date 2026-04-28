<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class Service extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'status',
        'image'
    ];

    // Chiffrement de description
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = Crypt::encryptString($value);
    }

    public function getDescriptionAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            return $value;
        }
    }
}