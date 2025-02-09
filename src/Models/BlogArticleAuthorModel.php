<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class BlogArticleAuthorModel extends Pivot
{
    protected $table = 'blog_article_authors';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['article_id', 'author_id', 'created_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = Carbon::now('Asia/Bangkok');
        });
    }
}
