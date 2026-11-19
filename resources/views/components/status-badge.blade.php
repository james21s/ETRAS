@props(['status'])
@php
    $s = $status instanceof \App\Enums\PaperStatus ? $status : \App\Enums\PaperStatus::from($status);
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$s->badgeClasses()]) }}>{{ $s->label() }}</span>
