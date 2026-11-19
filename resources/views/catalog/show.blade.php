<x-public-layout :title="$paper->title.' · StandardsPress'">
    <a href="{{ route('catalog.index') }}" class="text-sm text-indigo-700 hover:underline">&larr; Back to catalog</a>

    <div class="grid gap-6 lg:grid-cols-3 mt-4">
        <article class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
            <p class="text-xs font-mono text-gray-500">{{ $paper->reference }}</p>
            <h1 class="text-2xl font-semibold mt-1">{{ $paper->title }}</h1>
            <p class="text-sm text-gray-500 mt-2">
                {{ $paper->author->name }}
                @if ($paper->author->organization) ({{ $paper->author->organization }}) @endif
                @if ($paper->author->committee) &middot; {{ $paper->author->committee->name }} @endif
                &middot; Published {{ $paper->published_at?->format('F j, Y') }} &middot; v{{ $paper->version }}
            </p>

            <div class="mt-4 flex flex-wrap gap-1.5">
                @foreach ($paper->sectors as $sector)
                    <a href="{{ route('catalog.index', ['sectors' => [$sector->slug]]) }}" class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs text-indigo-700 hover:bg-indigo-100">
                        {{ $sector->name }}@if ($sector->pivot->is_primary) &#9733;@endif
                    </a>
                @endforeach
            </div>

            <h2 class="mt-6 font-semibold">Abstract</h2>
            <p class="mt-2 text-sm leading-relaxed text-gray-700 whitespace-pre-line">{{ $paper->abstract }}</p>

            <div class="mt-6">
                @auth
                    <a href="{{ route('papers.download', $paper) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                        Download PDF @if ($paper->file_size) ({{ \App\Support\FileSize::format($paper->file_size) }}) @endif
                    </a>
                @else
                    <p class="text-sm text-gray-600">
                        <a href="{{ route('login') }}" class="text-indigo-700 underline">Log in</a> or
                        <a href="{{ route('register') }}" class="text-indigo-700 underline">register</a> to download the full PDF.
                    </p>
                @endauth
            </div>
        </article>

        <aside class="bg-white rounded-lg shadow-sm p-6 h-fit">
            <h2 class="font-semibold">Related papers</h2>
            <p class="text-xs text-gray-500 mb-3">Ranked by number of shared sectors.</p>
            <ul class="space-y-3">
                @forelse ($related as $item)
                    <li>
                        <a href="{{ route('catalog.show', $item) }}" class="text-sm text-indigo-700 hover:underline">{{ $item->title }}</a>
                        <div class="text-xs text-gray-500">{{ $item->shared_sectors_count }} shared sector{{ $item->shared_sectors_count === 1 ? '' : 's' }}</div>
                    </li>
                @empty
                    <li class="text-sm text-gray-500">No related papers yet.</li>
                @endforelse
            </ul>
        </aside>
    </div>
</x-public-layout>
