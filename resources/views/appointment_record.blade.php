@extends("layouts.app")
@section("title", "Appointment Record")
@section("content")

<table class="table table-bordered mt-3"> 
    <thead>
        <tr>
            <th>Patient Name</th>
            <th>Doctor Name</th>
            <th>Date</th>
            <th>Time</th>
            <th>Concern</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
@foreach ($bookings as $booking)
        <tr>
            <td>{{ $booking->patient->user->name }}</td> 
            <td>{{ $booking->doctor->firstname }} {{ $booking->doctor->lastname }}</td>
            <td>{{ \Carbon\Carbon::parse($booking->date)->format('F d, Y') }}</td>
            <td>{{ \Carbon\Carbon::parse($booking->time)->format('g:i A') }}</td>
            <td>{{ $booking->concern }}</td>
            <td>
                @if($booking->status == 'cancelled')
                    <span class="badge bg-danger">Cancelled</span>
                @elseif($booking->status == 'confirmed')
                    <span class="badge bg-success">Confirmed</span>
                @endif
            </td>
        </tr>
@endforeach

</table>
@endsection