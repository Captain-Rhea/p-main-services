<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;

class BlogActivityLogModel extends Model
{
    protected $table = 'blog_activity_logs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'article_id',
        'action',
        'details'
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = Uuid::uuid4()->toString();
            $model->created_at = Carbon::now('Asia/Bangkok');
        });
    }

    public function article()
    {
        return $this->belongsTo(BlogArticleModel::class, 'article_id');
    }
}
