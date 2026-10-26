<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AttendanceLocationController;
use App\Http\Controllers\AttendanceSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecognitionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryComponentController;
use App\Http\Controllers\VisitAttendanceController;
use App\Http\Controllers\WorkScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/notifications/{id}', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::middleware('admin')->prefix('attendance/corrections')->name('attendance.corrections.')->group(function () {
        Route::get('/create', [AttendanceCorrectionController::class, 'create'])->name('create');
        Route::post('/', [AttendanceCorrectionController::class, 'store'])->name('store');
        Route::get('/{attendance}/edit', [AttendanceCorrectionController::class, 'edit'])->name('edit');
        Route::put('/{attendance}', [AttendanceCorrectionController::class, 'update'])->name('update');
    });
    Route::get('/attendance/{attendance}', [AttendanceController::class, 'show'])->whereNumber('attendance')->name('attendance.show');
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
    Route::get('/attendance/settings', [AttendanceSettingController::class, 'index'])->middleware('admin')->name('attendance.settings');
    Route::put('/attendance/settings', [AttendanceSettingController::class, 'update'])->middleware('admin')->name('attendance.settings.update');
    Route::middleware('admin')->prefix('attendance/locations')->name('attendance.locations.')->group(function () {
        Route::get('/', [AttendanceLocationController::class, 'index'])->name('index');
        Route::post('/', [AttendanceLocationController::class, 'store'])->name('store');
        Route::put('/{location}', [AttendanceLocationController::class, 'update'])->name('update');
        Route::patch('/{location}/activate', [AttendanceLocationController::class, 'activate'])->name('activate');
        Route::delete('/{location}', [AttendanceLocationController::class, 'destroy'])->name('destroy');
    });
    Route::patch('/attendance/{attendance}/checkout-approval', [AttendanceController::class, 'updateCheckoutApproval'])->middleware('admin')->name('attendance.approval');
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leave.index');
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('leave.show');
    Route::get('/leave-requests/{leaveRequest}/attachment', [LeaveRequestController::class, 'attachment'])->name('leave.attachment');
    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->name('leave.store');
    Route::put('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'update'])->name('leave.update');
    Route::delete('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'destroy'])->name('leave.destroy');
    Route::patch('/leave-requests/{leaveRequest}/review', [LeaveRequestController::class, 'review'])->middleware('admin')->name('leave.review');
    Route::get('/recognition', [RecognitionController::class, 'index'])->name('recognition.index');
    Route::get('/visits', [VisitAttendanceController::class, 'index'])->name('visits.index');
    Route::post('/visits', [VisitAttendanceController::class, 'store'])->name('visits.store');
    Route::get('/visits/{visit}', [VisitAttendanceController::class, 'show'])->whereNumber('visit')->name('visits.show');
    Route::put('/visits/{visit}', [VisitAttendanceController::class, 'update'])->whereNumber('visit')->name('visits.update');
    Route::delete('/visits/{visit}', [VisitAttendanceController::class, 'destroy'])->name('visits.destroy');
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->whereNumber('payroll')->name('payroll.show');
    Route::get('/payroll/{payroll}/pdf', [PayrollController::class, 'exportPdf'])->whereNumber('payroll')->name('payroll.pdf');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/profil-saya', function () {
        return redirect()->route('profile');
    });
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/attendance', [ReportController::class, 'attendance'])->name('attendance');
        Route::get('/leaves', [ReportController::class, 'leave'])->name('leaves');
        Route::get('/payroll', [ReportController::class, 'payroll'])->name('payroll');
        Route::get('/reward-punishment', [ReportController::class, 'rewardPunishment'])->name('reward-punishment');
        Route::get('/employees', [ReportController::class, 'employees'])->name('employees');
        Route::get('/print', [ReportController::class, 'print'])->name('print');
        Route::get('/pdf', [ReportController::class, 'exportPdf'])->name('pdf');
        Route::get('/excel', [ReportController::class, 'exportExcel'])->name('excel');
    });
    Route::middleware('admin')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::post('/employees/{employee}/account', [EmployeeController::class, 'storeAccount'])->name('employees.account');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::prefix('admin/karyawan')->name('admin.employees.')->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('index');
            Route::get('/create', [EmployeeController::class, 'create'])->name('create');
            Route::post('/', [EmployeeController::class, 'store'])->name('store');
            Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
            Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
            Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
            Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
        });
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::patch('/payroll/{payroll}/status', [PayrollController::class, 'updateStatus'])->name('payroll.status');
        Route::get('/positions', [PositionController::class, 'index'])->name('positions.index');
        Route::post('/positions', [PositionController::class, 'store'])->name('positions.store');
        Route::put('/positions/{position}', [PositionController::class, 'update'])->name('positions.update');
        Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->name('positions.destroy');
        Route::get('/work-schedules', [WorkScheduleController::class, 'index'])->name('work-schedules.index');
        Route::post('/work-schedules', [WorkScheduleController::class, 'store'])->name('work-schedules.store');
        Route::put('/work-schedules/{schedule}', [WorkScheduleController::class, 'update'])->name('work-schedules.update');
        Route::delete('/work-schedules/{schedule}', [WorkScheduleController::class, 'destroy'])->name('work-schedules.destroy');
        Route::get('/salary-components', [SalaryComponentController::class, 'index'])->name('salary-components.index');
        Route::post('/salary-components', [SalaryComponentController::class, 'store'])->name('salary-components.store');
        Route::put('/salary-components/{salaryComponent}', [SalaryComponentController::class, 'update'])->name('salary-components.update');
        Route::delete('/salary-components/{salaryComponent}', [SalaryComponentController::class, 'destroy'])->name('salary-components.destroy');
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
    Route::middleware('admin')->prefix('holidays')->name('holidays.')->group(function () {
        Route::get('/', [HolidayController::class, 'index'])->name('index');
        Route::post('/', [HolidayController::class, 'store'])->name('store');
        Route::put('/{holiday}', [HolidayController::class, 'update'])->name('update');
        Route::delete('/{holiday}', [HolidayController::class, 'destroy'])->name('destroy');
    });
    Route::middleware('admin')->group(function () {
        Route::post('/recognition/rewards', [RecognitionController::class, 'storeReward'])->name('recognition.rewards.store');
        Route::post('/recognition/auto-points', [RecognitionController::class, 'generateAutoPoints'])->name('recognition.auto-points');
        Route::post('/recognition/punishments', [RecognitionController::class, 'storePunishment'])->name('recognition.punishments.store');
        Route::delete('/recognition/rewards/{reward}', [RecognitionController::class, 'destroyReward'])->name('recognition.rewards.destroy');
        Route::delete('/recognition/punishments/{punishment}', [RecognitionController::class, 'destroyPunishment'])->name('recognition.punishments.destroy');
    });
});

Route::get('/{page}', [HomeController::class, 'page'])
    ->whereIn('page', ['tentang', 'profil-lkd', 'profil', 'kegiatan', 'program', 'potensi', 'berita', 'galeri', 'kontak']);
