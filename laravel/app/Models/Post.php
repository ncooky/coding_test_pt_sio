<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    protected $fillable = [
        'title', 'caption'
    ];

    public function postLikes()
    {
        return $this->hasMany(PostLikes::class);
    }

    public function postImages()
    {
        return $this->hasMany(PostImages::class);
    }

    public function postComments()
    {
        return $this->hasMany(PostComments::class);
    }
}
