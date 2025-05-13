<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; 
use App\Models\Booking;
use App\Models\Patient;
use App\Models\AddDoctor;
use App\Models\DoctorAvailability;
use App\Models\AppointmentRecord;
use App\Models\User;
use App\Notifications\NewAppointmentNotification;
use App\Notifications\AppointmentConfirmedNotification;
use App\Notifications\RescheduledAppointmentNotification;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function create()
    {
       
        $doctors = AddDoctor::all();
        $patient = Patient::where('user_id', Auth::id())->first();

        if (!$patient) {
            return redirect()->route('AccountDetails')->with('error', 'Please complete your patient information first.');
        }

        return view('user.booking', compact('doctors', 'patient'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:add_doctors,DoctorId',
            'date' => 'required|date',
            'time' => 'required',
            'concern' => 'required|string|max:255',
        ]);
    
        $patient = Patient::where('user_id', Auth::id())->first();
    
        if (!$patient) {
            return redirect()->route('AccountDetails')->with('error', 'Please complete your patient information first.');
        }

        $day = Carbon::parse($request->date)->format('l'); 
        $isAvailable = DoctorAvailability::where('DoctorId', $request->doctor_id)
            ->where('day', $day)
            ->where('status', true)
            ->exists();
    
        if (!$isAvailable) {
            return redirect()->back()->with('error', 'This doctor is not available on the selected date.')->withInput();
        }
    
        // 🚀 Check if any booking already exists for that doctor/date/time
        $existingBooking = Booking::where('doctor_id', $request->doctor_id)
            ->where('date', $request->date)
            ->where('time', $request->time)
            ->whereIn('status', ['pending', 'confirmed']) 
            ->first();
    
        if ($existingBooking) {
            return redirect()->back()->with('error', 'Sorry, the selected time slot is already reserved. Please choose another time.');
        }
    
        // ✅ No conflicting booking found, safe to create
        $booking = Booking::create([
            'patient_id' => $patient->id,
            'doctor_id' => $request->doctor_id,
            'date' => $request->date,
            'time' => $request->time,
            'concern' => $request->concern,
            'status' => 'pending',
        ]);
        $admin = User::where('role', 'admin')->first();
        $admin?->notify(new NewAppointmentNotification($booking));
        return redirect()->route('user.history')->with('success', 'Appointment request submitted successfully!');
    }
    

    public function history()
    {
        $user = Auth::user();
        $bookings = Booking::whereHas('patient', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with('doctor', 'patient.user')
        ->orderBy('created_at', 'desc')
        ->paginate(10); // Add pagination
    
        return view('user.history', compact('bookings'));
    }

    
    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->status = 'cancelled';
        $booking->save();

        AppointmentRecord::create([
            'booking_id' => $booking->BookingId,
            'status' => 'cancelled',
        ]);

        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notifications()
                ->where('data->booking_id', $booking->BookingId)
                ->delete();
        }

        return redirect()->back()->with('success', 'Appointment cancelled successfully.');
    }

    public function getAvailableTimeSlots(Request $request)
    {
        $doctorId = $request->query('doctor_id');
        $date = $request->query('date');
    
        $availability = DoctorAvailability::where('DoctorId', $doctorId)
            ->where('day', date('l', strtotime($date)))
            ->first();
    
        if (!$availability) {
            return response()->json([]);
        }
    
        $startTime = Carbon::createFromFormat('H:i:s', $availability->start_time);
        $endTime = Carbon::createFromFormat('H:i:s', $availability->end_time);
    
        $slots = [];
        while ($startTime < $endTime) {
            // Skip 12PM to 1PM lunch break
            if ($startTime->format('H:i') >= '12:00' && $startTime->format('H:i') < '13:00') {
                $startTime->addMinutes(30);
                continue;
            }
    
            $slots[] = $startTime->format('H:i');
            $startTime->addMinutes(30);
        }
    
        $bookedSlots = Booking::where('doctor_id', $doctorId)
            ->where('date', $date)
            ->where('status', 'confirmed')
            ->pluck('time') // get all booked times
            ->map(function ($time) {
                return Carbon::createFromFormat('H:i:s', $time)->format('H:i');
            })
            ->toArray();
    
        //Remove booked slots
        $availableSlots = array_diff($slots, $bookedSlots);
    
        return response()->json(array_values($availableSlots)); // reset array keys
    }
    
    //FOR ADMINS
    public function index()
    {
        $bookings = Booking::where('status', 'pending')->with(['patient.user', 'doctor'])->get();
    
        return view('appointment_list', compact('bookings'));
    }

    public function confirm($id)
    {
        $booking = Booking::where('BookingId', $id)->firstOrFail();
        $booking->status = 'confirmed';
        $booking->save();

        AppointmentRecord::create([
            'booking_id' => $booking->BookingId,
            'status' => 'confirmed'
        ]);
        
        $booking->patient->user->notify(new AppointmentConfirmedNotification($booking));
        return redirect()->route('appointmentlist')->with('success', 'Appointment confirmed!');
    }

    public function records()
    {
        $records = AppointmentRecord::with('booking.patient.user', 'booking.doctor')
            ->paginate(10); 
        return view('appointment_record', compact('records'));
    }
    public function search(Request $request)
    {
        $query = $request->input('search');

        $records = AppointmentRecord::with('booking.patient.user', 'booking.doctor')
            ->whereHas('booking.patient.user', function ($subQuery) use ($query) {
                $subQuery->where('name', 'LIKE', "%{$query}%");
            })
            ->orWhereHas('booking.doctor', function ($subQuery) use ($query) {
                $subQuery->where('firstname', 'LIKE', "%{$query}%")
                        ->orWhere('lastname', 'LIKE', "%{$query}%");
            })
            ->orWhereHas('booking', function ($subQuery) use ($query) {
                $subQuery->where('concern', 'LIKE', "%{$query}%");
            })
            ->paginate(10)
            ->appends(['search' => $query]);

        return view('appointment_record', compact('records'));
    }
    public function reschedule(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'reason' => 'required|string',
        ]);

        $booking = Booking::findOrFail($id);
        $originalDate = $booking->date;
        $originalTime = $booking->time;

        $booking->update([
            'date' => $request->date,
            'time' => $request->time,
            'reason' => $request->reason,
            'status' => 'confirmed', 
        ]);

        AppointmentRecord::create([
            'booking_id' => $booking->BookingId,
            'status' => 'confirmed'
        ]);

        $booking->patient->user->notify(new RescheduledAppointmentNotification($booking, $request->reason, $originalDate, $originalTime));

        return redirect()->route('appointmentlist')->with('success', 'Appointment has been rescheduled and confirmed!');
    }

}

