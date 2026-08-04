<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait FiltersContacts
{
    private function applyContactFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('keyword'), function (Builder $query) use ($request): void {
                $keyword = '%'.$request->string('keyword')->toString().'%';

                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('code', 'like', $keyword)
                        ->orWhere('name', 'like', $keyword);
                });
            })
            ->when(
                $request->input('is_active') !== null && $request->input('is_active') !== '',
                fn (Builder $query) => $query->where('is_active', $request->boolean('is_active')),
            );
    }

    private function toggleContactStatus(Model $contact): bool
    {
        $contact->update(['is_active' => ! $contact->is_active]);

        return $contact->is_active;
    }
}
