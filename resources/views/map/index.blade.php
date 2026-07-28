@extends('layouts.tif')

@section('title', '会場マップ - TIF Planner')

@section('content')
<div class="space-y-6 max-w-none">
    <div>
        <h1 class="text-2xl font-bold">会場マップ</h1>
        <p class="text-sm text-slate-400 mt-1">TOKYO IDOL FESTIVAL 2026 公式会場マップ</p>
    </div>

    <div class="rounded-2xl border border-slate-700 bg-white overflow-hidden shadow-lg">
        <img src="{{ asset('images/tif2026-map.png') }}"
             alt="TOKYO IDOL FESTIVAL 2026 会場マップ"
             class="w-full h-auto block"
             loading="lazy">
    </div>

    @if ($performances->count() >= 2)
        <section class="rounded-xl border border-slate-700 bg-slate-900 p-5">
            <h2 class="font-semibold mb-3">推し間の移動ルート</h2>
            <ol class="space-y-2">
                @foreach ($performances as $index => $performance)
                    @if ($index > 0)
                        @php $prev = $performances[$index - 1]; @endphp
                        <li class="text-sm text-slate-300">
                            {{ $prev->stage->name }} → {{ $performance->stage->name }}
                            ({{ $prev->artist_name }} → {{ $performance->artist_name }})
                        </li>
                    @endif
                @endforeach
            </ol>
        </section>
    @endif
</div>
@endsection
