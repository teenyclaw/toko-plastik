<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Session;

class CurrentOutletService
{
    public const SESSION_KEY = 'current_outlet_id';

    /** @return Collection<int, Outlet> */
    public function accessibleOutlets(User $user): Collection
    {
        if ($user->isAdmin()) {
            return Outlet::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        return $user->outlets()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function canAccess(User $user, Outlet|int $outlet): bool
    {
        $outletId = $outlet instanceof Outlet ? $outlet->id : $outlet;

        if ($user->isAdmin()) {
            return Outlet::query()->where('id', $outletId)->where('is_active', true)->exists();
        }

        return $user->outlets()
            ->where('outlets.id', $outletId)
            ->where('is_active', true)
            ->exists();
    }

    public function current(): Outlet
    {
        $user = auth()->user();

        if (! $user) {
            return Outlet::query()->where('is_active', true)->orderBy('id')->firstOrFail();
        }

        $sessionId = Session::get(self::SESSION_KEY);

        if ($sessionId && $this->canAccess($user, (int) $sessionId)) {
            return Outlet::query()->findOrFail((int) $sessionId);
        }

        return $this->setDefaultForUser($user);
    }

    public function currentId(): int
    {
        return $this->current()->id;
    }

    public function switch(User $user, int $outletId): Outlet
    {
        if (! $this->canAccess($user, $outletId)) {
            throw new \RuntimeException('Anda tidak memiliki akses ke cabang ini.');
        }

        Session::put(self::SESSION_KEY, $outletId);

        return Outlet::query()->findOrFail($outletId);
    }

    public function setDefaultForUser(User $user): Outlet
    {
        $outlet = $this->accessibleOutlets($user)->first();

        if (! $outlet) {
            throw new \RuntimeException('Tidak ada cabang yang dapat diakses untuk akun ini.');
        }

        Session::put(self::SESSION_KEY, $outlet->id);

        return $outlet;
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function assertOrderBelongsToCurrentOutlet(int $orderOutletId): void
    {
        abort_unless($orderOutletId === $this->currentId(), 403, 'Order bukan milik cabang aktif.');
    }
}
