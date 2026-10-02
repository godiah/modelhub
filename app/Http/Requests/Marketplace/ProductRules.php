<?php

namespace App\Http\Requests\Marketplace;

use App\Models\Category;
use App\Support\Settings\FeePolicy;
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

    /** Price in whole KES (M-Pesa takes no cents); 0 means free (the form sends 0 when "free" is ticked, so a blank price is a mistake). */
    protected function priceRule(): array
    {
        return ['required', 'numeric', 'min:0', 'max:1000000', function (string $attribute, mixed $value, Closure $fail) {
            $minimum = FeePolicy::minModelPriceMinor() / 100;

            if ((float) $value != floor((float) $value)) {
                $fail('Prices are in whole shillings, because M-Pesa cannot take cents.');
            } elseif ((float) $value > 0 && (float) $value < $minimum) {
                $fail('A paid model must cost at least KES '.number_format($minimum).', or be free.');
            }
        }];
    }

    /**
     * The optional Extended licence price in whole KES: empty means only Standard is sold. It must meet the platform minimum and cost more
     * than Standard, and a free model cannot have one.
     */
    protected function extendedPriceRule(): array
    {
        return ['nullable', 'numeric', 'min:0', 'max:1000000', function (string $attribute, mixed $value, Closure $fail) {
            if ($value === null || $value === '' || (float) $value <= 0) {
                return;
            }

            $standard = (float) $this->input('price', 0);
            $minimum = FeePolicy::minModelPriceMinor() / 100;

            if ((float) $value != floor((float) $value)) {
                $fail('Prices are in whole shillings, because M-Pesa cannot take cents.');
            } elseif ($standard <= 0) {
                $fail('A free model cannot also be sold with an Extended licence.');
            } elseif ((float) $value < $minimum) {
                $fail('The Extended licence must cost at least KES '.number_format($minimum).'.');
            } elseif ((float) $value <= $standard) {
                $fail('The Extended licence must cost more than the Standard one.');
            }
        }];
    }

    /** The Extended licence price in minor units, or null when it is not sold (blank, zero, or the model is free). */
    public function extendedPriceMinor(): ?int
    {
        $price = (float) $this->input('extended_price', 0);

        return $price > 0 && $this->priceMinor() > 0 ? (int) round($price * 100) : null;
    }

    /** The price field in minor units. */
    public function priceMinor(): int
    {
        return (int) round(((float) $this->input('price', 0)) * 100);
    }
}
