<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Welcome, {{ auth()->user()->name }}
            <span class="text-sm font-normal text-gray-500">({{ auth()->user()->role->label() }})</span>
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            @if ($role === 'author')
                <div class="grid gap-4 sm:grid-cols-4">
                    @foreach ($counts as $status => $total)
                        <a href="{{ route('papers.index', ['status' => $status]) }}" class="bg-white rounded-lg shadow-sm p-4 hover:shadow">
                            <div class="text-3xl font-semibold">{{ $total }}</div>
                            <x-status-badge :status="$status" class="mt-1" />
                        </a>
                    @endforeach
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold">Recent manuscripts</h3>
                        <a href="{{ route('papers.create') }}" class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-500">New submission</a>
                    </div>
                    <ul class="divide-y">
                        @forelse ($recent as $paper)
                            <li class="py-2 flex items-center justify-between">
                                <a href="{{ route('papers.show', $paper) }}" class="text-indigo-700 hover:underline">{{ $paper->title }}</a>
                                <x-status-badge :status="$paper->status" />
                            </li>
                        @empty
                            <li class="py-2 text-sm text-gray-500">You have not started a manuscript yet.</li>
                        @endforelse
                    </ul>
                </div>

            @elseif ($role === 'reviewer')
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-semibold mb-1">Reviews awaiting you ({{ $pending->count() }})</h3>
                    <p class="text-xs text-gray-500 mb-3">{{ $completedCount }} review(s) completed so far.</p>
                    <ul class="divide-y">
                        @forelse ($pending as $review)
                            <li class="py-2 flex items-center justify-between">
                                <span>{{ $review->paper->title }}
                                    <span class="text-xs text-gray-500">assigned {{ $review->assigned_at->diffForHumans() }}</span></span>
                                <a href="{{ route('reviews.edit', $review) }}" class="text-sm text-indigo-700 underline">Review</a>
                            </li>
                        @empty
                            <li class="py-2 text-sm text-gray-500">Nothing to review right now.</li>
                        @endforelse
                    </ul>
                </div>

            @elseif ($role === 'editor')
                @php
                    $queues = [
                        'Needs reviewers' => $needsReviewers,
                        'Awaiting reviews' => $awaitingReviews,
                        'Ready to approve' => $readyToApprove,
                        'Ready to publish' => $readyToPublish,
                    ];
                @endphp
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($queues as $label => $papers)
                        <div class="bg-white rounded-lg shadow-sm p-5">
                            <h3 class="font-semibold mb-2">{{ $label }} <span class="text-gray-400 font-normal">({{ $papers->count() }})</span></h3>
                            <ul class="divide-y text-sm">
                                @forelse ($papers as $paper)
                                    <li class="py-1.5">
                                        <a href="{{ route('papers.show', $paper) }}" class="text-indigo-700 hover:underline">{{ $paper->title }}</a>
                                        <span class="text-xs text-gray-500">by {{ $paper->author->name }}</span>
                                    </li>
                                @empty
                                    <li class="py-1.5 text-gray-500">Queue is empty.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endforeach
                </div>

            @elseif ($role === 'executive')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="bg-white rounded-lg shadow-sm p-5"><div class="text-3xl font-semibold">{{ $published }}</div><div class="text-sm text-gray-500">Published papers</div></div>
                    <div class="bg-white rounded-lg shadow-sm p-5"><div class="text-3xl font-semibold">{{ $inPipeline }}</div><div class="text-sm text-gray-500">In review / approved pipeline</div></div>
                </div>
                <a href="{{ route('oversight') }}" class="inline-block rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-500">Open full oversight dashboard</a>

            @else
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-semibold mb-3">Latest published papers</h3>
                    <ul class="divide-y">
                        @foreach ($latest as $paper)
                            <li class="py-2">
                                <a href="{{ route('catalog.show', $paper) }}" class="text-indigo-700 hover:underline">{{ $paper->title }}</a>
                                <div class="text-xs text-gray-500">{{ $paper->sectors->pluck('name')->join(', ') }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
