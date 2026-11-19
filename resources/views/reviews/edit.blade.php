<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Review: {{ $review->paper->title }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            <div class="bg-white rounded-lg shadow-sm p-6 text-sm space-y-2">
                <p><span class="text-gray-500">Author:</span> {{ $review->paper->author->name }}</p>
                <p><span class="text-gray-500">Sectors:</span> {{ $review->paper->sectors->pluck('name')->join(', ') }}</p>
                <p class="whitespace-pre-line text-gray-700">{{ $review->paper->abstract }}</p>
                @can('download', $review->paper)
                    <a href="{{ route('papers.download', $review->paper) }}" class="inline-block rounded-md bg-gray-800 px-3 py-1.5 text-white">Download manuscript</a>
                @endcan
            </div>

            <form method="POST" action="{{ route('reviews.update', $review) }}" class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700" for="recommendation">Recommendation</label>
                    <select id="recommendation" name="recommendation" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">Select…</option>
                        @foreach (\App\Models\Review::RECOMMENDATIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('recommendation') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700" for="score">Overall score (1–5)</label>
                    <input id="score" type="number" name="score" min="1" max="5" required value="{{ old('score') }}" class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700" for="comments_to_author">Comments to the author <span class="text-gray-400">(shown anonymously)</span></label>
                    <textarea id="comments_to_author" name="comments_to_author" rows="6" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('comments_to_author') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700" for="confidential_notes">Confidential notes to the editor <span class="text-gray-400">(never shown to the author)</span></label>
                    <textarea id="confidential_notes" name="confidential_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('confidential_notes') }}</textarea>
                </div>

                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Submit review</button>
            </form>
        </div>
    </div>
</x-app-layout>
