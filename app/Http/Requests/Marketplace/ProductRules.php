<?php

namespace App\Http\Requests\Marketplace;

use App\Models\Category;
use Closure;

/** Rules shared by creating and editing a listing. */
trait ProductRules
{
    protected function titleRule(): array
    {
        return ['required', 'string', 'min:5', 'max:150'];
    }

    /** A listing sits in a category that has no sub-categories (a sub-category, or a top-level one without any). */
    protected function categoryRule(): array
    {
        return ['required', 'integer', function (string $attribute, mixed $value, Closure $fail) {
            $category = Category::active()->withCount('children')->find($value);

            if (! $category || $category->children_count > 0) {
                $fail('Choose the most specific category that fits.');
            }
        }];
    }

    /** Price in whole KES; 0 means free (the form sends 0 when "free" is ticked, so a blank price is a mistake). */
    protected function priceRule(): array
    {
        return ['required', 'numeric', 'min:0', 'max:1000000'];
    }

    /** The price field in minor units. */
    public function priceMinor(): int
    {
        return (int) round(((float) $this->input('price', 0)) * 100);
    }
}
