<?php

namespace App\Support\Staff;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Sorting for the staff lists, from `?sort=column&dir=asc|desc`. Only the columns a list declares can be sorted, so the query
 * string can never name an arbitrary column.
 *
 *   [$sort, $dir] = ListSort::resolve($request, ['name', 'created_at'], default: 'created_at');
 *   ListSort::apply($query, $sort, $dir, ['name' => 'name', 'joined' => 'created_at']);
 */
final class ListSort
{
    /**
     * @param  list<string>  $allowed  the sort keys this list offers
     * @param  list<string>  $descFirst  keys that start high-to-low (dates, counts, amounts); the rest start A to Z
     * @return array{0: string, 1: string} the chosen key and direction
     */
    public static function resolve(Request $request, array $allowed, string $default, array $descFirst = []): array
    {
        $sort = in_array($request->query('sort'), $allowed, true) ? $request->query('sort') : $default;
        $dir = in_array($request->query('dir'), ['asc', 'desc'], true) ? $request->query('dir') : (in_array($sort, $descFirst, true) ? 'desc' : 'asc');

        return [$sort, $dir];
    }

    /**
     * @param  array<string, string|\Closure>  $columns  sort key => the column (or withCount alias) to order by, or a closure
     *                                                   (Builder, direction) for anything special such as empty values last
     */
    public static function apply(Builder $query, string $sort, string $dir, array $columns): Builder
    {
        $column = $columns[$sort];

        $column instanceof \Closure ? $column($query, $dir) : $query->orderBy($column, $dir);

        return $query->orderBy($query->getModel()->getQualifiedKeyName(), $dir);
    }
}
