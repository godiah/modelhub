<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SellerStatus;
use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Support\Staff\ListSort;
use App\Support\Staff\StaffAudit;
use Illuminate\Http\Request;

/** Every store, whatever its status (the applications queue stays the work list). Permission: view sellers. */
class AdminStoreController extends Controller
{
    /** Sortable columns: sort key => the column or count alias it orders by. */
    public const SORTS = ['applied' => 'created_at', 'name' => 'display_name', 'models' => 'published_count', 'rating' => 'rating_avg'];

    public function index(Request $request)
    {
        $statuses = ['all' => 'All'] + collect(SellerStatus::cases())->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)])->all();
        $status = array_key_exists($request->query('status'), $statuses) ? $request->query('status') : 'all';
        $term = trim((string) $request->query('q'));
        [$sort, $dir] = ListSort::resolve($request, array_keys(self::SORTS), default: 'applied', descFirst: ['applied', 'models', 'rating']);

        $stores = SellerProfile::with('user:id,name,avatar,suspended_at')
            ->withCount(['products', 'products as published_count' => fn ($query) => $query->published()])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q->where('display_name', 'like', "%{$term}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%"))))
            ->tap(fn ($query) => ListSort::apply($query, $sort, $dir, self::SORTS))
            ->paginate(12)
            ->withQueryString();

        $byStatus = SellerProfile::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.stores.index', [
            'stores' => $stores, 'statuses' => $statuses, 'status' => $status, 'term' => $term, 'sort' => $sort, 'dir' => $dir,
            'counts' => ['all' => (int) $byStatus->sum()] + collect(SellerStatus::cases())->mapWithKeys(fn ($case) => [$case->value => (int) ($byStatus[$case->value] ?? 0)])->all(),
        ]);
    }

    /** A Super admin gives one seller their own model-sales commission (or clears it to use the platform rate). */
    public function commission(Request $request, SellerProfile $seller)
    {
        $data = $request->validate(['commission_percent' => ['nullable', 'numeric', 'min:0', 'max:50', 'decimal:0,2']]);
        $new = ($data['commission_percent'] ?? null) === null ? null : round((float) $data['commission_percent'], 2);
        $old = $seller->commission_percent !== null ? (float) $seller->commission_percent : null;

        if ($new === $old) {
            return back()->with(FlashAlertHelper::success('Nothing changed'));
        }

        $seller->forceFill(['commission_percent' => $new])->save();
        StaffAudit::log('seller.commission-changed', "Set the commission of {$seller->display_name} to ".($new === null ? 'the platform rate' : $new.'%'), $seller, ['from' => $old, 'to' => $new]);

        return back()->with(FlashAlertHelper::success('Commission saved', $new === null ? 'This store now uses the platform rate.' : "New sales from {$seller->display_name} are charged {$new}%."));
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
