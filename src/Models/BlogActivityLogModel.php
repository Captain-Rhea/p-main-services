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
        'post_id',
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

    public function post()
    {
        return $this->belongsTo(BlogPostModel::class, 'post_id');
    }
}
