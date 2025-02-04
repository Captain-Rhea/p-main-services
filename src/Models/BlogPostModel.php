<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class BlogPostModel extends Model
{
    use SoftDeletes;

    protected $table = 'blog_posts';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'title_th',
        'title_en',
        'slug',
        'content_th',
        'content_en',
        'summary_th',
        'summary_en',
        'cover_image',
        'status',
        'published_at',
        'user_id',
        'locked_by',
        'locked_at',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'content_th' => 'array',
        'content_en' => 'array',
        'cover_image' => 'array',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = Carbon::now('Asia/Bangkok');
            $model->updated_at = Carbon::now('Asia/Bangkok');
        });

        static::updating(function ($model) {
            $model->updated_at = Carbon::now('Asia/Bangkok');
        });
    }

    public function categories()
    {
        return $this->belongsToMany(BlogCategoryModel::class, 'blog_post_categories', 'post_id', 'category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(BlogTagModel::class, 'blog_post_tags', 'post_id', 'tag_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(BlogActivityLogModel::class, 'post_id');
    }
}
