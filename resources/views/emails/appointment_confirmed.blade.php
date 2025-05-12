<!DOCTYPE html>
<html>
<head>
    <title>Appointment Confirmed</title>
</head>
<body>
    <h2>Hi {{ $booking->patient->user->name }},</h2>
    <p>Your appointment has been confirmed by Medicare Clinic.</p>
    <p><strong>Date:</strong> {{ $booking->date }}</p>
    <p><strong>Time:</strong> {{ $booking->time }}</p>
    <p><strong>Doctor:</strong> Dr. {{ $booking->doctor->firstname }} {{ $booking->doctor->lastname }}</p>
    <br>
    <p>Thank you for choosing us!</p>
</body>
</html>
