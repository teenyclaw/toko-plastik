<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DiningTable;
use App\Models\Outlet;

trait ResolvesCustomerContext
{
    protected function resolveOutlet(string $slug): Outlet
    {
        return Outlet::where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    protected function resolveTable(Outlet $outlet, string $token): DiningTable
    {
        return DiningTable::where('outlet_id', $outlet->id)
            ->where('token', $token)
            ->where('is_active', true)
            ->firstOrFail();
    }

    protected function tableSessionKey(Outlet $outlet): string
    {
        return 'dining_table_' . $outlet->id;
    }

    protected function bindTableSession(Outlet $outlet, DiningTable $table): void
    {
        session([$this->tableSessionKey($outlet) => $table->id]);
    }

    protected function clearTableSession(Outlet $outlet): void
    {
        session()->forget($this->tableSessionKey($outlet));
    }

    protected function activeTable(Outlet $outlet): ?DiningTable
    {
        $id = session($this->tableSessionKey($outlet));

        if (! $id) {
            return null;
        }

        return DiningTable::where('outlet_id', $outlet->id)
            ->where('is_active', true)
            ->find($id);
    }
}
