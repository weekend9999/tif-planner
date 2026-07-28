<?php

namespace App\Services;

use App\Enums\BufferMode;
use App\Enums\FeasibilityStatus;
use App\Models\Performance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FeasibilityResult
{
    public function __construct(
        public readonly ?Performance $from,
        public readonly Performance $to,
        public readonly FeasibilityStatus $status,
        public readonly int $travelMinutes,
        public readonly Carbon $recommendedDeparture,
        public readonly Carbon $arrivalDeadline,
        public readonly ?int $minutesUntilDeparture = null,
        public readonly ?string $message = null,
    ) {}
}

class FeasibilityService
{
    public function __construct(
        private readonly TravelTimeService $travelTimeService,
    ) {}

    /**
     * @param  Collection<int, Performance>  $performances
     * @return array{
     *     legs: array<int, FeasibilityResult>,
     *     overlaps: array<int, array{first: Performance, second: Performance}>,
     *     allFeasible: bool
     * }
     */
    public function analyze(
        Collection $performances,
        BufferMode $bufferMode,
        int $exitBuffer,
        int $entryBuffer,
        ?Carbon $now = null,
    ): array {
        $sorted = $performances
            ->sortBy(fn (Performance $p) => $p->day->format('Y-m-d').' '.$p->starts_at)
            ->values();

        $legs = [];
        $previous = null;

        foreach ($sorted as $performance) {
            if ($previous === null) {
                $previous = $performance;
                continue;
            }

            $legs[] = $this->analyzeLeg(
                $previous,
                $performance,
                $bufferMode,
                $exitBuffer,
                $entryBuffer,
                $now,
            );

            $previous = $performance;
        }

        $overlaps = $this->detectOverlaps($sorted);
        $overlapDetails = $this->analyzeOverlaps($sorted, $bufferMode, $exitBuffer, $entryBuffer, $now);

        return [
            'legs' => $legs,
            'overlaps' => $overlaps,
            'overlapDetails' => $overlapDetails,
            'allFeasible' => collect($legs)->every(
                fn (FeasibilityResult $leg) => $leg->status !== FeasibilityStatus::Impossible
            ) && collect($overlapDetails)->every(
                fn (array $detail) => $detail['type'] !== 'impossible'
            ),
        ];
    }

    /**
     * Per-performance annotations for plan-result timetable highlights.
     *
     * @param  Collection<int, Performance>  $performances
     * @return array<int, array{
     *     status: FeasibilityStatus,
     *     status_label: string,
     *     lines: list<string>
     * }>
     */
    public function buildBlockAnnotations(
        Collection $performances,
        BufferMode $bufferMode,
        int $exitBuffer,
        int $entryBuffer,
        ?Carbon $now = null,
    ): array {
        if ($performances->isEmpty()) {
            return [];
        }

        $analysis = $this->analyze($performances, $bufferMode, $exitBuffer, $entryBuffer, $now);
        $sorted = $performances
            ->sortBy(fn (Performance $p) => $p->day->format('Y-m-d').' '.$p->starts_at)
            ->values();

        $annotations = [];
        $overlapPartners = [];

        foreach ($analysis['overlapDetails'] as $detail) {
            $a = $detail['first'];
            $b = $detail['second'];
            $overlapPartners[$a->id][] = $b;
            $overlapPartners[$b->id][] = $a;

            if ($detail['type'] === 'partial_resolvable') {
                $annotations[$detail['earlier']->id] = $this->annotationForEarlierInPartialOverlap(
                    $detail,
                    $bufferMode,
                    $exitBuffer,
                    $entryBuffer,
                );
                $annotations[$detail['later']->id] = $this->annotationForLaterInPartialOverlap(
                    $detail,
                    $bufferMode,
                    $exitBuffer,
                    $entryBuffer,
                );
                continue;
            }

            foreach ([$a, $b] as $performance) {
                if (isset($annotations[$performance->id])) {
                    continue;
                }

                $partners = collect($overlapPartners[$performance->id] ?? [])
                    ->map(fn (Performance $p) => $p->artist_name.' ('.$p->startsAtFormatted().')')
                    ->implode('、');

                $annotations[$performance->id] = [
                    'status' => FeasibilityStatus::Impossible->value,
                    'status_label' => FeasibilityStatus::Impossible->label(),
                    'lines' => $this->impossibleOverlapLines($performance, $partners, $detail),
                ];
            }
        }

        $firstPerDay = [];
        foreach ($sorted as $performance) {
            $dayKey = $performance->day->format('Y-m-d');
            $firstPerDay[$dayKey] ??= $performance->id;
        }

        foreach ($firstPerDay as $performanceId) {
            if (isset($annotations[$performanceId])) {
                continue;
            }

            $annotations[$performanceId] = [
                'status' => FeasibilityStatus::Ok->value,
                'status_label' => FeasibilityStatus::Ok->label(),
                'lines' => $this->movementSectionForFirstPerformance(),
            ];
        }

        foreach ($analysis['legs'] as $leg) {
            $toId = $leg->to->id;

            if (isset($annotations[$toId])) {
                $annotations[$toId] = $this->mergeAnnotationWithIncomingLeg($annotations[$toId], $leg);

                continue;
            }

            $annotations[$toId] = $this->annotationFromLeg($leg);
        }

        return $annotations;
    }

