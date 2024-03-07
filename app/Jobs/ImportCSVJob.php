<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\ImportUser;

class ImportCSVJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $path;

    /**
     * Create a new job instance.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $file = fopen(storage_path('app/' . $this->path), 'r');
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

            // Combine headers with row data
            $rowData = array_combine($headers, explode(';', $row[0]));

            // Push row data to collection
            $rows->push($rowData);
            $rowCount++;

            if ($rows->count() == 1000) {
                // Convert collection to array for processing
                $arrayData = $rows->toArray();
                $this->processRecords($arrayData);
                $rows = collect([]); // Reset the collection
                $rowCount = 0;
            }
        }

        // Process remaining rows if any
        if ($rows->count() > 0) {
            $arrayData = $rows->toArray();
            $this->processRecords($arrayData);
        }

        fclose($file);  
        // Delete the uploaded file after processing
        unlink(storage_path('app/' . $this->path));
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
