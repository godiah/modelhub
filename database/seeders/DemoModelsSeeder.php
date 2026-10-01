<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Models\SellerProfile;
use App\Models\Software;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Local demo data for the models marketplace: a few approved sellers and about fifty real 3D models, each with
 * real renders, real polygon counts and descriptions, spread over the catalogue's categories and statuses
 * (published, in review, needs changes, draft) so every marketplace page has something to show.
 *
 *   php artisan db:seed --class=DemoModelsSeeder
 *
 * The models are Poly Haven's CC0 (public domain) library, read from its public API, and the previews are
 * Poly Haven's own renders. No model file is real: the private files are sparse placeholders of a believable
 * size, so downloads give empty data. Safe to re-run (matched by slug, images and files not recreated), uses
 *
 * @demo.test sellers and a "-demo" slug suffix so it can be found and removed, never runs in production, and
 * is not part of DatabaseSeeder. Without a network it does nothing.
 */
class DemoModelsSeeder extends Seeder
{
    private const API = 'https://api.polyhaven.com/assets?t=models';

    private const CDN = 'https://cdn.polyhaven.com/asset_img';

    private const TARGET = 54;

    private const PER_CATEGORY = 3;

    private bool $offline = false;

    /** @var list<array{name: string, email: string, store: string, bio: string, focus: string}> */
    private const SELLERS = [
        ['name' => 'Amani Otieno', 'email' => 'demo.seller1@demo.test', 'store' => 'Maker Atelier 3D', 'bio' => 'Furniture and interior props for architectural visualisation, built with clean topology and real-world scale. Eight years in Nairobi studios.', 'focus' => 'Furniture, decor and interior props as Blender, FBX and glTF.'],
        ['name' => 'Baraka Njuguna', 'email' => 'demo.seller2@demo.test', 'store' => 'Polygon Workshop', 'bio' => 'Game-ready props and environment assets. I optimise for mobile and web, with hand-checked UVs and tidy hierarchies.', 'focus' => 'Low-poly props and environment assets for games and AR.'],
        ['name' => 'Chebet Kiplagat', 'email' => 'demo.seller3@demo.test', 'store' => 'Grain & Mesh', 'bio' => 'Scanned and photogrammetry-based assets: rocks, wood, weathered objects. Everything is delivered with calibrated PBR textures.', 'focus' => 'Scanned assets, natural materials and weathered props.'],
        ['name' => 'Dalila Mwenda', 'email' => 'demo.seller4@demo.test', 'store' => 'Studio Kijani', 'bio' => 'Plants, trees and organic scenery for outdoor visualisation, plus the odd industrial piece when a scene needs it.', 'focus' => 'Plants, trees, outdoor and industrial assets.'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoModelsSeeder is for local environments only.');

            return;
        }

        $assets = $this->catalogue();

        if ($assets === null) {
            $this->command?->warn('Could not reach Poly Haven, so no demo models were created. Try again with a network connection.');

            return;
        }

        $sellers = $this->sellers();
        $software = Software::whereIn('name', ['Blender', 'Autodesk 3ds Max', 'Autodesk Maya', 'Cinema 4D', 'Unity', 'Unreal Engine'])->pluck('id', 'name');
        $count = 0;

        foreach ($this->select($assets) as $index => [$id, $asset, $slug]) {
            $category = Category::where('slug', $slug)->first();

            if (! $category) {
                continue;
            }

            $this->product($index, $id, $asset, $category, $sellers[$index % count($sellers)], $software);
            $count++;
        }

        $this->command?->info("Demo models ready: {$count} (CC0 models and renders from Poly Haven).");
    }

    /** @return array<string, array<string, mixed>>|null */
    private function catalogue(): ?array
    {
        try {
            $response = Http::timeout(30)->withHeaders(['User-Agent' => 'ModelHub-demo-seeder'])->get(self::API);
        } catch (ConnectionException) {
            return null;
        }

        return $response->successful() && is_array($response->json()) ? $response->json() : null;
    }

    /**
     * About TARGET models spread over our categories (at most PER_CATEGORY each), chosen deterministically.
     *
     * @return list<array{0: string, 1: array<string, mixed>, 2: string}>
     */
    private function select(array $assets): array
    {
        $byCategory = [];

        ksort($assets);
        foreach ($assets as $id => $asset) {
            if (blank($asset['description'] ?? null) || ($asset['polycount'] ?? 0) < 100) {
                continue;
            }
            $byCategory[$this->category($asset)][] = [$id, $asset];
        }

        ksort($byCategory);
        $picked = [];

        // Round-robin so every category gets its first model before any gets a third
        for ($round = 0; $round < self::PER_CATEGORY && count($picked) < self::TARGET; $round++) {
            foreach ($byCategory as $slug => $items) {
                if (isset($items[$round]) && count($picked) < self::TARGET) {
                    $picked[] = [$items[$round][0], $items[$round][1], $slug];
                }
            }
        }

        // A stable shuffle, so sellers and statuses are mixed through the list
        usort($picked, fn ($a, $b) => crc32($a[0]) <=> crc32($b[0]));

        return $picked;
    }