    /** @return array{status: string, status_label: string, lines: list<string>} */
    private function mergeAnnotationWithIncomingLeg(array $existing, FeasibilityResult $leg): array
    {
        $status = $this->worstStatus(
            FeasibilityStatus::from($existing['status']),
            $leg->status,
        );

        $lines = array_merge(
            $this->movementSectionLines($leg),
            $existing['lines'],
        );

        return [
            'status' => $status->value,
            'status_label' => $status->label(),
            'lines' => $lines,
        ];
    }

    /** @return list<string> */
    private function movementSectionForFirstPerformance(): array
    {
        return [
            '【移動】',
            'この日の最初の推し（移動判定なし）',
        ];
    }

    /** @return list<string> */
    private function movementSectionLines(FeasibilityResult $leg): array
    {
        $lines = [
            '【移動】',
            sprintf(
                '%s（%s）→ %s（%s）',
                $leg->from->artist_name,
                $leg->from->stage->name,
                $leg->to->artist_name,
                $leg->to->stage->name,
            ),
        ];

        if ($leg->travelMinutes > 0) {
            $lines[] = '移動 '.$leg->travelMinutes.' 分';
        }

        $lines[] = '退場推奨 '.$leg->recommendedDeparture->format('H:i');
        $lines[] = '入場期限 '.$leg->arrivalDeadline->format('H:i');

        $fromEnd = $this->toDateTime($leg->from->day, $leg->from->ends_at);
        if ($leg->recommendedDeparture->lt($fromEnd)) {
            $missedMinutes = max(0, (int) $leg->recommendedDeparture->diffInMinutes($fromEnd, false));
            $lines[] = sprintf(
                '前の公演終了（%s）より前の途中退場が必要（後半約 %d 分は見逃し）',
                $fromEnd->format('H:i'),
                $missedMinutes,
            );
        } elseif ($fromEnd->copy()->addMinutes($leg->travelMinutes)->gt($leg->arrivalDeadline)) {
            $lines[] = sprintf(
                '%s を最後まで見ると %s に間に合いません',
                $leg->from->artist_name,
                $leg->to->artist_name,
            );
        }

        $lines = array_merge(
            $lines,
            $this->travelTimeService->destinationAdvisoryLines($leg->from->stage_id, $leg->to->stage_id),
        );

        return $lines;
    }

    private function worstStatus(FeasibilityStatus $a, FeasibilityStatus $b): FeasibilityStatus
    {
        $severity = [
            FeasibilityStatus::Impossible->value => 0,
            FeasibilityStatus::LeaveNow->value => 1,
            FeasibilityStatus::Tight->value => 2,
            FeasibilityStatus::Ok->value => 3,
        ];

        return ($severity[$a->value] ?? 3) <= ($severity[$b->value] ?? 3) ? $a : $b;
    }

    /** @return array{status: string, status_label: string, lines: list<string>} */
    private function annotationFromLeg(FeasibilityResult $leg): array
    {
        return [
            'status' => $leg->status->value,
            'status_label' => $leg->status->label(),
            'lines' => $this->movementSectionLines($leg),
        ];
    }

