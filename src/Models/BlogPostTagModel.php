<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class BlogPostTagModel extends Pivot
{
    protected $table = 'blog_post_tags';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['post_id', 'tag_id', 'created_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = Carbon::now('Asia/Bangkok');
        });
    }
}
