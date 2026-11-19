<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">API tokens</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />

            @if (session('new_token'))
                <div class="rounded-md bg-yellow-50 border border-yellow-200 p-4 text-sm">
                    <p class="font-medium text-yellow-900">Your new token (copy it now):</p>
                    <code class="block mt-2 break-all rounded bg-white p-2 border">{{ session('new_token') }}</code>
                </div>
            @endif

            <form method="POST" action="{{ route('tokens.store') }}" class="bg-white rounded-lg shadow-sm p-4 flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600">Token name (e.g. the aggregator it is for)</label>
                    <input name="name" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <button class="rounded-md bg-indigo-600 px-3 py-2 text-sm text-white">Create token</button>
            </form>

            <div class="bg-white rounded-lg shadow-sm p-4 text-sm">
                <h3 class="font-semibold mb-2">Using the REST feed</h3>
                <pre class="overflow-x-auto rounded bg-gray-900 p-3 text-xs text-gray-100">curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     "{{ url('/api/v1/papers') }}?q=bridge&amp;sector[]=civil-infrastructure"</pre>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-2">Name</th><th class="px-4 py-2">Created</th><th class="px-4 py-2">Last used</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($tokens as $token)
                            <tr>
                                <td class="px-4 py-2">{{ $token->name }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $token->created_at->format('M j, Y') }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $token->last_used_at?->diffForHumans() ?? 'never' }}</td>
                                <td class="px-4 py-2 text-right">
                                    <form method="POST" action="{{ route('tokens.destroy', $token->id) }}">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-600 underline">Revoke</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No tokens yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
