<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()->orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['is_active' => true, 'role' => UserRole::Kasir]),
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['password'] = $request->input('password');

        User::query()->create($data);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        if ($user->id === auth()->id() && ! ($data['is_active'] ?? true)) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        if ($user->isOwner() && $data['role'] !== UserRole::Owner && ! $this->hasOtherOwner($user)) {
            return back()->with('error', 'Harus ada minimal satu owner aktif.');
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($user->isOwner() && ! $this->hasOtherOwner($user)) {
            return back()->with('error', 'Harus ada minimal satu owner.');
        }

        if ($user->sales()->exists() || $user->purchases()->exists()) {
            return back()->with('error', 'Pengguna punya riwayat transaksi. Nonaktifkan saja.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna dihapus.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $emailRule = ['required', 'email', 'max:255'];
        $emailRule[] = $user
            ? Rule::unique('users', 'email')->ignore($user->id)
            : Rule::unique('users', 'email');

        $passwordRule = $user
            ? ['nullable', 'confirmed', Password::defaults()]
            : ['required', 'confirmed', Password::defaults()];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRule,
            'password' => $passwordRule,
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }

    private function hasOtherOwner(User $except): bool
    {
        return User::query()
            ->where('role', UserRole::Owner)
            ->where('is_active', true)
            ->where('id', '!=', $except->id)
            ->exists();
    }
}
