<?php

namespace App\Models;

use App\Enums\GeometryType;
use App\Enums\ProductStatus;
use App\Enums\UvLayout;
use App\Services\Marketplace\ProductRatingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A 3D model listing. In the interface it is a "model"; the class is Product so it does not collide with
 * Eloquent's Model or the existing ModelJob. Prices are stored in minor units with their currency.
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'category_id', 'title', 'slug', 'description', 'tags', 'status',
        'price_minor', 'currency', 'license',
        'geometry_type', 'polygons', 'vertices', 'uv_layout', 'render_engine',
        'is_rigged', 'is_animated', 'is_low_poly', 'is_pbr', 'has_textures', 'has_materials', 'is_uv_mapped', 'is_print_ready', 'is_vr_ready',
        'rating_avg', 'rating_count', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_notes', 'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'status' => ProductStatus::class,
        'geometry_type' => GeometryType::class,
        'uv_layout' => UvLayout::class,
        'price_minor' => 'integer',
        'is_rigged' => 'boolean',
        'is_animated' => 'boolean',
        'is_low_poly' => 'boolean',
        'is_pbr' => 'boolean',
        'has_textures' => 'boolean',
        'has_materials' => 'boolean',
        'is_uv_mapped' => 'boolean',
        'is_print_ready' => 'boolean',
        'is_vr_ready' => 'boolean',
        'rating_avg' => 'float',
        'rating_count' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /** The feature flags buyers can filter by, with their labels. */
    public const FEATURES = [
        'is_rigged' => 'Rigged',
        'is_animated' => 'Animated',
        'is_low_poly' => 'Low-poly',
        'is_pbr' => 'PBR',
        'has_textures' => 'Textures',
        'has_materials' => 'Materials',
        'is_uv_mapped' => 'UV mapped',
        'is_print_ready' => '3D print ready',
        'is_vr_ready' => 'VR / AR ready',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            $product->slug = $product->slug ?: Str::slug($product->title).'-'.Str::lower(Str::random(6));
        });

        // The store's rating counts reviews of published models only, so it follows a model going live, being
        // taken down, deleted or restored, whichever path caused it.
        $refreshStore = fn (Product $product) => app(ProductRatingService::class)->recomputeStore($product->user_id);
        static::saved(fn (Product $product) => $product->wasChanged('status') ? $refreshStore($product) : null);
        static::deleted($refreshStore);
        static::restored($refreshStore);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sellerProfile()
    {
        return $this->hasOne(SellerProfile::class, 'user_id', 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function files()
    {
        return $this->hasMany(ProductFile::class)->orderBy('kind')->orderBy('original_name');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    public function software()
    {
        return $this->belongsToMany(Software::class, 'product_software')->withPivot('version');
    }

    public function reviewer()
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    /** Live in the catalogue: published, and the seller is still approved (suspending a seller hides their models). */
    public function scopePublished($query)
    {
        return $query->where('products.status', ProductStatus::Published->value)
            ->whereExists(fn ($seller) => $seller->selectRaw('1')->from('seller_profiles')
                ->whereColumn('seller_profiles.user_id', 'products.user_id')
                ->where('seller_profiles.status', 'approved'));
    }

    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    /** Whether this member may write a review now: a verified buyer of a model that is not theirs, with no review yet. */
    public function canBeReviewedBy(?User $user): bool
    {
        return $user !== null
            && $user->id !== $this->user_id
            && $this->purchases()->completed()->where('user_id', $user->id)->exists()
            && ! $this->reviews()->where('user_id', $user->id)->exists();
    }

    /** The price in whole currency units (KES), for display. */
    public function price(): float
    {
        return $this->price_minor / 100;
    }

    public function isFree(): bool
    {
        return $this->price_minor === 0;
    }

    public function feature_labels(): array
    {
        return collect(self::FEATURES)->filter(fn ($label, $field) => $this->{$field})->values()->all();
    }

    /** What is missing before the listing can be sent for review, keyed by requirement (empty when it is ready). */
    public function readinessProblems(): array
    {
        $problems = [];
        $maxImages = config('marketplace.max_images');

        if (blank($this->title)) {
            $problems['title'] = 'Add a title.';
        }
        if (! $this->category_id) {
            $problems['category'] = 'Choose a category.';
        }
        if (blank($this->description) || mb_strlen(trim($this->description)) < 30) {
            $problems['description'] = 'Write a description (at least 30 characters).';
        }
        if ($this->images()->count() < 1) {
            $problems['images'] = 'Add at least one preview image.';
        }
        if ($this->images()->count() > $maxImages) {
            $problems['images'] = "Use at most {$maxImages} preview images.";
        }
        if ($this->files()->whereIn('kind', ['native', 'exchange', 'archive'])->count() < 1) {
            $problems['files'] = 'Upload at least one model file.';
        }

        return $problems;
    }

    /** The requirements for sending a listing for review, keyed like readinessProblems(), with their labels. */
    public static function requirements(): array
    {
        return [
            'title' => 'A title',
            'category' => 'A category',
            'description' => 'A description (30+ characters)',
            'images' => 'At least one preview image',
            'files' => 'At least one model file',
        ];
    }
}
