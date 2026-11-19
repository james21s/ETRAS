@php
    $maxCommittee = max(1, $report['committees']->max('published_papers_count') ?? 1);
    $maxMonth = max(1, max($report['published_by_month']) ?: 1);
    $maxSector = max(1, $report['sectors']->max('published_papers_count') ?? 1);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Executive oversight</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white rounded-lg shadow-sm p-5"><div class="text-3xl font-semibold">{{ $report['total_papers'] }}</div><div class="text-sm text-gray-500">Total papers</div></div>
                <div class="bg-white rounded-lg shadow-sm p-5"><div class="text-3xl font-semibold">{{ $report['avg_review_days'] ?? '—' }}<span class="text-base font-normal text-gray-500"> days</span></div><div class="text-sm text-gray-500">Avg. review turnaround</div></div>
                <div class="bg-white rounded-lg shadow-sm p-5"><div class="text-3xl font-semibold">{{ $report['avg_publication_days'] ?? '—' }}<span class="text-base font-normal text-gray-500"> days</span></div><div class="text-sm text-gray-500">Avg. submission → publication</div></div>
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <div class="text-3xl font-semibold">{{ $report['overdue_reviews'] }} <span class="text-base font-normal text-gray-500">/ {{ $report['stalled_papers'] }}</span></div>
                    <div class="text-sm text-gray-500">Overdue reviews / stalled papers</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold mb-3">Pipeline by status</h3>
                <div class="flex flex-wrap gap-3">
                    @foreach ($report['by_status'] as $status => $total)
                        <div class="rounded-md border px-4 py-2 text-center">
                            <div class="text-2xl font-semibold">{{ $total }}</div>
                            <x-status-badge :status="$status" />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-semibold">Institutional publishing footprint</h3>
                    <p class="text-xs text-gray-500 mb-3">Published output of each committee's authors (Committee → Users → Papers).</p>
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase text-gray-500"><tr><th class="py-1">Committee</th><th>Published</th><th>All papers</th></tr></thead>
                        <tbody class="divide-y">
                            @foreach ($report['committees'] as $committee)
                                <tr>
                                    <td class="py-1.5 pr-3">{{ $committee->name }}
                                        <div class="h-1.5 mt-1 rounded bg-indigo-500" style="width: {{ round($committee->published_papers_count / $maxCommittee * 100) }}%"></div>
                                    </td>
                                    <td>{{ $committee->published_papers_count }}</td>
                                    <td class="text-gray-500">{{ $committee->papers_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="font-semibold mb-3">Papers published per month (last 12)</h3>
                        <div class="flex items-end gap-1.5 h-32">
                            @foreach ($report['published_by_month'] as $month => $count)
                                <div class="flex-1 flex flex-col items-center justify-end h-full" title="{{ $month }}: {{ $count }}">
                                    <span class="text-[10px] text-gray-500">{{ $count }}</span>
                                    <div class="w-full rounded-t bg-indigo-500" style="height: {{ round($count / $maxMonth * 100) }}%"></div>
                                    <span class="text-[9px] text-gray-400 mt-1">{{ substr($month, 5) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="font-semibold mb-3">Published papers by sector</h3>
                        <ul class="space-y-1.5 text-sm">
                            @foreach ($report['sectors'] as $sector)
                                <li>
                                    <div class="flex justify-between"><span>{{ $sector->name }}</span><span class="text-gray-500">{{ $sector->published_papers_count }}</span></div>
                                    <div class="h-1.5 rounded bg-emerald-500" style="width: {{ round($sector->published_papers_count / $maxSector * 100) }}%"></div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
