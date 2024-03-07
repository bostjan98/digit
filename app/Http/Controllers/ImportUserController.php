<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ImportCSVJob;
use Illuminate\Support\Facades\Storage;

class ImportUserController extends Controller
{
    // This method returns the view for uploading CSV files
    public function showUploadForm()
    {
        return view('import');
    }

    // This method handles the CSV import
    public function importCSV(Request $request)
    {
        // Check if the file was uploaded successfully
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            // Store the uploaded file in the storage
            $file = $request->file('file');

            // Get the original filename
            $originalName = $file->getClientOriginalName();

            // Get the current date
            $currentDate = now()->format('Y-m-d-H-i-s');

            // Generate a new filename with the current date and original filename
            $newFileName = $currentDate . '-' . $originalName;

            // Store the file with the new filename
            $path = $file->storeAs('uploads', $newFileName);

            // Dispatch the job for processing
            ImportCSVJob::dispatch($path);

            // Return success response
            return response()->json(['message' => 'CSV import process started successfully'])
            ->header('Refresh', '7;url=' . route('upload-csv'));
        } else {
            // Return error response if file upload failed
            return response()->json(['error' => 'File upload failed'], 400)
            ->header('Refresh', '7;url=' . route('upload-csv'));
        }
    }
}