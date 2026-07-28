@extends('layouts.tif')

@section('title', 'タイムテーブル - TIF Planner')

@section('content')
<div class="space-y-4 w-full" x-data="{ q: '' }">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">タイムテーブル</h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                現在時刻: {{ $now->format('Y-m-d H:i') }}
                @if ($showNowLine)
                    <span class="text-red-300">（NOW 赤線表示中）</span>
                @endif
            </p>
        </div>
    </div>

    <x-event-clock-preview :now="$now" :day="$day" />

    <div class="flex flex-wrap gap-2">
        @foreach ($days as $date => $label)
            <a href="{{ route('timetable.index', ['day' => $date]) }}"
               class="rounded-lg px-3 py-1.5 text-xs sm:text-sm font-medium {{ $day === $date ? 'bg-pink-500 text-white shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <input type="search" x-model="q" placeholder="アーティスト名でハイライト..."
           class="w-full max-w-md rounded-lg bg-slate-800 border border-slate-600 px-3 py-2 text-sm">

    <section class="w-full" x-effect="$root.querySelectorAll('[data-artist]').forEach(el => {
        const match = !q || el.dataset.artist.toLowerCase().includes(q.toLowerCase());
        el.classList.toggle('opacity-35', !match);
        el.classList.toggle('ring-2', match && q);
        el.classList.toggle('ring-yellow-300', match && q);
    })">
        <x-timetable-grid
            :grid="$grid"
            :show-now-line="$showNowLine"
        />
    </section>
</div>
@endsection
