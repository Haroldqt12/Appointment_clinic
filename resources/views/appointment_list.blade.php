@extends('layouts.app')
@section('title', 'Appointment Requests')
@section('scripts')
@section('content')
<div class="card shadow-lg border-0 rounded-4">
    <div class="card-header text-white text-center rounded-top" style="background-color: #0e2238;">
        <h3 class="m-0">Appointment Requests</h3>
        <p class="m-0">Manage and approve appointments easily</p>
    </div>
    <div class="card-body">
        @if(count($bookings))
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Concern</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $booking)
                    <tr>
                        <td>{{ $booking->patient->user->firstname ?? 'N/A' }}&nbsp;{{ $booking->patient->user->lastname ?? 'N/A' }}</td>
                        <td>{{ $booking->doctor->firstname }} {{ $booking->doctor->lastname }}</td>
                        <td>{{ \Carbon\Carbon::parse($booking->date)->format('M d, Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($booking->time)->format('h:i A') }}</td>
                        <td>{{ $booking->concern }}</td>
                        <td>
                            <span class="badge 
                                {{ $booking->status == 'pending' ? 'bg-warning text-dark' : 'bg-success' }}">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </td>
                        <td>
                            @if($booking->status == 'pending')
                            <div class="d-flex gap-2">
                                <form 
                                    method="POST"
                                    action="{{ route('appointmentlist.confirm', ['id' => $booking->BookingId]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Confirm
                                    </button>
                                </form>

                                <button class="btn btn-secondary btn-sm"
                                    onclick="openRescheduleModal(
                                        {{ $booking->BookingId }},
                                        {{ $booking->doctor_id }},
                                        '{{ $booking->date }}',
                                        '{{ \Carbon\Carbon::parse($booking->time)->format('H:i') }}'
                                    )">
                                    <i class="fas fa-calendar-alt"></i> Reschedule
                                </button>
                            </div>
                            @else
                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Confirmed</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <p class="text-center text-muted"><i class="fas fa-exclamation-circle"></i> No appointment requests available.</p>
        @endif
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="rescheduleForm" method="POST">    
        @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reschedule Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">       
                    <input type="hidden" name="booking_id" id="bookingId">
                    <div class="mb-3">
                        <label for="rescheduleDate" class="form-label">New Date</label>
                        <input type="date" class="form-control" name="date" id="rescheduleDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="time" class="form-label">Time</label>
                        <input type="time" class="form-control" id="currentTime" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="availableTimes" class="form-label">Reschedule To:</label>
                        <select class="form-select" name="time" id="availableTimes" required>
                            <option value="">Select a time</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="rescheduleReason" class="form-label">Reason</label>
                        <textarea name="reason" id="rescheduleReason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Confirm Reschedule</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- JS Script -->
<script>
    let doctorId = null;
    const dateInput = document.getElementById('rescheduleDate');
    const timeSelect = document.getElementById('availableTimes');
    const modalForm = document.getElementById('rescheduleForm');
    const currentTimeInput = document.getElementById('currentTime');

    function openRescheduleModal(bookingId, docId, currentDate, currentTime) {
        doctorId = docId;
        document.getElementById('bookingId').value = bookingId;
        document.getElementById('rescheduleDate').value = currentDate;
        document.getElementById('currentTime').value = currentTime;

        modalForm.action = `/appointmentlist/reschedule/${bookingId}`;

        fetchAvailableTimes(currentDate);

        const modal = new bootstrap.Modal(document.getElementById('rescheduleModal'));
        modal.show();
    }

    dateInput.addEventListener('change', () => {
        if (doctorId && dateInput.value) {
            fetchAvailableTimes(dateInput.value);
        }
    });

    function fetchAvailableTimes(date) {
        timeSelect.innerHTML = '<option>Loading...</option>';
        fetch(`/appointmentlist/available-slots?doctor_id=${doctorId}&date=${date}`)
            .then(response => response.json())
            .then(times => {
                timeSelect.innerHTML = '';
                if (times.length) {
                    times.forEach(time => {
                        const option = document.createElement('option');
                        option.value = time;
                        const formattedTime = new Date(`1970-01-01T${time}`).toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });
                        option.textContent = formattedTime;
                        timeSelect.appendChild(option);
                    });
                } else {
                    timeSelect.innerHTML = '<option>No available slots</option>';
                }
            });
    }
</script>
@endsection
