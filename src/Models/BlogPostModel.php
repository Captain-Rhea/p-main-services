<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;

class BlogPostModel extends Model
{
    use SoftDeletes;

    protected $table = 'blog_posts';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title_th',
        'title_en',
        'slug',
        'content_th',
        'content_en',
        'summary_th',
        'summary_en',
        'cover_image',
        'status',
        'published_by',
        'published_at',
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
            $timestamp = Carbon::now('Asia/Bangkok')->timestamp;
            $slug = "{$timestamp}_draft";

            $model->slug = $slug;
            $model->status = 'draft';
            $model->created_at = Carbon::now('Asia/Bangkok');
            $model->updated_at = Carbon::now('Asia/Bangkok');
        });

        static::updating(function ($model) {
            $model->updated_at = Carbon::now('Asia/Bangkok');
        });

        static::deleting(function ($model) {
            $model->deleted_at = Carbon::now('Asia/Bangkok');
        });
    }

    public function categories()
    {
        return $this->belongsToMany(BlogCategoryModel::class, 'blog_post_categories', 'post_id', 'category_id')
            ->using(BlogPostCategoryModel::class);
    }

    public function tags()
    {
        return $this->belongsToMany(BlogTagModel::class, 'blog_post_tags', 'post_id', 'tag_id')
            ->using(BlogPostTagModel::class);
    }

    public function authors()
    {
        return $this->belongsToMany(BlogAuthorModel::class, 'blog_post_authors', 'post_id', 'author_id')
            ->using(BlogPostAuthorModel::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(BlogActivityLogModel::class, 'post_id');
    }
}
