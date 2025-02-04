<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;

class BlogCategoryModel extends Model
{
    protected $table = 'blog_categories';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name_th', 'name_en', 'slug'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = Uuid::uuid4()->toString();
            $model->slug = self::createSlug($model->name_en);
            $model->created_at = Carbon::now('Asia/Bangkok');
            $model->updated_at = Carbon::now('Asia/Bangkok');
        });

        static::updating(function ($model) {
            if ($model->isDirty('name_en')) {
                $model->slug = self::createSlug($model->name_en);
            }
            $model->updated_at = Carbon::now('Asia/Bangkok');
        });
    }

    public function posts()
    {
        return $this->belongsToMany(BlogPostModel::class, 'blog_post_categories', 'category_id', 'post_id');
    }

    private static function createSlug($string)
    {
        $string = trim(mb_strtolower($string, 'UTF-8'));
        $string = preg_replace('/[^a-z0-9ก-๙เ-๋]+/u', '-', $string);
        return trim($string, '-');
    }
}
