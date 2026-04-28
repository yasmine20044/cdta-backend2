<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Mews\Purifier\Facades\Purifier;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'image',
        'status',
        'excerpt',
        'author',
        'published_at',
        'category'
    ];

    // Mutator pour content
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = Crypt::encryptString($value);
    }

    // Accessor pour content
    public function getContentAttribute($value)
    {
        try {
            $decoded = Crypt::decryptString($value);
            return Purifier::clean($decoded); // HTML safe
        } catch (DecryptException $e) {
            return $value;
        }
    }

    // Mutator pour excerpt
    public function setExcerptAttribute($value)
    {
        $this->attributes['excerpt'] = Crypt::encryptString($value);
    }

    // Accessor pour excerpt
    public function getExcerptAttribute($value)
    {
        try {
            $decoded = Crypt::decryptString($value);
            return Purifier::clean($decoded); // HTML safe
        } catch (DecryptException $e) {
            return $value;
        }
    }
}