    /** Poly Haven's categories and tags, mapped onto our category slugs. */
    private function category(array $asset): string
    {
        $categories = $asset['categories'] ?? [];
        $tags = array_map('strtolower', $asset['tags'] ?? []);
        $has = fn (string ...$words) => (bool) array_intersect($words, $categories) || (bool) array_intersect($words, $tags);

        return match (true) {
            $has('seating') && $has('sofa', 'couch') => 'furniture-sofa',
            $has('seating', 'chair', 'stool') => 'furniture-chair',
            $has('table', 'desk') => 'furniture-table',
            $has('lighting', 'lamp') => 'furniture-lamp',
            $has('appliances') => 'furniture-appliance',
            $has('dishes', 'tableware', 'cutlery') => 'furniture-tableware',
            $has('shelves', 'cabinet') => 'furniture-kitchen-cabinet',
            $has('food') => 'food-other-food',
            $has('trees') => 'plants-conifer',
            $has('plants') => 'plants-pot-plant',
            $has('electronics') => 'electronics-other-electronics',
            $has('tools') => 'industrial-tool',
            $has('industrial', 'containers') => 'industrial-industrial-part',
            $has('buildings', 'structures') => 'exteriors-house',
            $has('rocks', 'nature') => 'scanned-models',
            $has('decorative', 'decor') => 'architectural-decoration',
            $has('vehicles') => 'vehicles-vehicle-part',
            default => 'household-other-household',
        };
    }

