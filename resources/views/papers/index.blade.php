<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Papers</h2>
            @can('create', \App\Models\Paper::class)
                <a href="{{ route('papers.create') }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-500">New submission</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="GET" class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-600">Search</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600">Status</label>
                    <select name="status" class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">Any</option>
                        @foreach (\App\Enums\PaperStatus::cases() as $s)
                            <option value="{{ $s->value }}" @selected($status === $s)>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-md bg-gray-800 px-3 py-2 text-sm text-white">Filter</button>
            </form>

            <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-2">Title</th>
                            <th class="px-4 py-2">Author</th>
                            <th class="px-4 py-2">Sectors</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($papers as $paper)
                            <tr>
                                <td class="px-4 py-2"><a href="{{ route('papers.show', $paper) }}" class="text-indigo-700 hover:underline">{{ $paper->title }}</a></td>
                                <td class="px-4 py-2">{{ $paper->author->name }}</td>
                                <td class="px-4 py-2 text-xs text-gray-600">{{ $paper->sectors->pluck('name')->join(', ') }}</td>
                                <td class="px-4 py-2"><x-status-badge :status="$paper->status" /></td>
                                <td class="px-4 py-2 text-gray-500">{{ $paper->updated_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No papers to show.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $papers->links() }}
        </div>
    </div>
</x-app-layout>
