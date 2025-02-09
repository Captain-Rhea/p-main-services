<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class BlogArticleTagModel extends Pivot
{
    protected $table = 'blog_article_tags';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['article_id', 'tag_id', 'created_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = Carbon::now('Asia/Bangkok');
        });
    }
}