    /**
     * @param  Collection<int, Performance>  $performances
     * @return list<array{
     *     first: Performance,
     *     second: Performance,
     *     earlier: Performance,
     *     later: Performance,
     *     type: 'partial_resolvable'|'impossible'|'same_start',
     *     overlap_minutes: int,
     *     leg: FeasibilityResult|null,
     *     summary: string
     * }>
     */
    public function analyzeOverlaps(
        Collection $performances,
        BufferMode $bufferMode,
        int $exitBuffer,
        int $entryBuffer,
        ?Carbon $now = null,
    ): array {
        $details = [];

        foreach ($this->detectOverlaps($performances) as $overlap) {
            $details[] = $this->analyzeOverlapPair(
                $overlap['first'],
                $overlap['second'],
                $bufferMode,
                $exitBuffer,
                $entryBuffer,
                $now,
            );
        }

        return $details;
    }

    /**
     * @return array{
     *     first: Performance,
     *     second: Performance,
     *     earlier: Performance,
     *     later: Performance,
     *     type: 'partial_resolvable'|'impossible'|'same_start',
     *     overlap_minutes: int,
     *     leg: FeasibilityResult|null,
     *     summary: string
     * }
     */
    private function analyzeOverlapPair(
        Performance $a,
        Performance $b,
        BufferMode $bufferMode,
        int $exitBuffer,
        int $entryBuffer,
        ?Carbon $now,
    ): array {
        [$earlier, $later] = $this->orderByStart($a, $b);
        $overlapMinutes = $this->overlapMinutes($a, $b);

        if ($this->startsAtSameTime($earlier, $later)) {
            return [
                'first' => $a,
                'second' => $b,
                'earlier' => $earlier,
                'later' => $later,
                'type' => 'same_start',
                'overlap_minutes' => $overlapMinutes,
                'leg' => null,
                'summary' => $earlier->artist_name.' と '.$later->artist_name.' は同時開演のため、両方の冒頭からは見られません。',
            ];
        }

        $leg = $this->analyzeLeg($earlier, $later, $bufferMode, $exitBuffer, $entryBuffer, $now);

        if ($leg->status === FeasibilityStatus::Impossible) {
            return [
                'first' => $a,
                'second' => $b,
                'earlier' => $earlier,
                'later' => $later,
                'type' => 'impossible',
                'overlap_minutes' => $overlapMinutes,
                'leg' => $leg,
                'summary' => $earlier->artist_name.' と '.$later->artist_name.' は時間が重なり、途中退場しても '.$later->artist_name.' に間に合いません。',
            ];
        }

        return [
            'first' => $a,
            'second' => $b,
            'earlier' => $earlier,
            'later' => $later,
            'type' => 'partial_resolvable',
            'overlap_minutes' => $overlapMinutes,
            'leg' => $leg,
            'summary' => sprintf(
                '%s の途中退場（%s 頃）なら %s（%s）の冒頭から見られる見込みです。',
                $earlier->artist_name,
                $leg->recommendedDeparture->format('H:i'),
                $later->artist_name,
                $later->startsAtFormatted(),
            ),
        ];
    }

    /** @return array{status: string, status_label: string, lines: list<string>} */
    private function annotationForEarlierInPartialOverlap(
        array $detail,
        BufferMode $bufferMode,
        int $exitBuffer,
        int $entryBuffer,
    ): array {
        /** @var FeasibilityResult $earlyExitLeg */
        $earlyExitLeg = $detail['leg'];
        $earlier = $detail['earlier'];
        $later = $detail['later'];
        $fullShow = $this->analyzeFullShowThenMove($earlier, $later, $bufferMode, $exitBuffer);

        $lines = array_merge(
            $this->overlapSectionLines($detail['overlap_minutes']),
            $this->viewingChoicesForEarlierInOverlap($earlier, $later, $fullShow, $earlyExitLeg),
            $this->travelTimeService->destinationAdvisoryLines($earlier->stage_id, $later->stage_id),
        );

        return [
            'status' => $earlyExitLeg->status->value,
            'status_label' => $earlyExitLeg->status->label(),
            'lines' => $lines,
        ];
    }

