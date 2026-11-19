<x-public-layout title="Published Papers · StandardsPress">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Published standards &amp; research</h1>
        <p class="text-sm text-gray-600 mt-1">Search across technical sectors and committees. Papers are indexed under every sector they belong to.</p>
    </div>

    <form method="GET" action="{{ route('catalog.index') }}" class="bg-white rounded-lg shadow-sm p-4 mb-6 grid gap-4 md:grid-cols-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700" for="q">Search</label>
            <input id="q" type="text" name="q" value="{{ request('q') }}" placeholder="Title, abstract or reference" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700" for="committee">Committee</label>
            <select id="committee" name="committee" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">All committees</option>
                @foreach ($committees as $committee)
                    <option value="{{ $committee->slug }}" @selected(request('committee') === $committee->slug)>{{ $committee->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Filter</button>
            <a href="{{ route('catalog.index') }}" class="ms-3 text-sm text-gray-500 underline">Reset</a>
        </div>

        <div class="md:col-span-4">
            <span class="block text-sm font-medium text-gray-700 mb-1">Sectors (any of)</span>
            <div class="flex flex-wrap gap-x-4 gap-y-1">
                @foreach ($sectors as $sector)
                    <label class="inline-flex items-center gap-1.5 text-sm">
                        <input type="checkbox" name="sectors[]" value="{{ $sector->slug }}" class="rounded border-gray-300 text-indigo-600" @checked(in_array($sector->slug, $selectedSectors, true))>
                        {{ $sector->name }}
                    </label>
                @endforeach
            </div>
        </div>
    </form>

    <div class="space-y-4">
        @forelse ($papers as $paper)
            <article class="bg-white rounded-lg shadow-sm p-5">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="text-lg font-semibold">
                        <a href="{{ route('catalog.show', $paper) }}" class="text-indigo-700 hover:underline">{{ $paper->title }}</a>
                    </h2>
                    <span class="shrink-0 text-xs font-mono text-gray-500">{{ $paper->reference }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    {{ $paper->author->name }}
                    @if ($paper->author->committee) &middot; {{ $paper->author->committee->name }} @endif
                    &middot; {{ $paper->published_at?->format('M j, Y') }}
                </p>
                <p class="mt-2 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($paper->abstract, 220) }}</p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($paper->sectors as $sector)
                        <a href="{{ route('catalog.index', ['sectors' => [$sector->slug]]) }}" class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs text-indigo-700 hover:bg-indigo-100">{{ $sector->name }}</a>
                    @endforeach
                </div>
            </article>
        @empty
            <p class="text-gray-500">No published papers match your filters.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $papers->links() }}</div>
</x-public-layout>
