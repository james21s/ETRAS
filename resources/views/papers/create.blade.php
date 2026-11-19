<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New submission</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash />
            <form method="POST" action="{{ route('papers.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                @csrf
                @include('papers._form')
                <div class="flex items-center gap-3">
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Save draft</button>
                    <a href="{{ route('papers.index') }}" class="text-sm text-gray-500 underline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
