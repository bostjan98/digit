<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\ImportUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $rows = [];

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

            // Push row data to array
            $rows[] = $rowData;
            $rowCount++;

            if ($rowCount == 1000) {
                $this->processRecords($rows);
                $rows = []; // Reset the array
                $rowCount = 0;
            }
        }

        // Process remaining rows if any
        if (!empty($rows)) {
            $this->processRecords($rows);
        }

        fclose($file);  
        // Delete the uploaded file after processing
        unlink(storage_path('app/' . $this->path));
    }

    protected function processRecords($rows)
    {
        $currentTimestamp = Carbon::now();

        // Bulk insertion/update using database transactions
        DB::transaction(function () use ($rows, $currentTimestamp) {
            $updates = [];
            $inserts = [];

            foreach ($rows as $row) {
                $emso = $row[0];
                $existingUser = ImportUser::where('emso', $emso)->first();

                $data = [
                    'name_surname' => $row[1],
                    'country' => $row[2],
                    'age' => $row[3],
                    'descriptions' => $row[4],
                    'updated_at' => $currentTimestamp,
                ];

                if ($existingUser) {
                    $updates[] = $data + ['emso' => $emso];
                } else {
                    $inserts[] = $data + [
                        'emso' => $emso,
                        'created_at' => $currentTimestamp,
                    ];
                }
            }

            if (!empty($updates)) {
                // Bulk update existing records
                foreach (array_chunk($updates, 1000) as $chunk) {
                    ImportUser::upsert($chunk, ['emso'], array_keys($chunk[0]));
                }
            }

            if (!empty($inserts)) {
                // Bulk insert new records
                ImportUser::insert($inserts);
            }
        });
    }
}