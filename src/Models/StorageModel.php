<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class StorageModel extends Model
{
    protected $table = 'image_storage';
    protected $primaryKey = 'storage_id';
    public $timestamps = false;

    protected $fillable = [
        'image_id',
        'group',
        'image_name',
        'base_url',
        'lazy_url',
        'base_size',
        'lazy_size',
        'created_by'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $casts = [
        'base_size' => 'integer',
        'lazy_size' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
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
}
