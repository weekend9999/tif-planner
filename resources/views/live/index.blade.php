@extends('layouts.tif')

@section('title', '当日ビュー - TIF Planner')

@section('content')
<div class="space-y-6" x-data="{ now: new Date() }" x-init="setInterval(() => now = new Date(), 60000)">
    <div class="flex flex-wrap gap-2">
        @foreach ($days as $date => $label)
            <a href="{{ route('live.index', ['day' => $date]) }}"
               class="rounded-lg px-4 py-2 text-sm {{ $day === $date ? 'bg-red-500 text-white' : 'bg-slate-800 text-slate-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <section class="rounded-2xl border border-red-500/40 bg-red-500/10 p-5">
        <h1 class="text-xl font-bold text-red-300">🔴 NOW PLAYING</h1>
        <p class="text-sm text-slate-400 mt-1">{{ $now->format('H:i') }} 現在</p>
        <div class="mt-4 space-y-2">
            @forelse ($nowPlaying as $performance)
                <div class="rounded-lg bg-slate-900/80 px-4 py-3">
                    <div class="font-semibold">{{ $performance->stage->name }}</div>
                    <div>{{ $performance->artist_name }}</div>
                    <div class="text-sm text-slate-400">{{ $performance->startsAtFormatted() }}-{{ $performance->endsAtFormatted() }}</div>
                </div>
            @empty
                <p class="text-slate-400">現在出演中の登録データはありません</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-pink-500/30 bg-slate-900 p-5">
        <h2 class="text-lg font-bold">⏭ 次の推し</h2>
        <div class="mt-3 space-y-2">
            @forelse ($upcoming as $performance)
                <div class="rounded-lg border border-slate-700 px-4 py-3">
                    <div class="font-semibold">{{ $performance->artist_name }}</div>
                    <div class="text-sm text-slate-400">
                        {{ $performance->startsAtFormatted() }} / {{ $performance->stage->name }}
                    </div>
                </div>
            @empty
                <p class="text-slate-500">この日の推しはこれ以上ありません</p>
            @endforelse
        </div>
    </section>

    @if (!empty($analysis['legs']))
        <section class="space-y-3">
            <h2 class="text-lg font-bold">🚨 出発アラート</h2>
            @foreach ($analysis['legs'] as $leg)
                <div class="rounded-xl border p-4 {{ $leg->status->colorClass() }}">
                    <div class="font-semibold">{{ $leg->status->icon() }} {{ $leg->from->artist_name }} → {{ $leg->to->artist_name }}</div>
                    <p class="text-sm mt-2">{{ $leg->message }}</p>
                </div>
            @endforeach
        </section>
    @endif
</div>
@endsection
