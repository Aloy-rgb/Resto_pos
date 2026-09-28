<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('staff.index', [
            'staff' => User::whereIn('role', ['cashier', 'chef', 'manager'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'min:3', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:cashier,chef,manager'],
        ]);
        $data['email_verified_at'] = now();
        User::create($data);

        return back()->with('success', 'Staff account created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['cashier', 'chef', 'manager'], true), 404);
        if ($request->user()->role === 'manager') {
            $user->update($request->validate([
                'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'schedule' => ['nullable', 'string', 'max:5000'],
                'performance' => ['nullable', 'string', 'max:5000'],
            ]));
        } else {
            abort_unless($request->user()->role === 'admin', 403);
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'alpha_dash', 'min:3', 'max:50', 'unique:users,username,'.$user->id],
                'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
                'role' => ['required', 'in:cashier,chef,manager'],
                'password' => ['nullable', 'string', 'min:8'],
                'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
                'schedule' => ['nullable', 'string', 'max:5000'],
                'performance' => ['nullable', 'string', 'max:5000'],
            ]);
            if (blank($data['password'] ?? null)) unset($data['password']);
            $user->update($data);
        }

        return back()->with('success', 'Staff record updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        abort_unless(in_array($user->role, ['cashier', 'chef', 'manager'], true), 404);
        abort_if($user->is($request->user()), 422, 'You cannot delete your own account.');
        abort_if($user->orders()->exists(), 422, 'This staff member has order history and cannot be deleted.');
        $user->delete();

        return back()->with('success', 'Staff account deleted.');
    }
}
