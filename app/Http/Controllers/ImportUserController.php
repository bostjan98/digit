<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ImportUser;

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
            // Get the uploaded file
            $file = $request->file('file');
            
            // Specify the directory where you want to save the file
            $directory = storage_path('app/uploads');
            
            // Ensure the directory exists; create it if necessary
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Move the uploaded file to the specified directory
            $filename = $file->getClientOriginalName();
            $file->move($directory, $filename);
            
            // Get the path to the uploaded file
            $path = $directory . '/' . $filename;

            // Process the CSV file
            $this->processCSV($path);
            
            // Return success response
            return response()->json(['message' => 'CSV data imported successfully']);
        } else {
            // Return error response if file upload failed
            return response()->json(['error' => 'File upload failed'], 400);
        }
    }

    protected function processCSV($path)
    {
        $file = fopen($path, 'r');
        $headers = null;
        $rows = collect([]);
        
        $firstRowSkipped = false; // Flag to track if the first row has been skipped
        $rowCount = 0;
        while (($row = fgetcsv($file)) !== false) {
            if (!$firstRowSkipped) {
                $firstRowSkipped = true;
                $headers = explode(';', $row[0]); // Split the first row on the semicolon delimiter
                continue; // Skip the first row
            }
            $headers[0]=0;
            $headers[1]=1;
            $headers[2]=2;
            $headers[3]=3;
            $headers[4]=4;
          
            $rowData = explode(';', $row[0]); // Split the current row on the semicolon delimiter
            $rowData = array_combine($headers, $rowData); // Combine headers with row data
            $rows->push($rowData);
            $rowCount++;
          
            if ($rows->count() == 1000) {
                $arrayData = $rows->toArray();
                $this->processRecords( $arrayData);
                $rows = collect([]); // Reset the collection
                $rowCount = 0;
            }
        }
        if ($rows->count() > 0) {
            $this->processRecords($rows);
        }
        fclose($file);
    }

    protected function processRecords($rows)
    {
       
        foreach ($rows as $row) {
            
            $emso = $row[0];
            $existingUser = ImportUser::where('emso', $emso)->first();

            if ($existingUser) {
                $existingUser->update([
                    'name_surname' => $row[1],
                    'country' => $row[2],
                    'age' => $row[3],
                    'descriptions' => $row[4]
                ]);
            } else {
                ImportUser::create([
                    'emso' => $emso,
                    'name_surname' => $row[1],
                    'country' => $row[2],
                    'age' => $row[3],
                    'descriptions' => $row[4]
                ]);
            }
        }
    }
}