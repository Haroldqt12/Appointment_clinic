<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Patient;
use App\Models\AddDoctor;
use App\Models\DoctorAvailability;
use App\Models\AppointmentRecord;
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

        Booking::create([
            'patient_id' => $patient->id,
            'doctor_id' => $request->doctor_id,
            'date' => $request->date,
            'time' => $request->time,
            'concern' => $request->concern,
            'status' => 'pending',
        ]);

        return redirect()->route('user.history')->with('success', 'Appointment request submitted successfully!');
    }

    public function history()
    {
        $user = Auth::user();

        $bookings = Booking::whereHas('patient', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('doctor', 'patient.user')->get();

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
            if ($startTime->format('H:i') >= '12:00' && $startTime->format('H:i') < '13:00') {
                $startTime->addMinutes(30); 
                continue;
            }

            $slots[] = $startTime->format('H:i');
            $startTime->addMinutes(30);
        }

        return response()->json($slots);
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

        return redirect()->route('appointmentlist')->with('success', 'Appointment confirmed!');
    }

    public function records()
    {
        $bookings = Booking::with('patient.user', 'doctor')
                    ->whereIn('status', ['confirmed', 'cancelled'])
                    ->get();

        return view('appointment_record', compact('bookings'));
    }

}

