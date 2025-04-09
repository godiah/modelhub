<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobImage extends Model
{
    use HasFactory;

    protected $fillable = ['model_job_id', 'image_path'];

    public function job()
    {
        return $this->belongsTo(ModelJob::class);
    }
}
