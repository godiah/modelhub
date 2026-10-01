<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Http\Request;

/** Every store, whatever its status (the applications queue stays the work list). Permission: view sellers. */
class AdminStoreController extends Controller
{
    public function index(Request $request)
    {
        $statuses = ['all' => 'All'] + collect(SellerStatus::cases())->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)])->all();
        $status = array_key_exists($request->query('status'), $statuses) ? $request->query('status') : 'all';
        $term = trim((string) $request->query('q'));

        $stores = SellerProfile::with('user:id,name,avatar,suspended_at')
            ->withCount(['products', 'products as published_count' => fn ($query) => $query->published()])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q->where('display_name', 'like', "%{$term}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%"))))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $byStatus = SellerProfile::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.stores.index', [
            'stores' => $stores, 'statuses' => $statuses, 'status' => $status, 'term' => $term,
            'counts' => ['all' => (int) $byStatus->sum()] + collect(SellerStatus::cases())->mapWithKeys(fn ($case) => [$case->value => (int) ($byStatus[$case->value] ?? 0)])->all(),
        ]);
    }

    public function show(SellerProfile $seller)
    {
        $seller->load(['user:id,name,avatar,suspended_at', 'reviewer:id,name']);

        return view('admin.stores.show', [
            'store' => $seller,
            'models' => Product::with('images')->where('user_id', $seller->user_id)->withCount(['wishlistItems', 'reviews'])->latest()->limit(12)->get(),
        ]);
    }
}
