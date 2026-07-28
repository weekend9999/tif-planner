<?php

namespace App\Services;

use App\Enums\BufferMode;
use App\Models\Performance;
use App\Models\Stage;
use App\Models\StageTravelTime;
use App\Models\TravelRule;
use Illuminate\Support\Collection;

class TravelTimeService
{
    /** @var array<string, int>|null */
    private ?array $matrix = null;

    /** @var array<int, Collection<int, TravelRule>>|null */
    private ?array $rulesByStage = null;

    /** @var array<int, string>|null */
    private ?array $stageSlugsById = null;

    private function ensureLoaded(): void
    {
        if ($this->matrix !== null) {
            return;
        }

        $this->matrix = [];
        $this->rulesByStage = [];
        $this->stageSlugsById = Stage::query()->pluck('slug', 'id')->all();

        StageTravelTime::query()
            ->get(['from_stage_id', 'to_stage_id', 'walk_minutes'])
            ->each(function (StageTravelTime $row): void {
                $this->matrix[$this->key($row->from_stage_id, $row->to_stage_id)] = $row->walk_minutes;
            });

        TravelRule::query()->get()->groupBy('stage_id')->each(function ($rules, $stageId): void {
            $this->rulesByStage[(int) $stageId] = $rules;
        });
    }

    public function walkMinutes(int $fromStageId, int $toStageId): int
    {
        $this->ensureLoaded();

        if ($fromStageId === $toStageId) {
            return 0;
        }

        return $this->matrix[$this->key($fromStageId, $toStageId)] ?? 15;
    }

    public function destinationExtraMinutes(int $fromStageId, int $toStageId): int
    {
        $this->ensureLoaded();

        $exemptBaggage = $this->isBaggageCheckExempt($fromStageId, $toStageId);

        return ($this->rulesByStage[$toStageId] ?? collect())
            ->sum(function (TravelRule $rule) use ($exemptBaggage): int {
                if ($exemptBaggage && $rule->rule_type === 'baggage_check') {
                    return 0;
                }

                return $rule->extra_minutes;
            });
    }

    /** @return list<string> */
    public function destinationAdvisoryLines(int $fromStageId, int $toStageId): array
    {
        $this->ensureLoaded();

        $rules = $this->rulesByStage[$toStageId] ?? collect();
        if ($rules->isEmpty()) {
            return [];
        }

        $exemptBaggage = $this->isBaggageCheckExempt($fromStageId, $toStageId);
        $lines = [];

        foreach ($rules as $rule) {
            if ($rule->rule_type === 'baggage_check') {
                if (! $exemptBaggage) {
                    $lines[] = '手荷物検査あり';
                }

                continue;
            }

            if ($rule->rule_type === 'elevator_wait') {
                $lines[] = 'エレベーター待ちは混雑時に時間が伸びやすいため、＋15分程度の余裕を見込むと安心です';
            }
        }

        return $lines;
    }

    private function isBaggageCheckExempt(int $fromStageId, int $toStageId): bool
    {
        $fromSlug = $this->stageSlugsById[$fromStageId] ?? '';
        $toSlug = $this->stageSlugsById[$toStageId] ?? '';
        $aomiStages = ['torocco-park', 'ukishima-stage'];

        return in_array($fromSlug, $aomiStages, true) && in_array($toSlug, $aomiStages, true);
    }

    public function totalTravelMinutes(
        int $fromStageId,
        int $toStageId,
        BufferMode $bufferMode,
        int $exitBuffer,
    ): int {
        if ($fromStageId === $toStageId) {
            return 0;
        }

        $walk = (int) ceil($this->walkMinutes($fromStageId, $toStageId) * $bufferMode->travelMultiplier());
        $destinationExtra = $this->destinationExtraMinutes($fromStageId, $toStageId);

        return $walk + $exitBuffer + $destinationExtra;
    }

    public function totalTravelMinutesBetweenPerformances(
        Performance $from,
        Performance $to,
        BufferMode $bufferMode,
        int $exitBuffer,
    ): int {
        return $this->totalTravelMinutes(
            $from->stage_id,
            $to->stage_id,
            $bufferMode,
            $exitBuffer,
        );
    }

    private function key(int $fromStageId, int $toStageId): string
    {
        return "{$fromStageId}:{$toStageId}";
    }
}
