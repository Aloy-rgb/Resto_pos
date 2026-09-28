<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-8 p-6">
        <header><p class="text-sm uppercase tracking-widest text-gray-500">Restaurant setup</p><h1 class="text-3xl font-bold">Menu and tables</h1><p class="text-gray-600">Manage categories, menu availability, prices, and dining tables.</p></header>
        @if (session('success'))<div class="rounded bg-green-100 p-3 text-green-800">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="rounded bg-red-100 p-3 text-red-800">{{ $errors->first() }}</div>@endif

        <section class="space-y-4 rounded-lg bg-white p-5 shadow">
            <h2 class="text-xl font-semibold">Categories</h2>
            <form method="POST" action="{{ route('catalog.categories.store') }}" class="grid gap-3 md:grid-cols-3">@csrf
                <input class="rounded border-gray-300" name="name" placeholder="Category name" required>
                <input class="rounded border-gray-300" name="description" placeholder="Description">
                <button class="rounded bg-gray-900 px-4 py-2 text-white">Add category</button>
            </form>
            @foreach ($categories as $category)
                <div class="grid gap-3 border-t pt-3 md:grid-cols-[1fr_auto]">
                    <form method="POST" action="{{ route('catalog.categories.update', $category) }}" class="grid gap-3 md:grid-cols-3">@csrf @method('PUT')
                        <input class="rounded border-gray-300" name="name" value="{{ $category->name }}" required>
                        <input class="rounded border-gray-300" name="description" value="{{ $category->description }}">
                        <button class="rounded border px-3 py-2">Save ({{ $category->menu_items_count }} items)</button>
                    </form>
                    <form method="POST" action="{{ route('catalog.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')<button class="text-red-700">Delete</button></form>
                </div>
            @endforeach
        </section>

        <section class="space-y-4 rounded-lg bg-white p-5 shadow">
            <h2 class="text-xl font-semibold">Menu items</h2>
            <form method="POST" action="{{ route('catalog.menu-items.store') }}" class="grid gap-3 md:grid-cols-6">@csrf
                <input class="rounded border-gray-300" name="name" placeholder="Item name" required>
                <select class="rounded border-gray-300" name="category_id" required>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                <input class="rounded border-gray-300" name="description" placeholder="Description">
                <input class="rounded border-gray-300" type="number" name="current_price" min="0" step="0.01" placeholder="Price" required>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_available" value="1" checked> Available</label>
                <button class="rounded bg-gray-900 px-4 py-2 text-white">Add item</button>
            </form>
            @foreach ($menuItems as $item)
                <div class="grid gap-3 border-t pt-3 md:grid-cols-[1fr_auto]">
                    <form method="POST" action="{{ route('catalog.menu-items.update', $item) }}" class="grid gap-3 md:grid-cols-6">@csrf @method('PUT')
                        <input class="rounded border-gray-300" name="name" value="{{ $item->name }}" required>
                        <select class="rounded border-gray-300" name="category_id">@foreach ($categories as $category)<option value="{{ $category->id }}" @selected($item->category_id === $category->id)>{{ $category->name }}</option>@endforeach</select>
                        <input class="rounded border-gray-300" name="description" value="{{ $item->description }}">
                        <input class="rounded border-gray-300" type="number" name="current_price" min="0" step="0.01" value="{{ $item->current_price }}" required>
                        <label class="flex items-center gap-2"><input type="checkbox" name="is_available" value="1" @checked($item->is_available)> Available</label>
                        <button class="rounded border px-3 py-2">Save</button>
                    </form>
                    <form method="POST" action="{{ route('catalog.menu-items.destroy', $item) }}" onsubmit="return confirm('Delete this menu item?')">@csrf @method('DELETE')<button class="text-red-700">Delete</button></form>
                </div>
            @endforeach
        </section>

        <section class="space-y-4 rounded-lg bg-white p-5 shadow">
            <h2 class="text-xl font-semibold">Dining tables</h2>
            <form method="POST" action="{{ route('catalog.tables.store') }}" class="grid gap-3 md:grid-cols-3">@csrf
                <input class="rounded border-gray-300" type="number" name="table_number" min="1" placeholder="Table number" required>
                <input class="rounded border-gray-300" type="number" name="capacity" min="1" placeholder="Seats" required>
                <button class="rounded bg-gray-900 px-4 py-2 text-white">Add table</button>
            </form>
            @foreach ($tables as $table)
                <div class="grid gap-3 border-t pt-3 md:grid-cols-[1fr_auto]">
                    <form method="POST" action="{{ route('catalog.tables.update', $table) }}" class="grid gap-3 md:grid-cols-3">@csrf @method('PUT')
                        <label>Table number<input class="ml-2 rounded border-gray-300" type="number" name="table_number" value="{{ $table->table_number }}" @readonly($table->status === 'occupied') required></label>
                        <label>Seats<input class="ml-2 rounded border-gray-300" type="number" name="capacity" min="1" value="{{ $table->capacity }}" required></label>
                        <button class="rounded border px-3 py-2">Save · {{ ucfirst($table->status) }}</button>
                    </form>
                    <form method="POST" action="{{ route('catalog.tables.destroy', $table) }}" onsubmit="return confirm('Delete this table?')">@csrf @method('DELETE')<button class="text-red-700" @disabled($table->status === 'occupied')>Delete</button></form>
                </div>
            @endforeach
        </section>
    </div>
</x-app-layout>
