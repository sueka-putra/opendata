<?php

namespace App\Services;

use App\Models\AssessmentCountry;
use App\Models\AssessmentSummary;
use Illuminate\Support\Facades\DB;

class ScoreService
{
    private static function normalizeOpennessValue($value): float
    {
        $num = (float) ($value ?? 0);
        return $num < 0 ? 0.0 : $num;
    }

    /**
     * Parse series string into array of years.
     * Allowed: empty, 'NA', or comma-separated years.
     */
    public static function parseSeries(?string $series): array
    {
        $s = trim((string) $series);
        if ($s === '' || strtoupper($s) === 'NA') {
            return [];
        }
        $parts = array_filter(array_map('trim', explode(',', $s)));
        $years = [];
        foreach ($parts as $p) {
            if (preg_match('/^\d{4}$/', $p)) {
                $years[] = (int) $p;
                continue;
            }

            // Handle year ranges, e.g. 2016-2020
            if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $p, $m)) {
                $start = (int) $m[1];
                $end = (int) $m[2];
                if ($start > $end) {
                    [$start, $end] = [$end, $start];
                }
                foreach (range($start, $end) as $year) {
                    $years[] = $year;
                }
                continue;
            }

            // Handle fiscal-like tokens, e.g. 2012/2013, count as one year.
            if (preg_match('/^(\d{4})\s*\/\s*(\d{4})$/', $p, $m)) {
                $years[] = max((int) $m[1], (int) $m[2]);
                continue;
            }