    /** @return array{status: string, status_label: string, lines: list<string>} */
    private function annotationForLaterInPartialOverlap(
        array $detail,
        BufferMode $bufferMode,
        int $exitBuffer,
        int $entryBuffer,
    ): array {
        /** @var FeasibilityResult $earlyExitLeg */
        $earlyExitLeg = $detail['leg'];
        $earlier = $detail['earlier'];
        $later = $detail['later'];
        $fullShow = $this->analyzeFullShowThenMove($earlier, $later, $bufferMode, $exitBuffer);

        $lines = array_merge(
            $this->overlapSectionLines($detail['overlap_minutes']),
            $this->viewingChoicesForLaterInOverlap($earlier, $later, $fullShow, $earlyExitLeg),
            $this->travelTimeService->destinationAdvisoryLines($earlier->stage_id, $later->stage_id),
        );

        return [
            'status' => $earlyExitLeg->status->value,
            'status_label' => $earlyExitLeg->status->label(),
            'lines' => $lines,
        ];
    }

    /** @return list<string> */
    private function overlapSectionLines(int $overlapMinutes): array
    {
        return [
            '【公演時間かぶり】',
            $overlapMinutes.' 分かぶり',
        ];
    }

    /**
     * @param  array{departure: Carbon, arrival: Carbon, travelMinutes: int, missedFromStartMinutes: int, watchableMinutes: int, canCatchAny: bool, canCatchFromStart: bool}  $fullShow
     * @return list<string>
     */
    private function viewingChoicesForEarlierInOverlap(
        Performance $earlier,
        Performance $later,
        array $fullShow,
        FeasibilityResult $earlyExitLeg,
    ): array {
        $earlierStart = $this->toDateTime($earlier->day, $earlier->starts_at);
        $watchMinutes = max(0, (int) $earlierStart->diffInMinutes($earlyExitLeg->recommendedDeparture, false));
        $totalMinutes = max(1, (int) $earlierStart->diffInMinutes($this->toDateTime($earlier->day, $earlier->ends_at), false));
        $missedMinutes = max(0, $totalMinutes - $watchMinutes);

        $lines = ['【見方の選択肢】'];
        $lines[] = '① 最後まで観て移動 → '.$this->describeFullShowChoice($earlier, $later, $fullShow);
        $lines[] = sprintf(
            '② 途中退場して移動 → %s 頃退場、%s（%s）の冒頭から（%s は約 %d 分視聴、後半約 %d 分は見逃し）',
            $earlyExitLeg->recommendedDeparture->format('H:i'),
            $later->artist_name,
            $later->startsAtFormatted(),
            $earlier->artist_name,
            $watchMinutes,
            $missedMinutes,
        );

        return $lines;
    }

    /**
     * @param  array{departure: Carbon, arrival: Carbon, travelMinutes: int, missedFromStartMinutes: int, watchableMinutes: int, canCatchAny: bool, canCatchFromStart: bool}  $fullShow
     * @return list<string>
     */
    private function viewingChoicesForLaterInOverlap(
        Performance $earlier,
        Performance $later,
        array $fullShow,
        FeasibilityResult $earlyExitLeg,
    ): array {
        $lines = ['【見方の選択肢】'];
        $lines[] = '① '.$earlier->artist_name.' を最後まで観る → '.$this->describeFullShowChoiceForLater($earlier, $later, $fullShow);
        $lines[] = sprintf(
            '② %s 途中退場 → %s 頃退場、%s 開演から見られる見込み（入場期限 %s）',
            $earlier->artist_name,
            $earlyExitLeg->recommendedDeparture->format('H:i'),
            $later->startsAtFormatted(),
            $earlyExitLeg->arrivalDeadline->format('H:i'),
        );

        return $lines;
    }

    /**
     * @param  array{departure: Carbon, arrival: Carbon, travelMinutes: int, missedFromStartMinutes: int, watchableMinutes: int, canCatchAny: bool, canCatchFromStart: bool}  $fullShow
     */
    private function describeFullShowChoice(Performance $earlier, Performance $later, array $fullShow): string
    {
        if (! $fullShow['canCatchAny']) {
            return sprintf(
                '%s 退場、%s へ（移動 %d 分）→ %s には間に合いません',
                $fullShow['departure']->format('H:i'),
                $later->stage->name,
                $fullShow['travelMinutes'],
                $later->artist_name,
            );
        }

        if ($fullShow['canCatchFromStart']) {
            return sprintf(
                '%s 退場、%s へ（移動 %d 分）→ %s（%s）の冒頭から見られる見込み',
                $fullShow['departure']->format('H:i'),
                $later->stage->name,
                $fullShow['travelMinutes'],
                $later->artist_name,
                $later->startsAtFormatted(),
            );
        }

        return sprintf(
            '%s 退場、%s へ（移動 %d 分）→ %s は %s 頃から（冒頭約 %d 分見逃し、約 %d 分視聴可能）',
            $fullShow['departure']->format('H:i'),
            $later->stage->name,
            $fullShow['travelMinutes'],
            $later->artist_name,
            $fullShow['arrival']->format('H:i'),
            $fullShow['missedFromStartMinutes'],
            $fullShow['watchableMinutes'],
        );
    }

