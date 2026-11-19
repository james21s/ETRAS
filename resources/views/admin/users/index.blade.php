<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">User administration</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="GET" class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3 items-end">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Name or email" class="rounded-md border-gray-300 shadow-sm text-sm">
                <select name="role" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">Any role</option>
                    @foreach (\App\Enums\UserRole::cases() as $r)
                        <option value="{{ $r->value }}" @selected($roleFilter === $r)>{{ $r->label() }}</option>
                    @endforeach
                </select>
                <button class="rounded-md bg-gray-800 px-3 py-2 text-sm text-white">Filter</button>
            </form>

            <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-2">User</th><th class="px-4 py-2">Role &amp; committee</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($users as $u)
                            <tr>
                                <td class="px-4 py-2">{{ $u->name }}<div class="text-xs text-gray-500">{{ $u->email }}</div></td>
                                <td class="px-4 py-2">
                                    <form method="POST" action="{{ route('admin.users.update', $u) }}" class="flex flex-wrap gap-2">
                                        @csrf @method('PUT')
                                        <select name="role" class="rounded-md border-gray-300 text-sm">
                                            @foreach (\App\Enums\UserRole::cases() as $r)
                                                <option value="{{ $r->value }}" @selected($u->role === $r)>{{ $r->label() }}</option>
                                            @endforeach
                                        </select>
                                        <select name="committee_id" class="rounded-md border-gray-300 text-sm">
                                            <option value="">No committee</option>
                                            @foreach ($committees as $c)
                                                <option value="{{ $c->id }}" @selected($u->committee_id === $c->id)>{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="rounded-md bg-indigo-600 px-3 py-1 text-xs text-white">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
