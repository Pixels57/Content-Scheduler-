<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Platform extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'status',
    ];
    
    /**
     * The posts that belong to the platform.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_platform')
            ->withTimestamps();
    }
}