    /**
     * @param  array{departure: Carbon, arrival: Carbon, travelMinutes: int, missedFromStartMinutes: int, watchableMinutes: int, canCatchAny: bool, canCatchFromStart: bool}  $fullShow
     */
    private function describeFullShowChoiceForLater(Performance $earlier, Performance $later, array $fullShow): string
    {
        if (! $fullShow['canCatchAny']) {
            return sprintf(
                '終演（%s）後の移動（%d 分）では間に合いません',
                $fullShow['departure']->format('H:i'),
                $fullShow['travelMinutes'],
            );
        }

        if ($fullShow['canCatchFromStart']) {
            return sprintf(
                '終演（%s）後の移動（%d 分）→ %s 開演から見られる見込み',
                $fullShow['departure']->format('H:i'),
                $fullShow['travelMinutes'],
                $later->startsAtFormatted(),
            );
        }

        return sprintf(
            '終演（%s）後の移動（%d 分）→ %s 頃からの途中参加（約 %d 分視聴可能、冒頭約 %d 分は見逃し）',
            $fullShow['departure']->format('H:i'),
            $fullShow['travelMinutes'],
            $fullShow['arrival']->format('H:i'),
            $fullShow['watchableMinutes'],
            $fullShow['missedFromStartMinutes'],
        );
    }

    /**
     * @return array{
     *     departure: Carbon,
     *     arrival: Carbon,
     *     travelMinutes: int,
     *     missedFromStartMinutes: int,
     *     watchableMinutes: int,
     *     canCatchAny: bool,
     *     canCatchFromStart: bool
     * }
     */
    private function analyzeFullShowThenMove(
        Performance $from,
        Performance $to,
        BufferMode $bufferMode,
        int $exitBuffer,
    ): array {
        $travelMinutes = $this->travelTimeService->totalTravelMinutesBetweenPerformances(
            $from,
            $to,
            $bufferMode,
            $exitBuffer,
        );

        $departure = $this->toDateTime($from->day, $from->ends_at);
        $arrival = $departure->copy()->addMinutes($travelMinutes);
        $toStart = $this->toDateTime($to->day, $to->starts_at);
        $toEnd = $this->toDateTime($to->day, $to->ends_at);

        $canCatchAny = $arrival->lt($toEnd);
        $canCatchFromStart = $arrival->lte($toStart);
        $missedFromStartMinutes = $canCatchFromStart
            ? 0
            : max(0, (int) $toStart->diffInMinutes($arrival, false));
        $watchableMinutes = $canCatchAny
            ? max(0, (int) $arrival->diffInMinutes($toEnd, false))
            : 0;

        return [
            'departure' => $departure,
            'arrival' => $arrival,
            'travelMinutes' => $travelMinutes,
            'missedFromStartMinutes' => $missedFromStartMinutes,
            'watchableMinutes' => $watchableMinutes,
            'canCatchAny' => $canCatchAny,
            'canCatchFromStart' => $canCatchFromStart,
        ];
    }

    /** @return list<string> */
    private function impossibleOverlapLines(Performance $performance, string $partnerNames, array $detail): array
    {
        $lines = $this->overlapSectionLines($detail['overlap_minutes']);

        if ($detail['type'] === 'same_start') {
            $lines[] = $performance->artist_name.' と '.$partnerNames.' は同時開演のため、両方の冒頭からは見られません';
        } else {
            $lines[] = $performance->artist_name.' と '.$partnerNames.' は同時視聴できません';
            if ($detail['leg'] !== null) {
                $lines[] = '途中退場しても次の推しに間に合いません（'.$detail['leg']->message.')';
            }
        }

        return $lines;
    }

