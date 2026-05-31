<style>
    .gs-card {
        border: 1px solid #c9dbfb !important;
        box-shadow: 0 8px 20px rgba(45, 98, 181, 0.12) !important;
        color: #1f2e45;
    }
    .gs-title {
        color: #17488f;
    }
    .gs-subtitle {
        color: #365278 !important;
    }
    .gs-section-title {
        color: #17488f;
    }
    .gs-table-head th {
        background: #edf4ff;
        color: #123c7a;
    }
    .gs-alert-info {
        background: #edf4ff;
        border: 1px solid #c6dbfb;
        color: #1f2e45;
    }
    .gs-alert-primary {
        background: #e9f1ff;
        border: 1px solid #bcd3fa;
        color: #1f2e45;
    }
    .gs-welcome-img {
        width: 100%;
        max-width: 920px;
        border-radius: 12px;
        border: 1px solid #dbe7fb;
        display: block;
        margin: 0 auto;
    }
</style>

<section class="help-page py-2">
    <div class="container-fluid px-0">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card border-0 rounded-4 gs-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="mb-4">
                            <h1 class="fw-bold mb-2 gs-title">Getting Started</h1>
                            <p class="mb-0 gs-subtitle">
                                This page explains how to start using the Open Data Self-Assessment Portal after signing in.
                            </p>
                        </div>

                        <hr class="my-4">

                        <div class="mb-4">
                            <div class="mb-4">
                                <img src="{{ asset('img/help/welcome.png') }}" alt="Getting Started Welcome" class="gs-welcome-img">
                            </div>
                            <h4 class="fw-semibold mb-3 gs-section-title">1. After Signing In</h4>
                            <p>
                                After you successfully sign in to the portal, the system will guide you to the available
                                self-assessment. This may also happen after you successfully change your password when
                                signing in with a temporary password.
                            </p>
                            <p class="mb-0">
                                From this starting point, you can choose whether to go directly to the assessment form or
                                view a short guidance first.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 gs-section-title">2. Active Assessment Information</h4>
                            <p>
                                If there is an active assessment period, the portal will display information about the
                                assessment currently open for submission.
                            </p>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="gs-table-head">
                                        <tr>
                                            <th style="width: 30%;">Information</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Assessment Period</strong></td>
                                            <td>
                                                Shows the active self-assessment period, for example
                                                <strong>2026 Self-Assessment</strong>.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status</strong></td>
                                            <td>
                                                Shows whether the assessment is currently open for submission.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Available until</strong></td>
                                            <td>
                                                Shows the due date for completing or updating the assessment.
                                                After this date, access to modify the assessment may be closed.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 gs-section-title">3. Choosing How to Start</h4>
                            <p>
                                When an assessment is open, you will usually have two options to start:
                            </p>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="gs-table-head">
                                        <tr>
                                            <th style="width: 30%;">Option</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Take Assessment</strong></td>
                                            <td>
                                                Select this option if you would like to go directly to the assessment form
                                                and start reviewing, completing, or updating the assessment.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>View Quick Guide</strong></td>
                                            <td>
                                                Select this option if you would like to become familiar with the portal
                                                before starting. The Quick Guide provides a brief walkthrough of the main
                                                features and steps in the assessment form.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 gs-section-title">4. Take Assessment</h4>
                            <p>
                                The <strong>Take Assessment</strong> button brings you directly to the assessment form.
                                This option is suitable if you are already familiar with the portal or would like to start
                                working on the assessment immediately.
                            </p>
                            <p class="mb-0">
                                Please review the information carefully before saving or submitting any updates.
                                If more than one person has access to the same assessment, please coordinate internally
                                to avoid editing the assessment at the same time.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 gs-section-title">5. View Quick Guide</h4>
                            <p>
                                The <strong>View Quick Guide</strong> button opens a short guidance walkthrough.
                                This guidance refers to the Quick Help available on the assessment form screen.
                            </p>
                            <p>
                                The Quick Guide is useful for users who are accessing the portal for the first time or
                                would like to understand the main steps before completing the assessment.
                            </p>
                            <p class="mb-0">
                                You can reopen the Quick Guide anytime from the <strong>Help</strong> button located at
                                the bottom-right side of the assessment form page.
                            </p>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-semibold mb-3 gs-section-title">6. Closing the Start Screen</h4>
                            <p>
                                You may close the start screen by clicking the close button at the top-right corner.
                            </p>
                            <p class="mb-0">
                                Closing it will keep you in the portal. You can still access the assessment form,
                                dashboard, or available menus from the application.
                            </p>
                        </div>

                        <div class="alert rounded-3 mb-4 gs-alert-info">
                            <h5 class="fw-semibold mb-2">Tip</h5>
                            <p class="mb-0">
                                If this is your first time using the portal, it is recommended to select
                                <strong>View Quick Guide</strong> before starting the assessment.
                            </p>
                        </div>

                        <div class="alert rounded-3 mb-0 gs-alert-primary">
                            <h5 class="fw-semibold mb-2">Need Assistance?</h5>
                            <p class="mb-2">
                                If you have any questions or experience difficulties using the portal,
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
