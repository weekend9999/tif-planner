@extends('layouts.tif')

@section('title', 'TIF 2026 推し巡りプランナー')

@section('content')
<div class="space-y-8">
    <section class="rounded-2xl border border-pink-500/30 bg-gradient-to-br from-slate-900 to-slate-800 p-6">
        <p class="text-sm text-pink-300">TOKYO IDOL FESTIVAL 2026</p>
        <h1 class="mt-2 text-3xl font-bold">推し巡りプランナー</h1>
        <p class="mt-3 text-slate-300 leading-relaxed">
            ステージ間の移動時間を考慮して、推しのライブが<strong class="text-white">間に合うか</strong>、
            <strong class="text-white">いつ出発すべきか</strong>を自動判定します。
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('plans.edit') }}" class="rounded-xl bg-pink-500 px-5 py-3 font-semibold text-white">推しリストを作る</a>
            <a href="{{ route('live.index') }}" class="rounded-xl border border-red-400/50 px-5 py-3 text-red-300">当日ビュー</a>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-3">
        @foreach ($days as $date => $label)
            <a href="{{ route('timetable.index', ['day' => $date]) }}"
               class="rounded-xl border border-slate-700 bg-slate-900 p-5 hover:border-pink-500/50">
                <div class="text-lg font-semibold">{{ $label }}</div>
                <div class="mt-1 text-sm text-slate-400">タイムテーブルを見る</div>
            </a>
        @endforeach
    </section>

    <section class="rounded-2xl border border-slate-700 bg-slate-900 p-6">
        <h2 class="text-xl font-bold mb-4">使い方</h2>
        <ol class="space-y-3 text-slate-300 list-decimal list-inside">
            <li>「推しリストを作る」から見たいグループを追加</li>
            <li>「プラン結果」で間に合うか・退場時刻を確認</li>
            <li>当日は「当日ビュー」で NOW / NEXT / 出発アラートをチェック</li>
        </ol>
    </section>
</div>
@endsection
