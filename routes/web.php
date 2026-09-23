<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\FreeCoursesController;
use App\Http\Controllers\Leader;
use App\Http\Controllers\LeaderSignupController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ParticipantRegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/scanners/{scanner:slug}', [PageController::class, 'scanner'])->name('scanners.show');
Route::get('/leaders/{member:slug}', [PageController::class, 'member'])->name('team.show');
Route::get('/locale/{locale}', [PageController::class, 'locale'])->whereIn('locale', ['en', 'ar'])->name('locale');

Route::get('/free-courses', [FreeCoursesController::class, 'index'])->name('free-courses');
Route::get('/app', [PageController::class, 'app'])->name('app.download');
Route::post('/free-courses/unlock', [FreeCoursesController::class, 'unlock'])->middleware('throttle:free-courses')->name('free-courses.unlock');
Route::get('/free-courses/video/{video}', [FreeCoursesController::class, 'stream'])->name('free-courses.video');

/*
|--------------------------------------------------------------------------
| Competition — public
|--------------------------------------------------------------------------
*/
Route::get('/competition', [CompetitionController::class, 'show'])->name('competition');

// Participant registration (QR / referral link lands here: /register?ref=LDR-XXXXXXXX)
Route::get('/register', [ParticipantRegistrationController::class, 'create'])->middleware('throttle:code-lookup')->name('register');
Route::post('/register', [ParticipantRegistrationController::class, 'store'])->middleware('throttle:registrations')->name('register.store');
Route::get('/registration/{token}', [ParticipantRegistrationController::class, 'result'])->where('token', '[A-Za-z0-9]{40}')->name('registration.result');

// Leader registration
Route::middleware('guest')->group(function () {
    Route::get('/competition/join', [LeaderSignupController::class, 'create'])->name('leader.signup');
    Route::post('/competition/join', [LeaderSignupController::class, 'store'])->middleware('throttle:leader-signup')->name('leader.signup.store');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.attempt');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Lightweight polling endpoints (real-time without websocket infrastructure)
Route::prefix('live')->middleware('throttle:live')->name('live.')->group(function () {
    Route::get('/competition', [LiveController::class, 'competition'])->name('competition');
    Route::get('/registration/{token}', [LiveController::class, 'registration'])->where('token', '[A-Za-z0-9]{40}')->name('registration');
});

/*
|--------------------------------------------------------------------------
| Leader area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'leader'])->prefix('leader')->name('leader.')->group(function () {
    Route::get('/', [Leader\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/live', [Leader\DashboardController::class, 'live'])->middleware('throttle:live')->name('live');
    Route::post('/photo', [Leader\DashboardController::class, 'photo'])->middleware('throttle:10,1')->name('photo');
    Route::get('/qr.svg', [Leader\DashboardController::class, 'qrSvg'])->name('qr.svg');
    Route::get('/qr.png', [Leader\DashboardController::class, 'qrPng'])->name('qr.png');
});

/*
|--------------------------------------------------------------------------
| Admin dashboard (authorization enforced by gates/policies on every action)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/live', [Admin\DashboardController::class, 'live'])->middleware('throttle:live')->name('live');

    // Registrations review
    Route::get('/registrations', [Admin\RegistrationController::class, 'index'])->name('registrations.index');
    Route::get('/registrations/{registration}', [Admin\RegistrationController::class, 'show'])->name('registrations.show');
    Route::get('/registrations/{registration}/transfer', [Admin\RegistrationController::class, 'transfer'])->name('registrations.transfer');
    Route::post('/registrations/{registration}/accept', [Admin\RegistrationController::class, 'accept'])->name('registrations.accept');
    Route::post('/registrations/{registration}/reject', [Admin\RegistrationController::class, 'reject'])->name('registrations.reject');

    // Leaders
    Route::resource('leaders', Admin\LeaderController::class);
    Route::get('/leaders/{leader}/qr.svg', [Admin\LeaderController::class, 'qr'])->name('leaders.qr');
    Route::post('/leaders/{leader}/approve', [Admin\LeaderController::class, 'approve'])->name('leaders.approve');
    Route::post('/leaders/{leader}/reject', [Admin\LeaderController::class, 'reject'])->name('leaders.reject');

    // Leaderboard
    Route::get('/leaderboard', [Admin\LeaderboardController::class, 'index'])->name('leaderboard');

    // Competition, rounds & timer
    Route::get('/competition', [Admin\CompetitionController::class, 'index'])->name('competition.index');
    Route::get('/competitions/create', [Admin\CompetitionController::class, 'create'])->name('competitions.create');
    Route::post('/competitions', [Admin\CompetitionController::class, 'store'])->name('competitions.store');
    Route::get('/competitions/{competition}/edit', [Admin\CompetitionController::class, 'edit'])->name('competitions.edit');
    Route::put('/competitions/{competition}', [Admin\CompetitionController::class, 'update'])->name('competitions.update');
    Route::post('/competitions/{competition}/rounds', [Admin\RoundController::class, 'store'])->name('rounds.store');
    Route::get('/rounds/{round}', [Admin\RoundController::class, 'show'])->name('rounds.show');
    Route::post('/rounds/{round}/start', [Admin\RoundController::class, 'start'])->name('rounds.start');
    Route::post('/rounds/{round}/pause', [Admin\RoundController::class, 'pause'])->name('rounds.pause');
    Route::post('/rounds/{round}/resume', [Admin\RoundController::class, 'resume'])->name('rounds.resume');
    Route::post('/rounds/{round}/finish', [Admin\RoundController::class, 'finish'])->name('rounds.finish');
    Route::post('/rounds/{round}/adjust', [Admin\RoundController::class, 'adjust'])->name('rounds.adjust');
    Route::post('/rounds/{round}/registration', [Admin\RoundController::class, 'registration'])->name('rounds.registration');
    Route::post('/rounds/{round}/recalculate', [Admin\RoundController::class, 'recalculate'])->name('rounds.recalculate');

    // Audit trail (read-only by design)
    Route::get('/audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit.index');

    // Admin accounts
    Route::resource('users', Admin\UserController::class)->except(['show']);

    // Site settings & content
    Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::resource('scanners', Admin\ScannerController::class)->except(['show']);
    Route::resource('companies', Admin\CompanyController::class)->except(['show']);
    Route::put('/companies-section', [Admin\CompanyController::class, 'section'])->name('companies.section');
    Route::resource('team', Admin\TeamMemberController::class)->except(['show'])->parameters(['team' => 'member']);
    Route::get('/courses', [Admin\CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}/edit', [Admin\CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [Admin\CourseController::class, 'update'])->name('courses.update');
    Route::get('/gallery', [Admin\GalleryController::class, 'index'])->name('gallery.index');
    Route::post('/gallery', [Admin\GalleryController::class, 'store'])->name('gallery.store');
    Route::delete('/gallery/{image}', [Admin\GalleryController::class, 'destroy'])->name('gallery.destroy');
});
