<?php

namespace App\Services;

use App\Models\Performance;
use App\Models\Stage;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TimetableImportService
{
    /**
     * @return array{imported: int, skipped: int, errors: array<int, string>}
     */
    public function importFromCsv(string $csvPath, bool $replaceDay = false): array
    {
        if (! is_readable($csvPath)) {
            throw new InvalidArgumentException("CSV file not readable: {$csvPath}");
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            throw new InvalidArgumentException("Failed to open CSV: {$csvPath}");
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new InvalidArgumentException('CSV is empty');
        }

        $header = array_map(fn ($col) => strtolower(trim((string) $col)), $header);
        $this->validateHeader($header);

        $rows = [];
        $errors = [];
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = array_combine($header, array_pad($data, count($header), ''));
            if ($row === false) {
                $errors[$line] = '列数がヘッダーと一致しません';

                continue;
            }

            try {
                $rows[] = $this->normalizeRow($row);
            } catch (InvalidArgumentException $e) {
                $errors[$line] = $e->getMessage();
            }
        }

        fclose($handle);

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $replaceDay, &$imported, &$skipped): void {
            if ($replaceDay && ! empty($rows)) {
                $days = collect($rows)->pluck('day')->unique();
                Performance::query()
                    ->where(function ($query) use ($days): void {
                        foreach ($days as $day) {
                            $query->orWhereDate('day', $day);
                        }
                    })
                    ->delete();
            }

            $stagesBySlug = Stage::query()->pluck('id', 'slug');

            foreach ($rows as $row) {
                $stageId = $stagesBySlug[$row['stage_slug']] ?? null;
                if ($stageId === null) {
                    $skipped++;

                    continue;
                }

                Performance::query()->updateOrCreate(
                    [
                        'day' => $row['day'],
                        'stage_id' => $stageId,
                        'artist_name' => $row['artist_name'],
                        'starts_at' => $row['starts_at'],
                    ],
                    [
                        'ends_at' => $row['ends_at'],
                        'notes' => $row['notes'] ?? null,
                    ],
                );

                $imported++;
            }
        });

        return compact('imported', 'skipped', 'errors');
    }

    /** @param  array<string, string>  $header */
    private function validateHeader(array $header): void
    {
        $required = ['day', 'stage_slug', 'artist_name', 'starts_at', 'ends_at'];
        foreach ($required as $column) {
            if (! in_array($column, $header, true)) {
                throw new InvalidArgumentException("CSV header must include: {$column}");
            }
        }
    }

    /**
     * @param  array<string, string>  $row
     * @return array{day: string, stage_slug: string, artist_name: string, starts_at: string, ends_at: string, notes: ?string}
     */
    private function normalizeRow(array $row): array
    {
        $day = trim($row['day']);
        $stageSlug = trim($row['stage_slug']);
        $artist = trim($row['artist_name']);
        $startsAt = $this->normalizeTime(trim($row['starts_at']));
        $endsAt = $this->normalizeTime(trim($row['ends_at']));
        $notes = trim($row['notes'] ?? '') ?: null;

        if ($day === '' || $stageSlug === '' || $artist === '') {
            throw new InvalidArgumentException('day, stage_slug, artist_name は必須です');
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            throw new InvalidArgumentException("day は YYYY-MM-DD 形式にしてください: {$day}");
        }

        return [
            'day' => $day,
            'stage_slug' => $stageSlug,
            'artist_name' => $artist,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => $notes,
        ];
    }

    private function normalizeTime(string $time): string
    {
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            [$h, $m] = explode(':', $time);

            return sprintf('%02d:%02d:00', (int) $h, (int) $m);
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        throw new InvalidArgumentException("時刻形式が不正です: {$time}");
    }
}
