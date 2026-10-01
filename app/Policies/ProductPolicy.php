<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /** The seller who owns the listing, while they are still an approved seller. */
    public function manage(User $user, Product $product): bool
    {
        return $user->id === $product->user_id && $user->isApprovedSeller();
    }

    /** Editing details and files is only possible while the listing is a draft, needs changes or is unpublished. */
    public function edit(User $user, Product $product): bool
    {
        return $this->manage($user, $product) && $product->status->isEditable();
    }

    /** Reviewers can open any listing and its files. */
    public function review(User $user, Product $product): bool
    {
        return $user->can('review models');
    }
}
