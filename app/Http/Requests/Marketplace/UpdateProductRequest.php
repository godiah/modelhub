<?php

namespace App\Http\Requests\Marketplace;

use App\Enums\GeometryType;
use App\Enums\UvLayout;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    use ProductRules;

    public function authorize(): bool
    {
        return $this->user()->can('edit', $this->route('product'));
    }

    public function rules(): array
    {
        return [
            'title' => $this->titleRule(),
            'category_id' => $this->categoryRule(),
            'description' => ['nullable', 'string', 'max:20000'],
            'tags' => ['nullable', 'string', 'max:500'],
            'price' => $this->priceRule(),
            'geometry_type' => ['nullable', Rule::enum(GeometryType::class)],
            'polygons' => ['nullable', 'integer', 'min:0', 'max:2000000000'],
            'vertices' => ['nullable', 'integer', 'min:0', 'max:2000000000'],
            'uv_layout' => ['nullable', Rule::enum(UvLayout::class)],
            'render_engine' => ['nullable', 'string', 'max:80'],
            'software' => ['nullable', 'array', 'max:20'],
            'software.*' => ['integer', Rule::exists('software', 'id')->where('is_active', true)],
            ...collect(array_keys(Product::FEATURES))->mapWithKeys(fn ($field) => [$field => ['sometimes', 'boolean']])->all(),
        ];
    }

    /** Tags arrive as one comma-separated field; keep them tidy: lowercase, unique, no empties, capped. */
    public function tagList(): array
    {
        return collect(explode(',', (string) $this->input('tags')))
            ->map(fn ($tag) => mb_strtolower(trim($tag)))
            ->filter(fn ($tag) => $tag !== '' && mb_strlen($tag) <= 30)
            ->unique()
            ->take(config('marketplace.max_tags'))
            ->values()
            ->all();
    }
}