    /** @return array{0: Performance, 1: Performance} */
    private function orderByStart(Performance $a, Performance $b): array
    {
        $aStart = $this->toDateTime($a->day, $a->starts_at);
        $bStart = $this->toDateTime($b->day, $b->starts_at);

        if ($aStart->eq($bStart)) {
            $aEnd = $this->toDateTime($a->day, $a->ends_at);
            $bEnd = $this->toDateTime($b->day, $b->ends_at);

            return $aEnd->lte($bEnd) ? [$a, $b] : [$b, $a];
        }

        return $aStart->lt($bStart) ? [$a, $b] : [$b, $a];
    }

    private function startsAtSameTime(Performance $a, Performance $b): bool
    {
        return substr((string) $a->starts_at, 0, 5) === substr((string) $b->starts_at, 0, 5)
            && $a->day->format('Y-m-d') === $b->day->format('Y-m-d');
    }

    private function overlapMinutes(Performance $a, Performance $b): int
    {
        $start = max(
            $this->timeToMinutes(substr((string) $a->starts_at, 0, 5)),
            $this->timeToMinutes(substr((string) $b->starts_at, 0, 5)),
        );
        $end = min(
            $this->timeToMinutes(substr((string) $a->ends_at, 0, 5)),
            $this->timeToMinutes(substr((string) $b->ends_at, 0, 5)),
        );

        return max(0, $end - $start);
    }

