<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\SectionApiController;
use App\Http\Controllers\Api\Admin\CategoryApiController;
use App\Http\Controllers\Api\Admin\IndicatorApiController;
use App\Http\Controllers\Api\Admin\SubIndicatorApiController;
use App\Http\Controllers\Api\Admin\UserApiController;
use App\Http\Controllers\Api\Trx\PeriodApiController;
use App\Http\Controllers\Api\Trx\CountryApiController;
use App\Http\Controllers\Api\Trx\FormApiController;
use App\Http\Controllers\Api\Trx\DashboardAssessmentApiController;
use App\Http\Controllers\Api\Internal\VersionApiController;

Route::get('/i/version', [VersionApiController::class, 'show']);

Route::middleware(['web', 'auth', 'force.password.change'])->group(function () {

    // Admin masters
    Route::prefix('adm')->middleware('admin.only')->group(function () {
        Route::get('/sections', [SectionApiController::class, 'index']);
        Route::post('/section', [SectionApiController::class, 'store']);
        Route::delete('/section/{id}', [SectionApiController::class, 'destroy']);

        Route::get('/categories', [CategoryApiController::class, 'index']);
        Route::post('/category', [CategoryApiController::class, 'store']);
        Route::delete('/category/{id}', [CategoryApiController::class, 'destroy']);

        Route::get('/indicators', [IndicatorApiController::class, 'index']);
        Route::post('/indicator', [IndicatorApiController::class, 'store']);
        Route::delete('/indicator/{id}', [IndicatorApiController::class, 'destroy']);

        Route::get('/sub-indicators', [SubIndicatorApiController::class, 'index']);
        Route::post('/sub-indicator', [SubIndicatorApiController::class, 'store']);
        Route::delete('/sub-indicator/{id}', [SubIndicatorApiController::class, 'destroy']);

        Route::get('/users', [UserApiController::class, 'index']);
        Route::get('/user/{id}', [UserApiController::class, 'show']);
        Route::post('/user', [UserApiController::class, 'store']);
        Route::put('/user/{id}', [UserApiController::class, 'update']);
        Route::delete('/user/{id}', [UserApiController::class, 'destroy']);
        Route::post('/users/generate-temp-passwords', [UserApiController::class, 'generateTemporaryPasswords']);
    });

    // Transactions
    Route::prefix('trx')->group(function () {
        Route::get('/periods', [PeriodApiController::class, 'index']);
        Route::get('/configurations', [PeriodApiController::class, 'configurations'])->middleware('admin.only');
        Route::get('/configuration/{configId}/rows', [PeriodApiController::class, 'configurationRows'])->middleware('admin.only');
        Route::get('/period/{periodId}/rows', [PeriodApiController::class, 'rows'])->middleware('admin.only');
        Route::post('/period', [PeriodApiController::class, 'store'])->middleware('admin.only');
        Route::put('/period/{periodId}', [PeriodApiController::class, 'close'])->middleware('admin.only');

        Route::get('/countries/template-prefixes/{assessmentCountryId}', [CountryApiController::class, 'templatePrefixes'])->middleware('admin.only');
        Route::get('/countries/{periodId}', [CountryApiController::class, 'index'])->middleware('admin.only');
        Route::post('/countries/{assessmentCountryId}/unlock', [CountryApiController::class, 'unlock'])->middleware('admin.only');
        Route::post('/countries/attach-template', [CountryApiController::class, 'attachTemplate'])->middleware('admin.only');
        Route::post('/countries/upload-template', [CountryApiController::class, 'uploadTemplate'])->middleware('admin.only');

        Route::get('/form', [FormApiController::class, 'show']);
        Route::get('/form/template/download', [FormApiController::class, 'downloadTemplate'])->name('api.trx.form.template.download');
        Route::get('/form/logs', [FormApiController::class, 'logs']);
        Route::post('/form', [FormApiController::class, 'update']);
        Route::post('/form/summary', [FormApiController::class, 'updateSummary']);
        Route::post('/form/upload', [FormApiController::class, 'uploadTemplate']);
        Route::post('/form/submit', [FormApiController::class, 'submit']);
        Route::get('/dashboard-assessments', [DashboardAssessmentApiController::class, 'index']);
    });
});
