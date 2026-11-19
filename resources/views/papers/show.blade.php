@php
    $steps = \App\Enums\PaperStatus::cases();
    $currentIndex = array_search($paper->status, $steps, true);
    $isEditorView = auth()->user()->can('viewConfidential', $paper);
    $selectedSectorIds = $paper->sectors->pluck('id')->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $paper->title }}</h2>
            <x-status-badge :status="$paper->status" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash />

            {{-- Workflow stepper --}}
            <ol class="flex flex-wrap gap-2 text-xs font-medium">
                @foreach ($steps as $i => $step)
                    <li class="rounded-full px-3 py-1 {{ $i <= $currentIndex ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                        {{ $i + 1 }}. {{ $step->label() }}
                    </li>
                @endforeach
            </ol>

            {{-- Details --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div><dt class="text-gray-500">Author</dt><dd class="font-medium">{{ $paper->author->name }}</dd></div>
                    <div><dt class="text-gray-500">Committee</dt><dd class="font-medium">{{ $paper->author->committee?->name ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Version</dt><dd class="font-medium">v{{ $paper->version }}</dd></div>
                    <div><dt class="text-gray-500">Reference</dt><dd class="font-medium font-mono">{{ $paper->reference ?? '—' }}</dd></div>
                </dl>

                <div>
                    <h3 class="font-semibold text-sm">Abstract</h3>
                    <p class="mt-1 text-sm text-gray-700 whitespace-pre-line">{{ $paper->abstract }}</p>
                </div>

                <div class="flex flex-wrap gap-1.5">
                    @foreach ($paper->sectors as $sector)
                        <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs text-indigo-700">{{ $sector->name }}@if ($sector->pivot->is_primary) &#9733;@endif</span>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2 border-t">
                    @if ($paper->hasFile())
                        @can('download', $paper)
                            <a href="{{ route('papers.download', $paper) }}" class="rounded-md bg-gray-800 px-3 py-1.5 text-sm text-white hover:bg-gray-700">
                                Download PDF ({{ \App\Support\FileSize::format((int) $paper->file_size) }})
                            </a>
                        @endcan
                    @else
                        <span class="text-sm text-gray-500">No manuscript uploaded yet.</span>
                    @endif

                    @can('update', $paper)
                        <a href="{{ route('papers.edit', $paper) }}" class="text-sm text-indigo-700 underline">Edit draft</a>
                    @endcan

                    @can('submit', $paper)
                        <form method="POST" action="{{ route('papers.submit', $paper) }}">
                            @csrf
                            <button class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-500">Submit for review</button>
                        </form>
                    @endcan

                    @can('delete', $paper)
                        <form method="POST" action="{{ route('papers.destroy', $paper) }}" onsubmit="return confirm('Delete this paper?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-sm text-red-600 underline">Delete</button>
                        </form>
                    @endcan

                    @if ($paper->isPublished())
                        <a href="{{ route('catalog.show', $paper) }}" class="text-sm text-indigo-700 underline">Public page</a>
                    @endif
                </div>
            </div>

            {{-- AUTHOR VIEW: anonymised reviewer feedback only --}}
            @if ($feedback && $feedback->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold">Reviewer feedback</h3>
                    <p class="text-xs text-gray-500 mb-3">Reviews are anonymous. Reviewer identities and internal editorial notes are never shown to authors.</p>
                    <div class="space-y-4">
                        @foreach ($feedback as $item)
                            <div class="rounded-md border p-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">{{ $item['label'] }}</span>
                                    <span class="text-gray-500">{{ \App\Models\Review::RECOMMENDATIONS[$item['recommendation']] ?? '—' }} &middot; score {{ $item['score'] ?? '—' }}/5</span>
                                </div>
                                <p class="mt-2 text-sm text-gray-700 whitespace-pre-line">{{ $item['comments'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- EDITOR VIEW: workflow, reviewers, classification, internal notes --}}
            @if ($isEditorView)
                @can('transition', $paper)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-semibold mb-3">Editorial actions</h3>
                        <div class="flex flex-wrap gap-4">
                            @foreach ($paper->status->allowedTransitions() as $target)
                                <form method="POST" action="{{ route('papers.transition', $paper) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="to" value="{{ $target->value }}">
                                    <input type="text" name="note" placeholder="Note (optional)" class="rounded-md border-gray-300 text-sm">
                                    <button class="rounded-md px-3 py-1.5 text-sm text-white {{ $target === \App\Enums\PaperStatus::Draft ? 'bg-amber-600 hover:bg-amber-500' : 'bg-indigo-600 hover:bg-indigo-500' }}">
                                        {{ match ($target) {
                                            \App\Enums\PaperStatus::Draft => 'Return to author',
                                            \App\Enums\PaperStatus::Approved => 'Approve',
                                            \App\Enums\PaperStatus::Published => 'Publish',
                                            default => $target->label(),
                                        } }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endcan

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-3">Reviewers &amp; reviews (confidential)</h3>
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase text-gray-500">
                            <tr><th class="py-1">Reviewer</th><th>Status</th><th>Recommendation</th><th>Score</th><th></th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($paper->reviews as $review)
                                <tr class="align-top">
                                    <td class="py-2">{{ $review->reviewer->name }}<div class="text-xs text-gray-500">{{ $review->reviewer->organization }}</div></td>
                                    <td>{{ ucfirst($review->status) }}</td>
                                    <td>{{ \App\Models\Review::RECOMMENDATIONS[$review->recommendation] ?? '—' }}</td>
                                    <td>{{ $review->score ?? '—' }}</td>
                                    <td class="text-right">
                                        @if ($review->isPending())
                                            @can('assignReviewers', $paper)
                                                <form method="POST" action="{{ route('papers.reviewers.destroy', [$paper, $review]) }}">
                                                    @csrf @method('DELETE')
                                                    <button class="text-xs text-red-600 underline">Remove</button>
                                                </form>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                                @if ($review->isCompleted())
                                    <tr>
                                        <td colspan="5" class="pb-3 text-xs text-gray-600">
                                            <strong>To author:</strong> {{ $review->comments_to_author }}<br>
                                            <strong>Confidential:</strong> {{ $review->confidential_notes ?: '—' }}
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr><td colspan="5" class="py-3 text-gray-500">No reviewers assigned.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    @can('assignReviewers', $paper)
                        <form method="POST" action="{{ route('papers.reviewers.store', $paper) }}" class="mt-4 flex items-center gap-2">
                            @csrf
                            <select name="reviewer_id" class="rounded-md border-gray-300 text-sm" required>
                                <option value="">Assign a reviewer…</option>
                                @foreach ($availableReviewers as $reviewer)
                                    <option value="{{ $reviewer->id }}">{{ $reviewer->name }}@if ($reviewer->organization) — {{ $reviewer->organization }} @endif</option>
                                @endforeach
                            </select>
                            <button class="rounded-md bg-gray-800 px-3 py-1.5 text-sm text-white">Assign</button>
                        </form>
                    @endcan
                </div>

                @can('classify', $paper)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-semibold mb-3">Multi-disciplinary indexing</h3>
                        <form method="POST" action="{{ route('papers.sectors.update', $paper) }}">
                            @csrf @method('PUT')
                            <div class="grid gap-1 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($allSectors as $sector)
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="sectors[]" value="{{ $sector->id }}" class="rounded border-gray-300 text-indigo-600" @checked(in_array($sector->id, $selectedSectorIds, true))>
                                        {{ $sector->name }}
                                    </label>
                                @endforeach
                            </div>
                            <button class="mt-3 rounded-md bg-gray-800 px-3 py-1.5 text-sm text-white">Save indexing</button>
                        </form>
                    </div>
                @endcan

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-3">Internal editorial notes</h3>
                    <ul class="space-y-2 text-sm">
                        @forelse ($paper->notes as $note)
                            <li class="rounded-md bg-gray-50 p-3">{{ $note->body }}<div class="text-xs text-gray-500 mt-1">{{ $note->user->name }} &middot; {{ $note->created_at->diffForHumans() }}</div></li>
                        @empty
                            <li class="text-gray-500">No notes yet.</li>
                        @endforelse
                    </ul>
                    @can('addNote', $paper)
                        <form method="POST" action="{{ route('papers.notes.store', $paper) }}" class="mt-3 flex gap-2">
                            @csrf
                            <input type="text" name="body" required placeholder="Add an internal note (not visible to authors)" class="flex-1 rounded-md border-gray-300 text-sm">
                            <button class="rounded-md bg-gray-800 px-3 py-1.5 text-sm text-white">Add</button>
                        </form>
                    @endcan
                </div>
            @endif

            {{-- History (not shown to reviewers) --}}
            @unless (auth()->user()->isReviewer())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-3">Status history</h3>
                    <ol class="space-y-1.5 text-sm">
                        @foreach ($paper->histories as $h)
                            <li>
                                <span class="text-gray-500">{{ $h->created_at->format('M j, Y') }}</span> &middot;
                                {{ $h->from_status?->label() ?? 'New' }} &rarr; <strong>{{ $h->to_status->label() }}</strong>
                                @if ($h->user) <span class="text-gray-500">by {{ $h->user->name }}</span> @endif
                                @if ($h->note) <span class="text-gray-500">— {{ $h->note }}</span> @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endunless
        </div>
    </div>
</x-app-layout>
