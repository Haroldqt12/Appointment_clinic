@extends('layouts.nav')

@section('title', 'Book Appointment')
@section('content')
<div class="container mt-5">
    <h2>Book an Appointment</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('bookings.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="doctor_id" class="form-label">Select Doctor</label>
            <select name="doctor_id" id="doctor_id" class="form-select" required>
                <option value="">Choose...</option>
                @foreach ($doctors as $doctor)
                    <option value="{{ $doctor->DoctorId }}">{{ $doctor->firstname }} {{ $doctor->lastname }} - {{ $doctor->specialization }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="date" class="form-label">Select Date</label>
            <input type="date" name="date" id="date" class="form-control" min="{{ date('Y-m-d') }}" required>
        </div>

        <div class="mb-3">
            <label for="time" class="form-label">Available Time Slots</label>
            <select name="time" id="time" class="form-select" required>
                <option value="">Select a time</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="concern" class="form-label">Your Concern</label>
            <input type="text" name="concern" id="concern" class="form-control" rows="3" required></input>
        </div>

        <button type="submit" class="btn btn-primary">Book Now</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const doctorSelect = document.getElementById('doctor_id');
    const dateInput = document.getElementById('date');
    const timeSelect = document.getElementById('time');

    function loadAvailableSlots() {
        const doctorId = doctorSelect.value;
        const date = dateInput.value;

        if (doctorId && date) {
            fetch(`/booking/available-slots?doctor_id=${doctorId}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    timeSelect.innerHTML = '<option value="">Select a time</option>';
                    data.forEach(time => {
                        timeSelect.innerHTML += `<option value="${time}">${time}</option>`;
                    });
                });
        }
    }

    doctorSelect.addEventListener('change', loadAvailableSlots);
    dateInput.addEventListener('change', loadAvailableSlots);
});
</script>
@endsection