            // Handle short fiscal tokens, e.g. 2015/16, count as one year (2016).
            if (preg_match('/^(\d{4})\s*\/\s*(\d{2})$/', $p, $m)) {
                $startYear = (int) $m[1];
                $endTwoDigits = (int) $m[2];
                $endYear = ((int) floor($startYear / 100) * 100) + $endTwoDigits;
                if ($endYear < $startYear) {
                    $endYear += 100;
                }
                $years[] = max($startYear, $endYear);
            }
        }
        $years = array_values(array_unique($years));
        sort($years);
        return $years;
    }

    public static function computeRowCoverage(string $series, ?int $referenceYear = null): array
    {
        $raw = trim((string) $series);
        if (strtoupper($raw) === 'NA') {
            return [
                'count_all' => null,
                'count_5' => null,
                'count_10' => null,
                'c1' => null,
                'c2' => null,
                'c3' => null,
                'c' => null,
                'is_na' => true,
            ];
        }
        $years = self::parseSeries($raw);
        $countAll = count($years);
        $currentYear = (int) ($referenceYear ?: now('UTC')->format('Y'));
        $last5 = range($currentYear - 4, $currentYear);
        $last10 = range($currentYear - 9, $currentYear);
        $count5 = count(array_intersect($years, $last5));
        $count10 = count(array_intersect($years, $last10));
        $c1 = $countAll > 0 ? 1 : 0;
        $c2 = $count5 > 2 ? 1 : ($count5 > 1 ? 0.5 : 0);
        $c3 = $count10 > 5 ? 1 : ($count10 > 2 ? 0.5 : 0);
        return [
            'count_all' => $countAll,
            'count_5' => $count5,
            'count_10' => $count10,
            'c1' => $c1,
            'c2' => $c2,
            'c3' => $c3,
            'c' => $c1 + $c2 + $c3,
            'is_na' => false,
        ];
    }

    public static function computeRowOpenness(array $row): float
    {
        // Expect O1-O5 already validated to allowed set.
        return self::normalizeOpennessValue($row['machine_readability'] ?? 0)
            + self::normalizeOpennessValue($row['proprietary'] ?? 0)
            + self::normalizeOpennessValue($row['download_options'] ?? 0)
            + self::normalizeOpennessValue($row['metadata'] ?? 0)
            + self::normalizeOpennessValue($row['term_of_use'] ?? 0);
    }

    /**
     * Recompute per-section summaries (and optionally weighted) and persist to od_trx_assessment_summaries.
     */
    public static function recomputeAndPersist(AssessmentCountry $assessmentCountry): array
    {
        $assessmentCountry->loadMissing('period');
        $referenceYear = (int) ($assessmentCountry->period?->year ?: now('UTC')->format('Y'));

        $rows = DB::table('od_trx_assessment_country_rows as cr')
            ->join('od_mst_configuration_rows as cfg', 'cr.row_id', '=', 'cfg.id')
            ->select([
                'cr.id',
                'cr.row_id',
                'cfg.section_id',
                'cr.series',
                'cr.machine_readability',
                'cr.proprietary',
                'cr.download_options',
                'cr.metadata',
                'cr.term_of_use',
            ])
            ->where('cr.assessment_country_id', $assessmentCountry->id)
            ->get();

        $rowsWithScores = collect($rows)->map(function ($row) use ($referenceYear) {
            $cov = self::computeRowCoverage((string) $row->series, $referenceYear);
            $openness = $cov['is_na'] ? null : self::computeRowOpenness((array) $row);

            return [
                'id' => (int) $row->id,
                'row_id' => (int) $row->row_id,
                'section_id' => (int) $row->section_id,
                'count_all' => $cov['count_all'],
                'count_5' => $cov['count_5'],
                'count_10' => $cov['count_10'],
                'c1' => $cov['c1'],
                'c2' => $cov['c2'],
                'c3' => $cov['c3'],
                'coverage_sub_score' => $cov['is_na'] ? 0.0 : (float) $cov['c'],
                'opennes_sub_score' => $cov['is_na'] ? 0.0 : (float) $openness,
                'is_na' => (bool) $cov['is_na'],
            ];
        })->values();

        $bySection = $rowsWithScores->groupBy('section_id');
        $summaries = [];

        DB::transaction(function () use ($assessmentCountry, $rowsWithScores, $bySection, &$summaries) {
            // Persist row-level derived scores.
            foreach ($rowsWithScores as $row) {
                DB::table('od_trx_assessment_country_rows')
                    ->where('id', $row['id'])
                    ->update([
                        'count_all' => $row['count_all'],
                        'count_5' => $row['count_5'],
                        'count_10' => $row['count_10'],
                        'c1' => $row['c1'],
                        'c2' => $row['c2'],
                        'c3' => $row['c3'],
                        'coverage_sub_score' => $row['coverage_sub_score'],
                        'opennes_sub_score' => $row['opennes_sub_score'],
                    ]);
            }

            // Clear existing
            AssessmentSummary::where('assessment_country_id', $assessmentCountry->id)->delete();

            foreach ($bySection as $sectionId => $sectionRows) {
                $eligible = $sectionRows->filter(fn ($r) => !$r['is_na']);
                $nEligible = $eligible->count();

                $coverageMax = $nEligible * 3;
                $opennessMax = $nEligible * 5;

                $coverageActual = (float) $eligible->sum(fn ($r) => (float) ($r['coverage_sub_score'] ?? 0));
                $opennessActual = (float) $eligible->sum(fn ($r) => (float) ($r['opennes_sub_score'] ?? 0));

                $coverageRatio = $coverageMax > 0 ? ($coverageActual / $coverageMax) : 0;
                $opennessRatio = $opennessMax > 0 ? ($opennessActual / $opennessMax) : 0;

                $coverageSub = $coverageRatio * 100;
                $opennessSub = $opennessRatio * 100;
                $overall = ($coverageSub * 0.5) + ($opennessSub * 0.5);

                $rec = AssessmentSummary::create([
                    'assessment_country_id' => $assessmentCountry->id,
                    'section_id' => (int) $sectionId,
                    'coverage_max_score' => $coverageMax,
                    'coverage_actual_score' => $coverageActual,
                    'coverage_sub_score' => round($coverageSub, 2),
                    'opennes_max_score' => $opennessMax,
                    'opennes_actual_score' => $opennessActual,
                    'opennes_sub_score' => round($opennessSub, 2),
                    'overall_score' => round($overall, 2),
                ]);

                $summaries[] = $rec;
            }

            // Persist weighted score as section_id = 0.
            $sectionCount = count($summaries);
            $weightedCoverage = 0.0;
            $weightedOpenness = 0.0;
            if ($sectionCount > 0) {
                foreach ($summaries as $s) {
                    $weightedCoverage += ((float) $s->coverage_sub_score / $sectionCount);
                    $weightedOpenness += ((float) $s->opennes_sub_score / $sectionCount);
                }
            }
            $weightedOverall = (0.5 * $weightedCoverage) + (0.5 * $weightedOpenness);

            AssessmentSummary::create([
                'assessment_country_id' => $assessmentCountry->id,
                'section_id' => 0,
                'coverage_max_score' => 0,
                'coverage_actual_score' => 0,
                'coverage_sub_score' => round($weightedCoverage, 2),
                'opennes_max_score' => 0,
                'opennes_actual_score' => 0,
                'opennes_sub_score' => round($weightedOpenness, 2),
                'overall_score' => round($weightedOverall, 2),
            ]);
        });

        return $summaries;
    }
}