    /** @return list<User> */
    private function sellers(): array
    {
        return array_map(function (array $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password'), 'email_verified_at' => now()],
            );

            SellerProfile::updateOrCreate(['user_id' => $user->id], [
                'display_name' => $data['store'], 'bio' => $data['bio'], 'focus' => $data['focus'], 'status' => 'approved',
                'terms_accepted_at' => now(), 'submitted_at' => now()->subDays(30), 'reviewed_at' => now()->subDays(29),
            ]);

            return $user;
        }, self::SELLERS);
    }

    private function product(int $index, string $id, array $asset, Category $category, User $seller, $software): void
    {
        $status = match (true) {
            $index % 19 === 4 => ProductStatus::InReview,
            $index % 23 === 7 => ProductStatus::Rejected,
            $index % 29 === 11 => ProductStatus::Draft,
            default => ProductStatus::Published,
        };

        $polygons = (int) ($asset['polycount'] ?? 5000);
        $published = $status === ProductStatus::Published;
        $tags = array_slice(array_map('strtolower', $asset['tags'] ?? []), 0, 8);
        $hash = crc32($id);
        $prices = [0, 0, 30000, 50000, 80000, 120000, 250000, 450000];

        $product = Product::withTrashed()->updateOrCreate(['slug' => Str::slug($asset['name']).'-demo'], [
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'title' => $asset['name'].' - '.Str::title(implode(' ', array_slice($tags, 0, 2))).' PBR',
            'description' => $this->description($asset, $polygons),
            'tags' => $tags,
            'status' => $status,
            'price_minor' => $prices[$hash % count($prices)] * ($polygons > 20000 ? 2 : 1),
            'currency' => config('marketplace.currency'),
            'license' => 'standard',
            'geometry_type' => 'polygon_mesh',
            'polygons' => $polygons,
            'vertices' => (int) round($polygons * 0.52),
            'uv_layout' => 'non_overlapping',
            'render_engine' => 'Cycles 4.2',
            'is_rigged' => in_array('rigged', $asset['categories'] ?? [], true),
            'is_animated' => false,
            'is_low_poly' => $polygons < 5000,
            'is_pbr' => true,
            'has_textures' => true,
            'has_materials' => true,
            'is_uv_mapped' => true,
            'is_print_ready' => $hash % 11 === 0,
            'is_vr_ready' => $polygons < 15000 && $hash % 3 === 0,
            'submitted_at' => $status === ProductStatus::Draft ? null : now()->subDays(40 - $index % 30),
            'reviewed_at' => in_array($status, [ProductStatus::Published, ProductStatus::Rejected], true) ? now()->subDays(38 - $index % 30) : null,
            'published_at' => $published ? now()->subDays(1 + $hash % 50)->subMinutes($index * 7) : null,
            'review_notes' => $status === ProductStatus::Rejected ? 'Please add a wireframe preview and list the exact texture resolutions in the description.' : null,
        ]);

        $product->software()->sync(array_filter([
            $software['Blender'] ?? null,
            $hash % 2 === 0 ? ($software['Autodesk 3ds Max'] ?? null) : ($software['Autodesk Maya'] ?? null),
            $hash % 4 === 0 ? ($software['Unreal Engine'] ?? null) : null,
        ]));

        $this->images($product, $id);
        $this->files($product, $asset);
    }

    private function description(array $asset, int $polygons): string
    {
        $resolution = (int) (($asset['max_resolution'][0] ?? 2048) / 1024);
        $size = collect($asset['dimensions'] ?? [])->map(fn ($mm) => number_format($mm / 10, 1))->implode(' × ');
        $author = collect($asset['authors'] ?? [])->keys()->first();

        return trim($asset['description'])."\n\n**What is included**\n\n"
            ."- Blender source file, FBX and glTF (GLB) exports\n"
            ."- {$resolution}K PBR textures (base colour, normal, roughness and metallic maps)\n"
            .($size ? "- Real-world scale: {$size} cm\n" : '')
            .'- '.number_format($polygons)." triangles with clean, non-overlapping UVs\n\n"
            .'*Demo listing based on the CC0 (public domain) model'.($author ? " by {$author}" : '').' from Poly Haven.*';
    }

    /** Cover (Poly Haven's thumbnail) and a clay render, converted to JPEG when GD is available. */
    private function images(Product $product, string $id): void
    {
        if ($product->images()->exists() || $this->offline) {
            return;
        }

        $disk = config('marketplace.images_disk');
        $sources = [
            self::CDN."/thumbs/{$id}.png?width=720&height=720",
            self::CDN."/renders/{$id}/clay.png?width=900",
        ];

        foreach ($sources as $position => $url) {
            $path = "product-images/{$product->id}/demo-{$position}.jpg";
            $bytes = $this->download($url);

            if ($bytes === null) {
                continue;
            }

            Storage::disk($disk)->put($path, $this->asJpeg($bytes));
            ProductImage::create(['product_id' => $product->id, 'disk' => $disk, 'path' => $path, 'position' => $position + 1]);
        }
    }

    /** Placeholder model files: sparse, so they take no disk space but show a believable size. */
    private function files(Product $product, array $asset): void
    {
        $disk = config('marketplace.files_disk');

        if ($product->files()->exists() || config("filesystems.disks.{$disk}.driver") !== 'local') {
            return;
        }

        $base = match (true) {
            ($asset['max_resolution'][0] ?? 2048) >= 8192 => 48,
            ($asset['max_resolution'][0] ?? 2048) >= 4096 => 34,
            ($asset['max_resolution'][0] ?? 2048) >= 2048 => 18,
            default => 8,
        } * 1048576;

        foreach ([['blend', 'native', 1.0], ['fbx', 'exchange', 0.55], ['glb', 'exchange', 0.45]] as [$extension, $kind, $factor]) {
            $path = "product-files/{$product->id}/demo-".Str::before($product->slug, '-demo').".{$extension}";
            $size = (int) ($base * $factor);
            $absolute = Storage::disk($disk)->path($path);

            @mkdir(dirname($absolute), 0775, true);
            $handle = fopen($absolute, 'wb');
            fseek($handle, $size - 1);
            fwrite($handle, "\0");
            fclose($handle);

            ProductFile::create([
                'product_id' => $product->id, 'disk' => $disk, 'path' => $path, 'original_name' => Str::before($product->slug, '-demo').".{$extension}",
                'extension' => $extension, 'kind' => $kind, 'size_bytes' => $size, 'checksum' => null,
            ]);
        }
    }

    private function download(string $url): ?string
    {
        try {
            $response = Http::timeout(30)->withHeaders(['User-Agent' => 'ModelHub-demo-seeder'])->get($url);
        } catch (ConnectionException) {
            $this->offline = true;

            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    private function asJpeg(string $bytes): string
    {
        if (! function_exists('imagecreatefromstring') || ! ($image = @imagecreatefromstring($bytes))) {
            return $bytes;
        }

        // Flatten any transparency onto white so the JPEG has a clean background
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, 84);

        return (string) ob_get_clean();
    }
}
