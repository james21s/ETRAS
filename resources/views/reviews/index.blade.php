<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">My reviews</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />
            <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-2">Paper</th><th class="px-4 py-2">Sectors</th><th class="px-4 py-2">Assigned</th><th class="px-4 py-2">Status</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($reviews as $review)
                            <tr>
                                <td class="px-4 py-2">{{ $review->paper->title }}</td>
                                <td class="px-4 py-2 text-xs text-gray-600">{{ $review->paper->sectors->pluck('name')->join(', ') }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $review->assigned_at->format('M j, Y') }}</td>
                                <td class="px-4 py-2">{{ ucfirst($review->status) }}</td>
                                <td class="px-4 py-2 text-right">
                                    @can('update', $review)
                                        <a href="{{ route('reviews.edit', $review) }}" class="text-indigo-700 underline">Write review</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No reviews assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $reviews->links() }}
        </div>
    </div>
</x-app-layout>
