<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Technical sectors</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <form method="POST" action="{{ route('sectors.store') }}" class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600">Name</label>
                    <input name="name" required class="mt-1 rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div class="flex-1 min-w-[12rem]">
                    <label class="block text-xs font-medium text-gray-600">Description</label>
                    <input name="description" class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <button class="rounded-md bg-indigo-600 px-3 py-2 text-sm text-white">Add sector</button>
            </form>

            <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-2">Sector</th><th class="px-4 py-2">Slug</th><th class="px-4 py-2">Papers</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($sectors as $sector)
                            <tr>
                                <td class="px-4 py-2">{{ $sector->name }}</td>
                                <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $sector->slug }}</td>
                                <td class="px-4 py-2">{{ $sector->papers_count }}</td>
                                <td class="px-4 py-2 text-right">
                                    <form method="POST" action="{{ route('sectors.destroy', $sector) }}" onsubmit="return confirm('Delete sector?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-600 underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
