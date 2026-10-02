<?php

namespace App\Support\Settings;

/**
 * The money rules a Super admin sets for the platform: the commission on jobs and on model sales, the lowest price a model may be
 * sold at, and how long sale earnings are held before they can be withdrawn. Defaults are the decided launch values (see MARKETPLACE.md).
 */
final class FeeSettings
{
    /** @return array<string, array{type: string, default: mixed, label: string, help?: string, min?: int|float, max?: int|float, unit?: string}> */
    public static function definitions(): array
    {
        return [
            'fees.jobs_percent' => ['type' => 'float', 'default' => 10.0, 'min' => 0, 'max' => 50, 'label' => 'Commission on jobs', 'help' => 'Taken from the agreed amount when a client hires a freelancer. Each hire keeps the rate it was made at, so changing this only affects new offers.', 'unit' => '%'],
            'fees.models_percent' => ['type' => 'float', 'default' => 15.0, 'min' => 0, 'max' => 50, 'label' => 'Commission on model sales', 'help' => 'Taken from each model sale. A seller can have their own rate (set on their store page); every sale keeps the rate it was made at.', 'unit' => '%'],
            'fees.min_model_price' => ['type' => 'int', 'default' => 100, 'min' => 0, 'max' => 100000, 'label' => 'Lowest price for a paid model', 'help' => 'Stops prices so low that payment charges swallow the sale. Free models are always allowed.', 'unit' => 'KES'],
            'fees.sale_hold_days' => ['type' => 'int', 'default' => 7, 'min' => 0, 'max' => 60, 'label' => 'Hold sale earnings for', 'help' => 'Earnings from a model sale become withdrawable after this many days, which is also the window in which a broken or misdescribed file can be refunded. 0 means no hold.', 'unit' => 'days'],
        ];
    }

    /** @return list<array{title: string, description: string, keys: list<string>}> */
    public static function sections(): array
    {
        return [
            ['title' => 'Commission', 'description' => 'What the platform keeps from each transaction.', 'keys' => ['fees.jobs_percent', 'fees.models_percent']],
            ['title' => 'Model sales', 'description' => 'How low a model can be priced, and when sellers can withdraw what they earn.', 'keys' => ['fees.min_model_price', 'fees.sale_hold_days']],
        ];
    }
}
