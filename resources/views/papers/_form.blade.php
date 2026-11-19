@php
    $selected = collect(old('sectors', isset($paper) ? $paper->sectors->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();
@endphp

<div>
    <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
    <input id="title" name="title" type="text" value="{{ old('title', $paper->title ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
</div>

<div>
    <label for="abstract" class="block text-sm font-medium text-gray-700">Abstract</label>
    <textarea id="abstract" name="abstract" rows="6" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('abstract', $paper->abstract ?? '') }}</textarea>
</div>

<div>
    <span class="block text-sm font-medium text-gray-700">Technical sectors <span class="text-gray-400">(select every sector the paper belongs to; the first is the primary)</span></span>
    <div class="mt-2 grid gap-1 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($sectors as $sector)
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="sectors[]" value="{{ $sector->id }}" class="rounded border-gray-300 text-indigo-600" @checked(in_array($sector->id, $selected, true))>
                {{ $sector->name }}
            </label>
        @endforeach
    </div>
</div>

<div>
    <label for="manuscript" class="block text-sm font-medium text-gray-700">Manuscript (PDF, max {{ config('standardspress.max_upload_kb') / 1024 }} MB)</label>
    <input id="manuscript" name="manuscript" type="file" accept="application/pdf" class="mt-1 block w-full text-sm">
    @isset($paper)
        @if ($paper->hasFile())
            <p class="mt-1 text-xs text-gray-500">Current file: {{ $paper->file_original_name }} ({{ \App\Support\FileSize::format((int) $paper->file_size) }}). Uploading a new file replaces it.</p>
        @endif
    @endisset
</div>
