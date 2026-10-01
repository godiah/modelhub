<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductFile extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'disk', 'path', 'original_name', 'extension', 'kind', 'size_bytes', 'checksum'];

    protected $casts = ['size_bytes' => 'integer'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Human size, e.g. "12.4 MB". */
    public function readableSize(): string
    {
        $bytes = $this->size_bytes;

        return match (true) {
            $bytes >= 1048576 => number_format($bytes / 1048576, 1).' MB',
            $bytes >= 1024 => number_format($bytes / 1024, 0).' KB',
            default => $bytes.' B',
        };
    }

    public function deleteFromStorage(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }
}
