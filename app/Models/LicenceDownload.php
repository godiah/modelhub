<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A file a licence holder downloaded. */
class LicenceDownload extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['issued_licence_id', 'product_file_id', 'file_name', 'ip_address'];

    public function licence()
    {
        return $this->belongsTo(IssuedLicence::class, 'issued_licence_id');
    }
}
