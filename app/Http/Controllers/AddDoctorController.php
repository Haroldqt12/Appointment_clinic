<?php

namespace App\Http\Controllers;

use App\Models\AddDoctor;
use App\Models\DoctorAvailability;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AddDoctorController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'age' => 'required|integer|min:1',
            'gender' => 'required|string',
            'contact' => 'required|string|max:15',
            'email' => 'required|email|max:255|unique:add_doctors,email',
            'marital' => 'nullable|string',
            'street' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'postal' => 'nullable|string|max:10',
            'specialization' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $doctorData = $request->only([
            'firstname', 'lastname', 'age', 'gender', 'contact', 'email',
            'marital', 'street', 'city', 'country', 'postal',
            'specialization', 'qualification', 'bio'
        ]);

        if ($request->hasFile('file_image')) {
            $imagePath = $request->file('file_image')->store('doctor_images', 'public');
            $doctorData['image_path'] = $imagePath;
        }

        AddDoctor::create($doctorData);

        return redirect()->route('DoctorList')->with('success', 'Doctor added successfully!');
    }

    public function searchDoctors(Request $request)
    {
        $searchTerm = $request->input('search');
        $query = AddDoctor::query();

        if ($searchTerm) {
            $query->where('firstname', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('lastname', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%");
        }

        return $query;
    }

    public function list(Request $request)
    {
        // Perform your search query
        $query = $this->searchDoctors($request);

        // Eager load the availabilities
        $Doctorlist = $query->with('availabilities')->paginate(4);

        // Get the current day and time
        $currentDay = Carbon::now()->format('l');       // e.g., 'Monday'
        $currentTime = Carbon::now()->format('H:i:s');  // e.g., '08:00:00'

        // Pass everything to the view
        return view('doctor_list', compact('Doctorlist', 'currentDay', 'currentTime'));
    }

    public function paginateDoctors(Request $request)
    {
        $query = $this->searchDoctors($request);
        $DoctorRecord = $query->paginate(2);

        return view('doctor_record', compact('DoctorRecord'));
    }

    public function edit($DoctorId) 
    {
        
        $doctor = AddDoctor::find($DoctorId);
        return view('doctor_edit', compact('doctor'));
    }

public function update(Request $request, $DoctorId)
{
    $doctor = AddDoctor::findOrFail($DoctorId);

    $request->validate([
        'file_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'firstname' => 'required|string|max:255',
        'lastname' => 'required|string|max:255',
        'age' => 'required|integer|min:1',
        'gender' => 'required|string',
        'contact' => 'required|string|max:15',
        'email' => 'required|email|max:255|unique:add_doctors,email,'.$doctor->DoctorId.',DoctorId',  
        'marital' => 'nullable|string',
        'street' => 'nullable|string|max:255',
        'city' => 'nullable|string|max:255',
        'country' => 'nullable|string|max:255',
        'postal' => 'nullable|string|max:10',
        'specialization' => 'nullable|string|max:255',
        'qualification' => 'nullable|string|max:255',
        'bio' => 'nullable|string',
    ]);
    

    $doctorData = $request->only([
        'firstname', 'lastname', 'age', 'gender', 'contact', 'email',
        'marital', 'street', 'city', 'country', 'postal',
        'specialization', 'qualification', 'bio'
    ]);

    if ($request->hasFile('file_image')) {
        // Delete old image if exists
        if ($doctor->image_path) {
            Storage::disk('public')->delete($doctor->image_path);
        }

        $imagePath = $request->file('file_image')->store('doctor_images', 'public');
        $doctorData['image_path'] = $imagePath;
    }

    $doctor->update($doctorData);

    return redirect()->route('DoctorRecord')->with('success', 'Doctor updated successfully!');
}

    public function view($id)
    {
        $doctor = AddDoctor::findOrFail($id);
        return view('doctor_view', compact('doctor'));
    }

    public function destroy($DoctorId)
    {
        $doctor = AddDoctor::findOrFail($DoctorId);
        DoctorAvailability::where('DoctorId', $doctor->DoctorId)->delete();

        if ($doctor->image_path) {
            Storage::disk('public')->delete($doctor->image_path);
        }

        $doctor->delete();

        return redirect()->route('DoctorRecord')->with('success', 'Doctor and associated availability deleted successfully!');
    }

    public function booking()
    {
        $doctors = AddDoctor::all();  

        return view('user.booking', compact('doctors'));  
    }

    


}