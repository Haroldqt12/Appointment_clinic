<?php

use App\Models\AddDoctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AddDoctorController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('login'); 
});

Route::get('/redirect', function () {
    $user = Auth::user();

    // Check if the user is an admin or a regular user
    if ($user->role === 'admin') {
        return redirect('/Admin/Dashboard');  // Admin dashboard
    }

    return redirect('/user/dashboard');  // User dashboard
})->name('redirect');


Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/Admin/Dashboard', [DashboardController::class, 'index'])->name('mainpage');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    Route::get('/appointmentlist', [BookingController::class, 'index'])->name('appointmentlist');
    Route::post('/appointmentlist/{id}/confirm', [BookingController::class, 'confirm'])->name('appointmentlist.confirm');
    Route::get('/appointmentlist/available-slots', [BookingController::class, 'getAvailableTimeSlots'])->name('appointmentlist.available-slots');


    Route::get('/appointmentrecords', [BookingController::class, 'records'])->name('appointmentrecord');
    Route::get('/appointmentrecords/search', [BookingController::class, 'search'])->name('appointmentrecord.search');

    // for doctors
    Route::get('/AddDoctor', function () {
        return view('doctor_add');      
    })->name('AddDoctor');

    Route::post('/AddDoctor', [AddDoctorController::class, 'store'])->name('StoreDoctor');
    Route::get('/DoctorList', [AddDoctorController::class, 'list'])->name('DoctorList');
         
    Route::get('/DoctorRecord', [AddDoctorController::class, 'show'])->name('DoctorRecord');
    Route::get('/doctor/edit/{DoctorId}', [AddDoctorController::class, 'edit'])->name('DoctorEdit');
    Route::put('/doctor/update/{DoctorId}', [AddDoctorController::class, 'update'])->name('DoctorUpdate');
    Route::delete('/doctor/{id}', [AddDoctorController::class, 'destroy'])->name('DeleteDoctor');
    Route::get('/doctors/view/{id}', [AddDoctorController::class, 'view'])->name('DoctorView');
    
    //Doctor Availability
    Route::post('/doctor-availability/store', [DoctorAvailabilityController::class, 'store'])->name('DoctorAvailability.store');
    Route::get('/doctor-availability', [DoctorAvailabilityController::class, 'index'])->name('AvailabilityList');
    Route::get('/doctor-availability/{DoctorId}', [DoctorAvailabilityController::class, 'create'])->name('Availability');
    Route::delete('/doctor-availability/{AvailabilityId}', [DoctorAvailabilityController::class, 'destroy'])->name('DeleteDoctorAvailability');
    Route::put('/doctor-availability/update', [DoctorAvailabilityController::class, 'update'])->name('UpdateDoctorAvailability');
    Route::patch('/doctor-availability/toggle/{AvailabilityId}', [DoctorAvailabilityController::class, 'toggleStatus'])->name('ToggleDoctorAvailability');
    

    // for patient
    Route::get('/PatientList', function () {
        return view('patient_list'); 
    })->name('PatientList');    
    Route::get('/PatientRecord', function () {
        return view('patient_record'); 
    })->name('PatientRecord');
    Route::get('/AddPatient', function () {
        return view('patient_add'); 
    })->name('AddPatient');

    //Adding Patients
    Route::get('/patients', [PatientController::class, 'index'])->name('PatientList');
    Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');

  
        
    //Patient Edit Update Delete and Show
    Route::get('/patients/{id}/edit', [PatientController::class, 'edit'])->name('patient.edit');
    Route::put('/patients/{id}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{id}', [PatientController::class, 'destroy'])->name('patients.destroy');
    Route::get('/patients/{id}', [PatientController::class, 'show'])->name('patients.show');


    //Appointment
    Route::get('/Appointment', function () {
        return view('appointment.appointment_list'); 
    })->name('appointment.appointment_list');

    //Rescheduling appointment
    Route::post('/appointmentlist/reschedule/{id}', [BookingController::class, 'reschedule'])->name('appointment.reschedule');    


    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
});

Route::post('/AddPatient',[PatientController::class, 'store'])->name('StoredPatient');
Route::get('/notifications/read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.read');
Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');



Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/user/dashboard', function () {
        return view('user.dashboard'); 
    })->name('user.dashboard');

    Route::get('/About', function () {
        return view('user.About'); 
    })->name('user.About');



    Route::get('/Doctors', [AddDoctorController::class, 'DoctorView'])->name('user.Doctors'); 
  

    Route::get('/History', [BookingController::class, 'history'])->name('user.history');
    Route::delete('/booking/cancel/{id}', [BookingController::class, 'cancel'])->name('booking.cancel');


    Route::get('/booking', [AddDoctorController::class, 'booking'])->name('user.booking'); 
    Route::get('/booking/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/booking', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/booking/available-slots', [BookingController::class, 'getAvailableTimeSlots'])->name('booking.available-slots');

    
    Route::get('MyAccount', function () {
        return view('user.account'); 
    })->name('AccountDetails');

    Route::get('PatientInfo', [PatientController::class, 'fetchPatientInfo'])->name('PatientInfo'); 
});

Route::get('/monthly-appointments', [DashboardController::class, 'getMonthlyAppointments'])->name('monthly.appointments');
Route::get('/appointments/daily/{year}/{month}', [DashboardController::class, 'getAppointmentsByMonth'])->name('appointments.byMonth');
Route::get('/appointments/yearly/{year}', [DashboardController::class, 'getAppointmentsByYear'])->name('appointments.byYear');



require __DIR__.'/auth.php';

