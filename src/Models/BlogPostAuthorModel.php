<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class BlogPostAuthorModel extends Pivot
{
    protected $table = 'blog_post_authors';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['post_id', 'author_id', 'created_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = Carbon::now('Asia/Bangkok');
        });
    }
}
