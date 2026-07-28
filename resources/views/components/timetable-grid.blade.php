@props([
    'grid',
    'selectedIds' => [],
    'selectable' => false,
    'showNowLine' => false,
    'blockAnnotations' => [],
])

@php
    use App\Enums\FeasibilityStatus;

    $blocksByStage = collect($grid['blocks'])->groupBy('stage_slug');
    $selectedSet = collect($selectedIds)->flip();
    $stageCount = count($grid['stages']);
    $gridColumns = "2rem repeat({$stageCount}, minmax(0, 1fr))";
    $planResultMode = !empty($blockAnnotations);
    $statusBorderClasses = [
        FeasibilityStatus::Ok->value => FeasibilityStatus::Ok->blockBorderClass(),
        FeasibilityStatus::Tight->value => FeasibilityStatus::Tight->blockBorderClass(),
        FeasibilityStatus::LeaveNow->value => FeasibilityStatus::LeaveNow->blockBorderClass(),
        FeasibilityStatus::Impossible->value => FeasibilityStatus::Impossible->blockBorderClass(),
    ];
@endphp

<div {{ $attributes->merge(['class' => 'tif-timetable w-full']) }}
     @if ($selectable)
         x-data="{
             selectedIds: @js(array_values($selectedIds)),
             toggling: null,
             isSelected(id) { return this.selectedIds.includes(id); },
             async toggle(id) {
                 if (this.toggling === id) return;
                 this.toggling = id;
                 const selected = this.isSelected(id);
                 try {
                     const res = await fetch(`/plans/performances/${id}`, {
                         method: selected ? 'DELETE' : 'POST',
                         headers: {
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                             'Accept': 'application/json',
                         },
                     });
                     if (!res.ok) return;
                     const data = await res.json();
                     if (selected) {
                         this.selectedIds = this.selectedIds.filter(x => x !== id);
                     } else {
                         this.selectedIds.push(id);
                     }
                     this.$dispatch('plan-count-changed', { count: data.count });
                 } finally {
                     this.toggling = null;
                 }
             }
         }"
     @elseif ($planResultMode)
         x-data="{
             tip: null,
             tipX: 0,
             tipY: 0,
             annotations: @js($blockAnnotations),
             showTip(event, blockId) {
                 if (!this.annotations[blockId]) return;
                 this.tip = this.annotations[blockId];
                 this.moveTip(event);
             },
             moveTip(event) {
                 this.tipX = event.clientX;
                 this.tipY = event.clientY;
             },
             hideTip() { this.tip = null; }
         }"
         @mouseleave="hideTip()"
     @endif>
    <div class="tif-timetable-frame w-full overflow-visible rounded-lg border border-slate-600/80 bg-[#f5f5f0] shadow-inner">
        <div class="tif-timetable-inner w-full min-w-0">
                {{-- Stage headers --}}
                <div class="tif-timetable-head sticky top-0 z-20 grid border-b border-slate-400/50 w-full"
                     style="grid-template-columns: {{ $gridColumns }};">
                    <div class="bg-[#ecece6] border-r border-slate-400/50"></div>
                    @foreach ($grid['stages'] as $stage)
                        <div class="px-0.5 py-1.5 text-center font-bold leading-[1.1] border-r border-white/25 last:border-r-0 tif-stage-head"
                             style="background: {{ $stage['header'] }}; color: {{ $stage['label'] }}; font-size: clamp(6px, 0.72vw, 11px);">
                            <span class="whitespace-pre-line">{{ $stage['short_name'] ?? $stage['name'] }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Grid body --}}
                <div class="tif-timetable-body grid relative w-full"
                     style="grid-template-columns: {{ $gridColumns }}; height: {{ $grid['total_height_px'] }}px;">

                    @if ($showNowLine && isset($grid['now_line_px']))
                        <div class="absolute z-30 border-t-2 border-red-600 pointer-events-none"
                             style="top: {{ $grid['now_line_px'] }}px; left: 0; right: 0;"></div>
                    @endif

                    {{-- Time axis --}}
                    <div class="relative border-r border-slate-400/50 bg-[#ecece6]">
                        @foreach ($grid['time_labels'] as $label)
                            <div class="absolute left-0 right-0 border-t border-slate-400/40 text-slate-700 pl-0.5 pt-px font-mono leading-none tif-time-label"
                                 style="top: {{ $label['top_px'] }}px; font-size: clamp(7px, 0.55vw, 10px);">
                                {{ $label['time'] }}
                            </div>
                        @endforeach
                    </div>

                    {{-- Stage columns (colored timeline) --}}
                    @foreach ($grid['stages'] as $stage)
                        <div class="relative border-r border-white/20 last:border-r-0 min-w-0"
                             style="background: {{ $stage['column'] }};">
                            @foreach ($grid['time_labels'] as $label)
                                <div class="absolute left-0 right-0 border-t border-black/10 pointer-events-none"
                                     style="top: {{ $label['top_px'] }}px;"></div>
                            @endforeach

                            @foreach ($blocksByStage->get($stage['slug'], collect()) as $block)
                                @php
                                    $isFavoriteArtist = !empty($block['is_favorite_artist']);
                                    $annotation = $blockAnnotations[$block['id']] ?? null;
                                    $blockBg = $isFavoriteArtist ? 'bg-pink-100' : 'bg-white';

                                    if ($planResultMode) {
                                        $isSelected = $selectedSet->has($block['id']);
                                        $dimClass = $isSelected ? '' : 'opacity-25';
                                        $highlightClass = $isSelected && $annotation
                                            ? ($statusBorderClasses[$annotation['status']] ?? '')
                                            : '';
                                    } elseif ($selectable) {
                                        $isSelected = false;
                                        $dimClass = '';
                                        $highlightClass = '';
                                    } else {
                                        $isSelected = $selectedSet->has($block['id']);
                                        $dimClass = '';
                                        $highlightClass = $isSelected
                                            ? 'ring-[3px] ring-pink-500 ring-offset-1 ring-offset-white border-2 border-pink-600 shadow-md z-30'
                                            : '';
                                    }
                                @endphp
                                <div class="absolute left-0.5 right-0.5 rounded-[2px] overflow-visible {{ $blockBg }} shadow-sm transition-all tif-block min-w-0 text-slate-900 {{ $dimClass }} {{ !empty($block['is_now']) ? 'ring-2 ring-red-600 z-20' : '' }} {{ $highlightClass }}"
                                     style="top: {{ $block['top_px'] }}px; height: {{ $block['height_px'] }}px;"
                                     data-artist="{{ $block['artist_name'] }}"
                                     @if ($selectable)
                                         :class="isSelected({{ $block['id'] }}) ? 'ring-[3px] ring-pink-500 ring-offset-1 ring-offset-white border-2 border-pink-600 shadow-md z-30' : ''"
                                     @endif
                                     @if ($planResultMode && $isSelected && $annotation)
                                         data-feasibility="{{ $annotation['status'] }}"
                                         @mouseenter="showTip($event, {{ $block['id'] }})"
                                         @mousemove="moveTip($event)"
                                         @mouseleave="hideTip()"
                                     @endif
                                     @unless($planResultMode)
                                         title="{{ $block['artist_name'] }} ({{ $block['starts_at'] }}-{{ $block['ends_at'] }})"
                                     @endunless>
                                    <div class="h-full min-w-0 overflow-hidden rounded-[2px] {{ $blockBg }}">
                                    @if ($selectable)
                                        <button type="button"
                                                @click="toggle({{ $block['id'] }})"
                                                :disabled="toggling === {{ $block['id'] }}"
                                                class="w-full h-full min-w-0 text-left px-0.5 py-px flex flex-col text-slate-900 hover:bg-pink-50 disabled:opacity-70">
                                            <span class="font-mono leading-none text-slate-600 tif-block-time">{{ $block['starts_at'] }}-{{ $block['ends_at'] }}</span>
                                            <span class="font-bold leading-tight tif-block-name flex-1 min-h-0">{{ $block['artist_name'] }}</span>
                                        </button>
                                    @else
                                        <div class="relative h-full min-w-0 px-0.5 py-px flex flex-col text-slate-900">
                                            <span class="font-mono leading-none text-slate-600 tif-block-time">{{ $block['starts_at'] }}-{{ $block['ends_at'] }}</span>
                                            <span class="font-bold leading-tight tif-block-name flex-1 min-h-0">{{ $block['artist_name'] }}</span>
                                            @if (!empty($block['is_now']))
                                                <span class="font-bold text-red-600 tif-block-badge shrink-0">NOW</span>
                                            @endif
                                            @if ($planResultMode && $isSelected && $annotation)
                                                <span class="absolute bottom-0 right-0 z-10 font-semibold leading-none tif-block-status-badge bg-white px-0.5 rounded-tl-[2px] {{ match($annotation['status']) {
                                                    'ok' => 'text-emerald-700',
                                                    'tight', 'leave_now' => 'text-amber-700',
                                                    'impossible' => 'text-red-700',
                                                    default => 'text-slate-600',
                                                } }}">{{ $annotation['status_label'] }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
    </div>

    @if ($selectable)
        <p class="mt-2 text-xs text-slate-400">ブロックをタップで推しに追加 / ピンク枠をタップで解除。薄いピンクは登録済み推しアイドルの出演枠です。</p>
    @elseif ($planResultMode)
        <p class="mt-2 text-xs text-slate-400">枠線の色付きブロックにカーソルを合わせると移動判定の詳細が表示されます。</p>
    @endif

    @if ($planResultMode)
        <div x-show="tip" x-cloak x-transition.opacity
             class="fixed z-[100] pointer-events-none max-w-xs rounded-lg border border-slate-500 bg-slate-900/95 p-3 text-xs text-slate-100 shadow-2xl backdrop-blur-sm"
             :style="{ left: Math.min(tipX + 12, window.innerWidth - 280) + 'px', top: Math.min(tipY + 12, window.innerHeight - 160) + 'px' }">
            <template x-if="tip">
                <div>
                    <div class="font-bold text-sm mb-1.5" x-text="tip.status_label"></div>
                    <template x-for="(line, i) in tip.lines" :key="i">
                        <p class="text-slate-300 leading-relaxed" x-text="line"></p>
                    </template>
                </div>
            </template>
        </div>
    @endif
</div>

<style>
    .tif-block-time { font-size: clamp(6px, 0.5vw, 9px); }
    .tif-block-name {
        font-size: clamp(6px, 0.62vw, 10px);
        word-break: break-all;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
    }
    .tif-block-badge { font-size: clamp(6px, 0.5vw, 8px); }
    .tif-block-status-badge { font-size: clamp(5px, 0.45vw, 7px); }
    [x-cloak] { display: none !important; }
</style>
