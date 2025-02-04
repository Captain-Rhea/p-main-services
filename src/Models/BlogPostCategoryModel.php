<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class BlogPostCategoryModel extends Pivot
{
    protected $table = 'blog_post_categories';
    protected $fillable = ['post_id', 'category_id'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = Carbon::now('Asia/Bangkok');
        });
    }
}
