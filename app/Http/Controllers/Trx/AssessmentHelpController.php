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
                'title' => 'Getting Started',
                'children' => [
                    ['key' => 'getting-started', 'title' => 'Getting Started'],
                ],
            ],
            [
                'title' => 'Authentication',
                'children' => [
                    ['key' => 'login', 'title' => 'Login'],
                    ['key' => 'change-password', 'title' => 'Change Password'],
                    ['key' => 'delegation', 'title' => 'Delegation'],
                ],
            ],
            [
                'title' => 'Dashboard',
                'children' => [
                    ['key' => 'dashboard', 'title' => 'Dashboard Overview'],
                ],
            ],
            [
                'title' => 'Assessment Form',
                'children' => [
                    ['key' => 'entry', 'title' => 'Entry form Table'],
                    ['key' => 'summary', 'title' => 'Summary'],
                ],
            ],
        ];
    }

    private function topics(): array
    {
        return [
            'getting-started' => [
                'title' => 'Getting Started',
                'description' => 'This page explains how to start using the Open Data Self-Assessment Portal after signing in.',
                'custom_view' => 'trx.partials.help_getting_started',
            ],
            'login' => [
                'title' => 'Login',
                'description' => 'This page provides guidance on how to access the Open Data Self-Assessment Portal.',
                'sections' => [
                    [
                        'heading' => '1. Registered Account',
                        'paragraphs' => [
                            'Access to the Open Data Self-Assessment Portal is provided only to users who have been registered by ASEANstats. Users may sign in using the email address or username registered by ASEANstats.',
                            'If you are unsure which email address or username to use, please contact ASEANstats for confirmation.',
                        ],
                    ],
                    [
                        'heading' => '2. How to Sign In',
                        'image' => '/img/help/login.png',
                        'bullets' => [
                            'Go to the portal login page.',
                            'Enter your registered email address or username in the Email or Username field.',
                            'Enter your password in the Password field.',
                            'Click the Sign In button to access the portal.',
                        ],
                    ],
                    [
                        'heading' => '3. Temporary Password',
                        'paragraphs' => [
                            'For newly registered users, ASEANstats will provide a temporary password through email. After signing in for the first time, users may be required to change their password before continuing to the assessment page.',
                            'Please keep your password confidential and do not share it with others.',
                        ],
                    ],
                    [
                        'heading' => '4. Remember Me',
                        'paragraphs' => [
                            'Users may select the Remember me option if they want the browser to remember their login session. This option should only be used on a personal or trusted device.',
                        ],
                    ],
                    [
                        'heading' => '5. Forgot Password',
                        'paragraphs' => [
                            'If you forget your password, click the Forgot Password? link on the login page and follow the instructions to reset your password.',
                            'If you do not receive the reset email or continue to experience issues, please contact ASEANstats.',
                        ],
                    ],
                    [
                        'heading' => 'Need Assistance?',
                        'paragraphs' => [
                            'For any questions or technical issues related to portal access, please contact ASEANstats.',
                        ],
                        'bullets' => [
                            'Email: stats@asean.org',
                        ]
                    ],
                ],
            ],
            'change-password' => [
                'title' => 'Change Password',
                'description' => 'This page explains how to change your password in the Open Data Self-Assessment Portal.',
                'sections' => [
                    [
                        'heading' => '1. When Do You Need to Change Your Password?',
                        'paragraphs' => [
                            'There are two situations where you may see the Change Password page.',
                        ],
                        'bullets' => [
                            'Forced Change Password: This appears when you are using a temporary password, usually after your account has been newly registered by ASEANstats or after your password has been reset. You must change your password before you can access the portal.',
                            'Regular Change Password: This page is used when you choose to change your password while already logged in to the portal. You may cancel the process or go to another page in the portal.',
                        ],
                    ],
                    [
                        'heading' => '2. Forced Change Password',
                        'paragraphs' => [
                            'If you are using a temporary password, the portal will require you to create a new password before you can continue. In this situation, access to other pages is restricted until the password has been successfully changed.',
                            'This is a security measure to ensure that only you know your personal password.',
                        ],
                        'image_after' => '/img/help/fchangep.png',
                        'image_after_width' => '75%',
                    ],
                    [
                        'heading' => '3. Regular Change Password',
                        'paragraphs' => [
                            'If you are already logged in to the portal, you may change your password at any time from the Change Password menu.',
                            'In this regular mode, you are not required to complete the process immediately. You may click Cancel or select another menu if you decide not to change your password.',
                        ],
                        'image_after' => '/img/help/changep.png',
                        'image_after_width' => '75%',
                    ],
                    [
                        'heading' => '4. How to Change Your Password',
                        'bullets' => [
                            'Enter your current password in the Current Password field.',
                            'Enter your new password in the New Password field.',
                            'Re-enter the same new password in the Confirm New Password field.',
                            'Click the Change Password button.',
                            'If the password is successfully changed, you can continue using the portal with your new password.',
                        ],
                    ],
                    [
                        'heading' => '5. Password Requirements',
                        'paragraphs' => [
                            'Your new password must be at least 8 characters long.',
                            'For better security, it is recommended to use a combination of letters, numbers, and symbols.',
                        ],
                    ],
                    [
                        'heading' => 'Security Tips',
                        'bullets' => [
                            'Use a strong password with a mix of letters, numbers, and symbols.',
                            'Do not reuse passwords from other accounts.',
                            'Keep your password confidential and do not share it with others.',
                        ],
                    ],
                    [
                        'heading' => 'Need Assistance?',
                        'paragraphs' => [
                            'If you cannot change your password or experience any issue, please contact ASEANstats.',
                        ],
                        'bullets' => [
                            'Email: stats@asean.org',
                        ],
                    ],
                ],
            ],
            'dashboard' => [
                'title' => 'Dashboard Overview',
                'description' => 'The Dashboard helps users monitor assessment progress and score trends.',
                'custom_view' => 'trx.partials.help_dashboard',
                'sections' => [
                    [
                        'heading' => 'Main Purpose',
                        'bullets' => [
                            'View your country performance at a glance.',
                            'Track completion and scoring status by section and period.',
                            'Open related pages for data entry and follow-up actions.',
                        ],
                    ],
                    [
                        'heading' => 'What You Can See',
                        'bullets' => [
                            'Key summary cards that show overall progress and score highlights.',
                            'Charts or tables for section-level score comparison.',
                            'Recent assessment context based on the selected period.',
                        ],
                    ],
                    [
                        'heading' => 'How to Use It Effectively',
                        'bullets' => [
                            'Review low-score sections first, then continue to Entry Form for updates.',
                            'Use filters or period selectors to compare results across assessment periods.',
                            'Use the Help button on the page if you need quick guidance for dashboard elements.',
                        ],
                    ],
                ],
            ],
            'delegation' => [
                'title' => 'Delegation',
                'description' => 'This page explains how the Delegation feature works in the Open Data Self-Assessment Portal.',
                'sections' => [
                    [
                        'heading' => '1. Purpose of the Delegation Feature',
                        'paragraphs' => [
                            'ASEANstats initially registers one or two focal points from each ASEAN Member State (AMS) in the Open Data Self-Assessment Portal. These registered users are considered the main focal points.',
                            'In practice, the assessment may be completed not only by the main focal point, but also by colleagues who assist in preparing or submitting the assessment. To support this process, the portal provides a Delegation feature.',
                            'Through this feature, a main focal point may create additional user accounts for colleagues who need access to the portal and can help complete the assessment.',
                        ],
                    ],
                    [
                        'heading' => '2. Who Can Use Delegation?',
                        'bullets' => [
                            'Main Focal Point: A user account initially registered by ASEANstats. The main focal point can add, edit, and delete delegation accounts.',
                            'Delegated User: A colleague added by the main focal point through the Delegation menu. Delegated users can access the portal using the email and password provided by the main focal point.',
                        ],
                    ],
                    [
                        'heading' => '3. Important Note on Shared Access',
                        'paragraphs' => [
                            'When delegation is used, more than one person may be able to access the portal for the same AMS. To avoid overwriting each other’s work, users should not edit the assessment at the same time.',
                            'If two or more users make changes simultaneously, one user’s changes may overwrite another’s. It is therefore recommended that the main focal point coordinate clearly with delegated users on who will update the assessment and when.',
                        ],
                    ],
                    [
                        'heading' => '4. Delegation List Page',
                        'paragraphs' => [
                            'The Delegation page displays the list of users who can access the portal for the same AMS.',
                            'The list generally includes the following information.',
                        ],
                        'image_after_paragraph' => '/img/help/delegate1.png',
                        'image_after_paragraph_index' => 1,
                        'image_after_paragraph_width' => '75%',
                        'bullets' => [
                            'No: sequence number.',
                            'Email: registered email address of the user.',
                            'Name: full name of the user.',
                            'Default: indicates whether the user is the main focal point registered by ASEANstats.',
                            'Action: available actions such as Edit or Delete.',
                        ],
                    ],
                    [
                        'heading' => '5. Adding a New Delegation',
                        'paragraphs' => [
                            'To add a colleague as a delegated user.',
                        ],
                        'bullets' => [
                            'Open the Delegation menu.',
                            'Click the Add Delegation button.',
                            'Complete the required information: Email (colleague email address), Name (full name), Password, and Confirm Password.',
                            'Click Add Delegation to save the new user account.',
                            'Share the registered email address and password with the delegated user so they can sign in.',
                            'Creating a delegation means registering a new user account in the system. The delegated user can then access the portal directly using the credentials provided by the main focal point.',
                        ],
                        'image_after_list' => [
                            [
                                'src' => '/img/help/delegate3.png',
                                'width' => '50%',
                            ],
                            [
                                'src' => '/img/help/delegate2.png',
                                'width' => '75%',
                            ],
                        ],
                    ],
                    [
                        'heading' => '6. Editing a Delegation',
                        'paragraphs' => [
                            'The main focal point may edit an existing delegation account when needed.',
                            'The following information may be updated: Name and Password.',
                            'This allows the main focal point to update a colleague’s details or reset the password if necessary.',
                        ],
                    ],
                    [
                        'heading' => '7. Deleting a Delegation',
                        'paragraphs' => [
                            'If a delegated user no longer needs access to the portal, the main focal point may remove the user by clicking Delete from the Delegation list.',
                            'Once deleted, the delegated user will no longer be able to sign in to the portal.',
                        ],
                    ],
                    [
                        'heading' => '8. Good Practice for Using Delegation',
                        'bullets' => [
                            'Only create delegation accounts for colleagues who need to assist with the assessment.',
                            'Coordinate internally to ensure that only one person edits the assessment at a time.',
                            'Keep delegation passwords secure and share them only with the intended user.',
                            'Delete delegation accounts that are no longer needed.',
                        ],
                    ],
                    [
                        'heading' => 'Need Assistance?',
                        'paragraphs' => [
                            'If you have any questions or experience difficulties using the Delegation feature, please contact ASEANstats.',
                        ],
                        'bullets' => [
                            'Email: stats@asean.org',
                        ],
                    ],
                ],
            ],
            'entry' => [
                'title' => 'Entry Form Table',
                'description' => 'Guidance below follows sheet "Scoring Guideline" in ACSS Open Data Assessment workbook.',
                'note_html' => 'For detail scoring guideline, please download the file <a href="/templates/ACSS%20Open%20Data%20Assesment%20Tools.xlsx" target="_blank" rel="noopener">assessment template</a>.',
                'sections' => [
                    [
                        'heading' => 'Series',
                        'paragraphs' => [
                            'Series is the Coverage input. Enter published years as comma-separated values (example: 2019,2020,2021) or use NA for indicators not applicable to the country.',
                            'Users can enter years with comma-separated values, year ranges with hyphen (example: 2018-2020), or a combination (example: 2016,2018-2020,2023).',
                            'When users leave the Series field, the input is normalized into comma-separated years, duplicates are removed, and years are sorted from smallest to largest.',
                            'For non-calendar data (example: 2012/2013), count it as one year. Overlapping entries (example: 2013 and 2013/14) must not be double-counted.',
                        ],
                        'bullets' => [
                            'C1 score: 1 if published data and required disaggregation/breakdown are available, 0 if not available.',
                            'C2 score: 1 for at least 3 of last 5 years, 0.5 for 1-2 years, 0 if none.',
                            'C3 score: 1 for at least 6 of last 10 years, 0.5 for 3-5 years, 0 for 2 or fewer.',
                            'Important rule: If C1 is 0, all elements C1-C3 and O1-O5 are scored 0 for that indicator.',
                        ],
                    ],
                    [
                        'heading' => 'Coverage',
                        'paragraphs' => [
                            'Coverage is represented by C1, C2, and C3 derived from Series and required disaggregation/breakdown availability.',
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
                            'Openness is assessed through five elements (O1-O5) using data availability with required disaggregation/breakdown.',
                        ],
                        'bullets' => [
                            'O1 Machine Readability: 1 if available in machine-readable format (XLSX/CSV/TXT/JSON/XML); 0 otherwise. PDF tables and scanned text are not machine-readable.',
                            'O2 Non-Proprietary: 1 if available in non-proprietary format (XLSX/CSV/XML/TXT/JSON); 0 otherwise. XLS/Stata/SAS/SPSS/DOC/PPT are proprietary. ZIP does not reduce score; RAR-only gets 0.',
                            'O3 Download Options: 1 if both bulk and API/user-selectable download exist, 0.5 if only one exists, 0 if none.',
                            'O4 Metadata Availability: 1 if all required metadata fields are present, 0.5 if at least 5 fields, 0 if 4 or fewer. Required fields include: definition, frequency, unit, disaggregation level, data source, availability period, direct URL, download formats, and terms-of-use URL.',
                            'O5 Terms of Use: 1 for open, 0.5 for semi-restrictive, 0 for restrictive/no terms.',
                        ],
                    ],
                    [
                        'heading' => 'URL (Evidence & Notes)',
                        'paragraphs' => [
                            'Provide dataset link(s) and supporting notes used for assessment. Assessment is based on data availability on the NSO website or linked line-ministry sources.',
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
