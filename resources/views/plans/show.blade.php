@extends('layouts.tif')

@section('title', 'プラン結果 - TIF Planner')

@section('content')
<div class="space-y-6 max-w-none">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">{{ $context->name }}</h1>
            <p class="text-sm text-slate-400">移動モード: {{ $context->bufferMode->label() }}</p>
        </div>
        <a href="{{ route('plans.edit') }}" class="rounded-lg bg-pink-500 px-4 py-2 text-sm font-semibold">編集</a>
    </div>

    @if ($context->performances->isEmpty())
        <div class="rounded-xl border border-slate-700 bg-slate-900 p-6 text-center text-slate-400">
            推しがまだ登録されていません。<a href="{{ route('plans.edit') }}" class="text-pink-400 underline">追加する</a>
        </div>
    @else
        {{-- Day tabs --}}
        <div class="flex flex-wrap gap-2">
            @foreach ($days as $date => $label)
                <a href="{{ route('plans.show', ['day' => $date]) }}"
                   class="rounded-lg px-4 py-2 text-sm font-medium {{ $day === $date ? 'bg-pink-500 text-white shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                    {{ $label }}
                    @php $count = $dayCounts[$date] ?? 0; @endphp
                    @if ($count > 0)
                        <span class="ml-1 opacity-80">({{ $count }})</span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap gap-4 text-xs text-slate-400">
            <span class="inline-flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded border-2 border-emerald-500 bg-white"></span> 余裕あり</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded border-2 border-amber-500 bg-white"></span> ギリギリ</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block w-3 h-3 rounded border-2 border-red-600 bg-white"></span> 間に合わない</span>
            <span class="text-slate-500">未選択の出演は薄く表示</span>
            @if ($showNowLine)
                <span class="text-red-300">NOW 赤線: {{ $now->format('H:i') }}</span>
            @endif
        </div>

        <x-event-clock-preview :now="$now" :day="$day" />

        {{-- Timetable grid --}}
        <section class="w-full">
            <h2 class="font-semibold mb-2 text-slate-200 text-sm sm:text-base">{{ $grid['day_label'] }} — 推しハイライト</h2>
            <x-timetable-grid
                :grid="$grid"
                :selected-ids="$selectedIds"
                :block-annotations="$blockAnnotations"
                :show-now-line="$showNowLine"
            />
        </section>

        @if (!empty($analysis['overlapDetails']))
            <section class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-5">
                <h2 class="font-bold text-amber-300 mb-3">⚠️ 時間が重なる推しがあります</h2>
                @foreach ($analysis['overlapDetails'] as $overlap)
                    <div class="text-sm mb-3 last:mb-0">
                        <p class="text-amber-100 font-medium mb-1">
                            {{ $overlap['first']->artist_name }} ({{ $overlap['first']->startsAtFormatted() }} {{ $overlap['first']->stage->name }})
                            ↔
                            {{ $overlap['second']->artist_name }} ({{ $overlap['second']->startsAtFormatted() }} {{ $overlap['second']->stage->name }})
                            <span class="text-amber-200/80 font-normal">— {{ $overlap['overlap_minutes'] }} 分かぶり</span>
                        </p>
                    </div>
                @endforeach
                <p class="text-xs text-slate-400 mt-2">詳細はタイムテーブルのブロックをタップしてください</p>
            </section>
        @endif

        @if ($analysis['allFeasible'])
            <div class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-4 text-emerald-300 text-sm">
                ✅ 現在の組み合わせはすべて間に合う見込みです
            </div>
        @else
            <div class="rounded-xl border border-red-500/40 bg-red-500/10 p-4 text-red-300 text-sm">
                ❌ 間に合わない組み合わせがあります。推しを減らすか、途中退場を検討してください。
            </div>
        @endif
    @endif
</div>
@endsection
