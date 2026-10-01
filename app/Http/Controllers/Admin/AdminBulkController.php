<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Services\Admin\BulkActionService;
use App\Support\Staff\BulkActions;
use Illuminate\Http\Request;

/** The one endpoint behind every list's bulk bar: run an action over the ticked items, then report what was done and what was skipped. */
class AdminBulkController extends Controller
{
    public function __invoke(Request $request, BulkActionService $bulk, string $action)
    {
        $definition = BulkActions::get($action) ?? abort(404);
        abort_unless(BulkActions::allows($request->user(), $action), 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:'.BulkActions::MAX],
            'ids.*' => ['required', 'distinct', $action === 'notification.read' ? 'string' : 'integer'],
            'reason' => [$definition['reason'] ? 'required' : 'nullable', 'string', 'min:5', 'max:500'],
        ], ['ids.required' => 'Tick at least one item first.', 'ids.max' => 'Choose at most '.BulkActions::MAX.' items at a time.']);

        $result = $bulk->run($request->user(), $action, $data['ids'], isset($data['reason']) ? trim($data['reason']) : null);
        $done = count($result['done']);
        $skipped = count($result['skipped']);

        return back()->with('bulk_result', $result)->with($done > 0
            ? FlashAlertHelper::success(ucfirst(BulkActions::count($definition['noun'], $done).' '.$definition['past']), $skipped ? $skipped.' skipped. See the details below.' : null)
            : FlashAlertHelper::error('Nothing was changed', 'Every selected item was skipped. See the details below.'));
    }
}
