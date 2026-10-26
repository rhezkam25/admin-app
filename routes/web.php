<?php

use App\Http\Controllers\ReportExportController;
use App\Livewire\Admin\AttendanceCorrections;
use App\Livewire\Admin\Employees;
use App\Livewire\Admin\Holidays;
use App\Livewire\Admin\Periods;
use App\Livewire\Admin\Schedules;
use App\Livewire\Admin\Settings;
use App\Livewire\Attendance\Clock;
use App\Livewire\Audit\Index as AuditIndex;
use App\Livewire\Dashboard;
use App\Livewire\Leave\Requests as LeaveRequests;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Overtime\Requests as OvertimeRequests;
use App\Livewire\Profile\ChangePassword;
use App\Livewire\Reports\Index as ReportsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/profile/password', ChangePassword::class)->name('password.change');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/attendance', Clock::class)->name('attendance');
    Route::get('/leave', LeaveRequests::class)->name('leave');
    Route::get('/overtime', OvertimeRequests::class)->name('overtime');
    Route::get('/notifications', NotificationsIndex::class)->name('notifications');

    Route::get('/reports', ReportsIndex::class)->name('reports');
    Route::get('/reports/attendance/excel', [ReportExportController::class, 'attendanceExcel'])->name('reports.attendance.excel');
    Route::get('/reports/attendance/pdf', [ReportExportController::class, 'attendancePdf'])->name('reports.attendance.pdf');
    Route::get('/reports/overtime/excel', [ReportExportController::class, 'overtimeExcel'])->name('reports.overtime.excel');
    Route::get('/reports/overtime/pdf', [ReportExportController::class, 'overtimePdf'])->name('reports.overtime.pdf');

    Route::get('/audit', AuditIndex::class)->name('audit');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/employees', Employees::class)->name('employees');
        Route::get('/attendance', AttendanceCorrections::class)->name('attendance');
        Route::get('/holidays', Holidays::class)->name('holidays');
        Route::get('/schedules', Schedules::class)->name('schedules');
        Route::get('/periods', Periods::class)->name('periods');
        Route::get('/settings', Settings::class)->name('settings');
    });
});