    private function timeToMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }

    public function analyzeLeg(
        Performance $from,
        Performance $to,
        BufferMode $bufferMode,
        int $exitBuffer,
        int $entryBuffer,
        ?Carbon $now = null,
    ): FeasibilityResult {
        if ($from->stage_id === $to->stage_id) {
            return $this->analyzeSameStageLeg($from, $to, $now);
        }

        $travelMinutes = $this->travelTimeService->totalTravelMinutesBetweenPerformances(
            $from,
            $to,
            $bufferMode,
            $exitBuffer,
        );

        $fromEnd = $this->toDateTime($from->day, $from->ends_at);
        $fromStart = $this->toDateTime($from->day, $from->starts_at);
        $toStart = $this->toDateTime($to->day, $to->starts_at);
        $arrivalDeadline = $toStart->copy()->subMinutes($entryBuffer);
        $recommendedDeparture = $arrivalDeadline->copy()->subMinutes($travelMinutes);

        $status = $this->determineStatus($fromStart, $fromEnd, $recommendedDeparture, $now);
        $minutesUntilDeparture = $now
            ? (int) $now->diffInMinutes($recommendedDeparture, false)
            : null;

        $message = $this->buildMessage($status, $fromEnd, $recommendedDeparture, $travelMinutes, $minutesUntilDeparture);

        return new FeasibilityResult(
            from: $from,
            to: $to,
            status: $status,
            travelMinutes: $travelMinutes,
            recommendedDeparture: $recommendedDeparture,
            arrivalDeadline: $arrivalDeadline,
            minutesUntilDeparture: $minutesUntilDeparture,
            message: $message,
        );
    }

    private function analyzeSameStageLeg(
        Performance $from,
        Performance $to,
        ?Carbon $now = null,
    ): FeasibilityResult {
        $fromEnd = $this->toDateTime($from->day, $from->ends_at);
        $toStart = $this->toDateTime($to->day, $to->starts_at);
        $gapMinutes = (int) $fromEnd->diffInMinutes($toStart, false);

        if ($gapMinutes < 0) {
            $status = FeasibilityStatus::Impossible;
            $message = '同一ステージですが時間が重なっています。どちらかを選んでください。';
        } elseif ($gapMinutes === 0) {
            $status = FeasibilityStatus::Ok;
            $message = '同一ステージ・開演直後の出演です。その場で待機できます。';
        } elseif ($gapMinutes <= 5) {
            $status = FeasibilityStatus::Tight;
            $message = sprintf('同一ステージ・移動不要。次の出演まで %d 分です。', $gapMinutes);
        } else {
            $status = FeasibilityStatus::Ok;
            $message = sprintf('同一ステージ・移動不要。次の出演まで %d 分の間隔があります。', $gapMinutes);
        }

        $recommendedDeparture = $fromEnd->copy();
        $minutesUntilDeparture = $now
            ? max(0, (int) $now->diffInMinutes($recommendedDeparture, false))
            : null;

        return new FeasibilityResult(
            from: $from,
            to: $to,
            status: $status,
            travelMinutes: 0,
            recommendedDeparture: $recommendedDeparture,
            arrivalDeadline: $toStart,
            minutesUntilDeparture: $minutesUntilDeparture,
            message: $message,
        );
    }

    /**
     * @param  Collection<int, Performance>  $performances
     * @return array<int, array{first: Performance, second: Performance}>
     */
    public function detectOverlaps(Collection $performances): array
    {
        $overlaps = [];
        $grouped = $performances->groupBy(fn (Performance $p) => $p->day->format('Y-m-d'));

        foreach ($grouped as $dayPerformances) {
            $items = $dayPerformances->values();
            for ($i = 0; $i < $items->count(); $i++) {
                for ($j = $i + 1; $j < $items->count(); $j++) {
                    $a = $items[$i];
                    $b = $items[$j];

                    if ($this->timesOverlap($a, $b)) {
                        $overlaps[] = ['first' => $a, 'second' => $b];
                    }
                }
            }
        }

        return $overlaps;
    }

    private function determineStatus(
        Carbon $fromStart,
        Carbon $fromEnd,
        Carbon $recommendedDeparture,
        ?Carbon $now,
    ): FeasibilityStatus {
        if ($recommendedDeparture->lt($fromStart)) {
            return FeasibilityStatus::Impossible;
        }

        if ($now !== null && $now->gte($recommendedDeparture) && $now->lt($fromEnd)) {
            return FeasibilityStatus::LeaveNow;
        }

        if ($recommendedDeparture->lt($fromEnd)) {
            $watchMinutes = max(0, (int) $fromStart->diffInMinutes($recommendedDeparture, false));
            $missedMinutes = max(0, (int) $recommendedDeparture->diffInMinutes($fromEnd, false));

            if ($missedMinutes > 5 || $watchMinutes < 10) {
                return FeasibilityStatus::Tight;
            }

            return FeasibilityStatus::Ok;
        }

        $slackMinutes = max(0, (int) $fromEnd->diffInMinutes($recommendedDeparture, false));

        if ($slackMinutes <= 5) {
            return FeasibilityStatus::Tight;
        }

        return FeasibilityStatus::Ok;
    }

    private function buildMessage(
        FeasibilityStatus $status,
        Carbon $fromEnd,
        Carbon $recommendedDeparture,
        int $travelMinutes,
        ?int $minutesUntilDeparture,
    ): string {
        return match ($status) {
            FeasibilityStatus::Impossible => sprintf(
                '前のライブ終了(%s)前に出発(%s)が必要です。移動+%d分',
                $fromEnd->format('H:i'),
                $recommendedDeparture->format('H:i'),
                $travelMinutes,
            ),
            FeasibilityStatus::LeaveNow => '今すぐ移動を開始してください。',
            FeasibilityStatus::Tight => sprintf(
                'ギリギリです。%s までに退場推奨（移動 %d 分）',
                $recommendedDeparture->format('H:i'),
                $travelMinutes,
            ),
            FeasibilityStatus::Ok => $minutesUntilDeparture !== null
                ? sprintf('出発まであと %d 分（%s 退場推奨）', max(0, $minutesUntilDeparture), $recommendedDeparture->format('H:i'))
                : sprintf('%s 退場推奨（移動 %d 分）', $recommendedDeparture->format('H:i'), $travelMinutes),
        };
    }

    private function timesOverlap(Performance $a, Performance $b): bool
    {
        if ($a->day->format('Y-m-d') !== $b->day->format('Y-m-d')) {
            return false;
        }

        $aStart = $this->toDateTime($a->day, $a->starts_at);
        $aEnd = $this->toDateTime($a->day, $a->ends_at);
        $bStart = $this->toDateTime($b->day, $b->starts_at);
        $bEnd = $this->toDateTime($b->day, $b->ends_at);

        return $aStart->lt($bEnd) && $bStart->lt($aEnd);
    }

    private function toDateTime(Carbon|\DateTimeInterface $day, string $time): Carbon
    {
        $date = $day instanceof Carbon ? $day->format('Y-m-d') : Carbon::parse($day)->format('Y-m-d');

        return Carbon::parse("{$date} {$time}");
    }
}
