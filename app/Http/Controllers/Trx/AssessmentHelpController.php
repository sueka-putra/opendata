<?php

namespace App\Http\Controllers\Trx;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AssessmentHelpController extends Controller
{
    public function index(Request $request)
    {
        $topics = $this->topics();
        $topicKeys = array_keys($topics);
        $defaultTopic = $topicKeys[0] ?? 'entry';

        $activeTopic = strtolower((string) $request->query('topic', $defaultTopic));
        if (!isset($topics[$activeTopic])) {
            $activeTopic = $defaultTopic;
        }

        if ($request->boolean('partial')) {
            return view('trx.partials.assessment_help_topic', [
                'topic' => $topics[$activeTopic],
            ]);
        }

        return view('trx.assessment_entry_help', [
            'topics' => $topics,
            'activeTopic' => $activeTopic,
            'topicGroups' => $this->topicGroups(),
        ]);
    }

    private function topicGroups(): array
    {
        return [
            [
                'title' => 'Assessment Form',
                'children' => [
                    ['key' => 'entry', 'title' => 'Entry form'],
                    ['key' => 'summary', 'title' => 'Summary'],
                ],
            ],
        ];
    }

    private function topics(): array
    {
        return [
            'entry' => [
                'title' => 'Entry form',
                'sections' => [
                    [
                        'heading' => 'Series',
                        'paragraphs' => [
                            'Series is the Coverage input. Fill years as comma-separated values (example: 2019,2020,2021) or use NA if the indicator is not applicable.',
                            'Users can enter years with comma-separated values, year ranges with hyphen (example: 2018-2020), or a combination (example: 2016,2018-2020,2023).',
                            'When users leave the Series field, the input is normalized into comma-separated years, duplicates are removed, and years are sorted from smallest to largest.',
                        ],
                        'bullets' => [
                            'C1 score: 1 if at least one valid year exists, otherwise 0.',
                            'C2 score: 1 for at least 3 of last 5 years, 0.5 for 1-2 years, 0 for none.',
                            'C3 score: 1 for at least 6 of last 10 years, 0.5 for 3-5 years, 0 for 2 or fewer.',
                        ],
                    ],
                    [
                        'heading' => 'Coverage',
                        'paragraphs' => [
                            'Coverage in this table is represented by Series-based scoring components.',
                        ],
                        'bullets' => [
                            'All: total number of valid years entered in Series.',
                            '5: number of entered years in the last 5 years (based on reference year).',
                            '10: number of entered years in the last 10 years (based on reference year).',
                            'C1: data availability indicator.',
                            'C2: continuity for recent 5-year period.',
                            'C3: continuity for recent 10-year period.',
                        ],
                    ],
                    [
                        'heading' => 'Openness',
                        'paragraphs' => [
                            'Openness is assessed through five elements.',
                        ],
                        'bullets' => [
                            'O1 Machine Readability: 1 for machine-readable formats (XLSX/CSV/JSON/XML/TXT), else 0.',
                            'O2 Non-Proprietary: 1 when non-proprietary format is available, else 0.',
                            'O3 Download Options: 1 if both bulk and API/user-selectable download exist, 0.5 if only one exists, 0 if none.',
                            'O4 Metadata Availability: 1 if all required metadata fields are present, 0.5 if at least 5 fields, 0 if 4 or fewer.',
                            'O5 Terms of Use: 1 for open terms, 0.5 for semi-restrictive terms, 0 for restrictive/no terms.',
                        ],
                    ],
                    [
                        'heading' => 'URL (Evidence & Notes)',
                        'paragraphs' => [
                            'Provide dataset link(s) used for assessment in the URL/Evidence field.',
                        ],
                        'bullets' => [
                            'Use direct and accessible links when possible.',
                            'Ensure the link matches selected indicator/disaggregation.',
                            'Add supporting explanation in remarks when needed.',
                        ],
                    ],
                ],
            ],
            'summary' => [
                'title' => 'Summary',
                'sections' => [
                    [
                        'heading' => 'Per Section - Coverage',
                        'paragraphs' => [
                            'Coverage values are aggregated per section from all visible row scores in that section.',
                        ],
                        'bullets' => [
                            'Coverage Max Score: total maximum points for coverage components in the section.',
                            'Coverage Actual Score: sum of earned coverage points in the section.',
                            'Coverage Sub Score: Coverage Actual Score / Coverage Max Score (shown as ratio).',
                        ],
                    ],
                    [
                        'heading' => 'Per Section - Openness',
                        'paragraphs' => [
                            'Openness values are also aggregated per section from row-level openness components.',
                        ],
                        'bullets' => [
                            'Openness Max Score: total maximum openness points in the section.',
                            'Openness Actual Score: sum of earned openness points in the section.',
                            'Openness Sub Score: Openness Actual Score / Openness Max Score (shown as ratio).',
                        ],
                    ],
                    [
                        'heading' => 'Per Section - Overall Score',
                        'paragraphs' => [
                            'Overall Score for each section is derived from the two sub-scores.',
                        ],
                        'bullets' => [
                            'Overall Score = (Coverage Sub Score + Openness Sub Score) / 2.',
                            'Progress indicates completion ratio of required inputs within the section.',
                        ],
                    ],
                    [
                        'heading' => 'Weighted Score Row',
                        'paragraphs' => [
                            'The final Weighted Score row combines all sections with their section weight.',
                        ],
                        'bullets' => [
                            'Weighted Coverage Sub Score = sum(Section Coverage Sub Score x Section Weight).',
                            'Weighted Openness Sub Score = sum(Section Openness Sub Score x Section Weight).',
                            'Weighted Overall Score = sum(Section Overall Score x Section Weight).',
                        ],
                    ],
                ],
            ],
        ];
    }
}
