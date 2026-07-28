@props(['now', 'day'])

@if (config('tif.allow_now_override'))
    <section class="rounded-xl border border-dashed border-amber-500/40 bg-amber-500/5 p-4 text-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h2 class="font-semibold text-amber-200">⏱ 時刻プレビュー（テスト用）</h2>
            <span class="text-xs text-slate-400">
                表示中: <strong class="text-amber-100">{{ $now->format('Y-m-d H:i') }}</strong>
                @if (app(\App\Services\EventClockService::class)->isPreviewing())
                    <span class="text-amber-300">（シミュレーション）</span>
                @else
                    <span>（現在時刻）</span>
                @endif
            </span>
        </div>
        <p class="text-xs text-slate-400 mb-3">
            当日以外でも NOW 赤線を確認できます。本番（GCP）では <code class="text-amber-200">TIF_ALLOW_NOW_OVERRIDE=false</code> にしてください。
        </p>
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            @if ($day)
                <input type="hidden" name="day" value="{{ $day }}">
            @endif
            <div>
                <label class="block text-xs text-slate-400 mb-1">日時</label>
                <input type="datetime-local" name="now"
                       value="{{ $now->format('Y-m-d\TH:i') }}"
                       class="rounded-lg bg-slate-800 border border-slate-600 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold hover:bg-amber-500">適用</button>
            <a href="{{ request()->url() }}?{{ http_build_query(array_filter(['day' => $day ?: null, 'now' => 'reset'])) }}"
               class="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800">現在時刻に戻す</a>
        </form>
        <div class="flex flex-wrap gap-2 mt-3">
            @foreach (['14:15', '16:25', '19:45', '20:25'] as $preset)
                <a href="{{ request()->url() }}?{{ http_build_query(['day' => $day ?: '2026-08-01', 'now' => ($day ?: '2026-08-01').' '.$preset]) }}"
                   class="rounded-full border border-amber-500/30 px-3 py-1 text-xs text-amber-100 hover:bg-amber-500/10">
                    {{ $preset }}
                </a>
            @endforeach
        </div>
    </section>
@endif
