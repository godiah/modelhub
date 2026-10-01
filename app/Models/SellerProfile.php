<?php

namespace App\Models;

use App\Enums\SellerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SellerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'status', 'display_name', 'slug', 'tagline', 'logo_path', 'website_url', 'name_changed_at', 'bio', 'focus', 'portfolio_url', 'terms_accepted_at',
        'submitted_at', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'status' => SellerStatus::class,
        'name_changed_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /** The storefront address is made once from the store name and then stays put, even if the name is edited later. */
    protected static function booted(): void
    {
        static::creating(function (SellerProfile $seller) {
            if (blank($seller->slug)) {
                $base = Str::slug($seller->display_name) ?: 'seller';
                $slug = $base;

                for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
                    $slug = $base.'-'.$i;
                }

                $seller->slug = $slug;
            }
        });
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk(config('marketplace.images_disk'))->url($this->logo_path) : null;
    }

    /** "K3S": the first letters of the first two words of the store name, for when there is no logo. */
    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->display_name)))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    }

    /** When the store name may next be changed, or null if it can be changed now. */
    public function nextNameChangeAt(): ?Carbon
    {
        $next = $this->name_changed_at?->copy()->addDays(config('marketplace.name_change_days'));

        return $next && $next->isFuture() ? $next : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === SellerStatus::Approved;
    }

    /** A rejected applicant may improve their application and send it again; a suspended seller may not. */
    public function canReapply(): bool
    {
        return $this->status === SellerStatus::Rejected;
    }
}
