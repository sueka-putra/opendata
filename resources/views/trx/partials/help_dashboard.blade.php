<style>
    .db-card {
        border: 1px solid #c9dbfb !important;
        box-shadow: 0 8px 20px rgba(45, 98, 181, 0.12) !important;
        color: #1f2e45;
    }
    .db-title {
        color: #17488f;
    }
    .db-subtitle {
        color: #365278 !important;
    }
    .db-section-title {
        color: #17488f;
    }
    .db-table-head th {
        background: #edf4ff;
        color: #123c7a;
    }
    .db-alert-info {
        background: #edf4ff;
        border: 1px solid #c6dbfb;
        color: #1f2e45;
    }
    .db-alert-warning {
        background: #fff6e8;
        border: 1px solid #fde1b4;
        color: #5a3c00;
    }
    .db-alert-primary {
        background: #e9f1ff;
        border: 1px solid #bcd3fa;
        color: #1f2e45;
    }
</style>

<section class="help-page py-2">
    <div class="container-fluid px-0">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card border-0 rounded-4 db-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="mb-4">
                            <h1 class="fw-bold mb-2 db-title">Dashboard</h1>
                            <p class="mb-0 db-subtitle">
                                The Dashboard is the landing page for accessing the assessment, monitoring submission
                                progress, and reviewing score trends across assessment periods.
                            </p>
                        </div>

                        <hr class="my-4">

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 db-section-title">1. Active Assessment</h4>
                            <p>
                                When there is an active assessment period, the Dashboard displays a highlighted
                                <strong>Active Assessment</strong> section at the top of the page. This section serves
                                as a reminder that an assessment is currently open and should be completed or updated
                                before the due date.
                            </p>
                            <p>
                                The active assessment section shows the current assessment period and provides a
                                <strong>Take Assessment</strong> button.
                            </p>
                            <p class="mb-0">
                                Click <strong>Take Assessment</strong> to go directly to the active assessment form.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 db-section-title">2. Assessment Histories</h4>
                            <p>
                                The <strong>Assessment Histories</strong> table lists all assessment periods available
                                for your AMS, including the active period and previous completed periods.
                            </p>
                            <p class="mb-0">
                                Through this table, users can access the active assessment, review past assessments,
                                and compare progress and scores across different assessment periods.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 db-section-title">3. Information in the Assessment Histories Table</h4>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="db-table-head">
                                        <tr>
                                            <th style="width: 28%;">Column</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Assessments</strong></td>
                                            <td>
                                                Shows the name of the assessment period, for example
                                                <strong>2026 Self-Assessment</strong>.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Period Status</strong></td>
                                            <td>
                                                Indicates whether the assessment period is currently
                                                <strong>Open</strong> or has been <strong>Completed</strong>.
                                                An open period means the assessment can still be edited and submitted.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Progress</strong><br>
                                                <span class="text-muted">Submission Status</span>
                                            </td>
                                            <td>
                                                Shows the submission status of the assessment. The status may be
                                                <strong>In-progress</strong>, <strong>Submitted</strong>, or
                                                <strong>Not-Submitted</strong>. <strong>Not-Submitted</strong> means
                                                the assessment was not submitted before the assessment period was closed.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Progress</strong><br>
                                                <span class="text-muted">Completion Percentage</span>
                                            </td>
                                            <td>
                                                Shows the percentage of assessment items that have been completed.
                                                This helps users monitor how much of the assessment has been filled in.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Coverage Sub Score</strong></td>
                                            <td>
                                                Shows the score related to data availability or coverage. This reflects
                                                the extent to which the required indicators, disaggregations, and data
                                                series are available for the assessment period.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Openness Sub Score</strong></td>
                                            <td>
                                                Shows the score related to data openness. This reflects the extent to
                                                which available data are provided in open and accessible formats,
                                                including aspects such as machine readability, download options,
                                                metadata availability, and terms of use.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Overall Score</strong></td>
                                            <td>
                                                Shows the combined result of the Coverage and Openness components.
                                                This score provides an overall view of the open data assessment result
                                                for the selected period.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Action</strong></td>
                                            <td>
                                                Provides the available action for each assessment period.
                                                <strong>Assess</strong> is shown for an active period that can still be
                                                edited or submitted. <strong>View</strong> is shown for previous or
                                                closed periods that can only be reviewed.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 db-section-title">4. Accessing the Active Assessment</h4>
                            <p>
                                When the assessment period is open, users can click <strong>Take Assessment</strong>
                                from the Active Assessment section or <strong>Assess</strong> from the Assessment
                                Histories table.
                            </p>
                            <p class="mb-0">
                                Both buttons will open the active assessment form, where users can review, complete,
                                update, and submit the assessment before the period is closed.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 db-section-title">5. Viewing Previous Assessments</h4>
                            <p>
                                For completed assessment periods, users can click <strong>View</strong> to review the
                                previous assessment record.
                            </p>
                            <p class="mb-0">
                                Previous assessments are provided for reference and comparison only. They cannot be
                                edited after the assessment period has been closed.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 db-section-title">6. Score Trend Overview</h4>
                            <p>
                                The <strong>Score Trend Overview</strong> chart shows the movement of scores across
                                available assessment periods.
                            </p>
                            <p>The chart displays the trend of:</p>
                            <ul class="ps-3">
                                <li><strong>Coverage Sub Score</strong></li>
                                <li><strong>Openness Sub Score</strong></li>
                                <li><strong>Overall Score</strong></li>
                            </ul>
                            <p class="mb-0">
                                This chart helps users see whether the scores have improved, declined, or remained
                                stable over time.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 db-section-title">7. Section Score Comparison</h4>
                            <p>
                                The <strong>Section Score Comparison</strong> chart shows the overall score by section,
                                grouped by assessment year.
                            </p>
                            <p>
                                The sections may include areas such as socio-demographic indicators, macroeconomic
                                indicators, trade and investment, and connectivity and environment indicators.
                            </p>
                            <p class="mb-0">
                                This chart can help users identify which sections have relatively higher scores and
                                which sections may need further improvement in future assessment periods.
                            </p>
                        </div>

                        <div class="alert rounded-3 mb-0 db-alert-primary">
                            <h5 class="fw-semibold mb-2">Need Assistance?</h5>
                            <p class="mb-2">
                                If you have any questions or experience difficulties using the Dashboard,
                                please contact ASEANstats.
                            </p>
                            <p class="mb-0">
                                <strong>ASEANstats</strong><br>
                                Email: <a href="mailto:stats@asean.org">stats@asean.org</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
