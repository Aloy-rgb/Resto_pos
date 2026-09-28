<x-app-layout>
    <div class="max-w-7xl mx-auto p-6 space-y-6">
        <header><p class="text-sm uppercase tracking-widest text-gray-500">{{ auth()->user()->role === 'admin' ? 'Access control' : 'Team management' }}</p><h1 class="text-3xl font-bold">Staff</h1><p class="text-gray-600">{{ auth()->user()->role === 'admin' ? 'Create and manage staff login accounts and access roles.' : 'Review staff names, salary, schedules, and performance notes.' }}</p></header>
        @if (session('success'))<div class="rounded bg-green-100 p-3 text-green-800">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="rounded bg-red-100 p-3 text-red-800">{{ $errors->first() }}</div>@endif

        @if (auth()->user()->role === 'admin')
            <section class="rounded-lg bg-white p-5 shadow"><h2 class="mb-4 text-xl font-semibold">Create staff account</h2><form method="POST" action="{{ route('staff.store') }}" class="grid gap-3 md:grid-cols-6">@csrf
                <input class="rounded border-gray-300" name="name" required placeholder="Full name"><input class="rounded border-gray-300" name="username" required minlength="3" placeholder="Username"><input class="rounded border-gray-300" type="email" name="email" required placeholder="Contact email"><input class="rounded border-gray-300" type="password" name="password" required minlength="8" placeholder="Password"><select class="rounded border-gray-300" name="role"><option value="cashier">Cashier</option><option value="chef">Chef</option><option value="manager">Manager</option></select><button class="rounded bg-gray-900 px-4 py-2 text-white">Create account</button>
            </form></section>
        @endif

        <section class="space-y-4">@forelse ($staff as $member)
            <article class="rounded-lg bg-white p-5 shadow"><div class="mb-4 flex items-center justify-between"><div><h2 class="text-lg font-semibold">{{ $member->name }}</h2><p class="text-sm text-gray-500">{{ $member->email }} · {{ ucfirst($member->role) }}</p></div>
                @if (auth()->user()->role === 'admin')<form method="POST" action="{{ route('staff.destroy', $member) }}" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')<button class="text-red-700">Delete account</button></form>@endif
            </div>
            <form method="POST" action="{{ route('staff.update', $member) }}" class="grid gap-3 md:grid-cols-3">@csrf @method('PUT')
                @if (auth()->user()->role === 'admin')
                    <label class="text-sm">Full name<input class="mt-1 w-full rounded border-gray-300" name="name" value="{{ $member->name }}" required></label>
                    <label class="text-sm">Username<input class="mt-1 w-full rounded border-gray-300" name="username" value="{{ $member->username }}" required minlength="3"></label>
                    <label class="text-sm">Contact email<input class="mt-1 w-full rounded border-gray-300" type="email" name="email" value="{{ $member->email }}" required></label>
                    <label class="text-sm">Role<select class="mt-1 w-full rounded border-gray-300" name="role"><option value="cashier" @selected($member->role === 'cashier')>Cashier</option><option value="chef" @selected($member->role === 'chef')>Chef</option><option value="manager" @selected($member->role === 'manager')>Manager</option></select></label>
                    <label class="text-sm">New password (leave blank to keep)<input class="mt-1 w-full rounded border-gray-300" type="password" name="password" minlength="8"></label>
                @endif
                <label class="text-sm">Salary<input class="mt-1 w-full rounded border-gray-300" type="number" min="0" step="0.01" name="salary" value="{{ $member->salary }}"></label>
                <label class="text-sm">Scheduling<textarea class="mt-1 w-full rounded border-gray-300" name="schedule" rows="2">{{ $member->schedule }}</textarea></label>
                <label class="text-sm">Performance<textarea class="mt-1 w-full rounded border-gray-300" name="performance" rows="2">{{ $member->performance }}</textarea></label>
                <div class="md:col-span-3"><button class="rounded bg-emerald-800 px-4 py-2 text-white">Save staff details</button></div>
            </form></article>
        @empty<p class="rounded bg-white p-6 text-gray-600">No staff accounts yet.</p>@endforelse</section>
    </div>
</x-app-layout>
