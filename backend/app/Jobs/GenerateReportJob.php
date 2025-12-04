<?php

namespace App\Jobs;

use App\Exports\AttendanceReportExport;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $report;
    public $reportType;
    public $startDate;
    public $endDate;
    public $selectedColumns;
    public $format;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(Report $report, $reportType, $startDate, $endDate, $selectedColumns = [], $format = 'excel')
    {
        $this->report = $report;
        $this->reportType = $reportType;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->selectedColumns = $selectedColumns;
        $this->format = $format;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update status to processing
            $this->report->update(['status' => 'processing']);

            // Get report data with chunking
            $reportData = $this->getReportDataChunked();

            $data = $reportData['data'];
            $headings = $reportData['headings'];
            $columns = $reportData['columns'];

            // Generate file
            $extension = $this->format === 'pdf' ? 'pdf' : 'xlsx';
            $writerType = $this->format === 'pdf' ? \Maatwebsite\Excel\Excel::DOMPDF : \Maatwebsite\Excel\Excel::XLSX;

            $filename = "{$this->reportType}_" . now()->format('Ymd_His') . ".{$extension}";
            $path = "exports/{$filename}";

            // Ensure directory exists
            if (!file_exists(storage_path('app/public/exports'))) {
                mkdir(storage_path('app/public/exports'), 0755, true);
            }

            // Store file
            Excel::store(
                new AttendanceReportExport($data, $headings, $columns),
                $path,
                'public',
                $writerType
            );

            // Update report record
            $appUrl = config('app.url');
            $downloadUrl = "{$appUrl}/storage/{$path}";

            $this->report->update([
                'status' => 'completed',
                'filename' => $filename,
                'file_path' => "storage/{$path}",
                'download_url' => $downloadUrl,
            ]);

            Log::info('Report generated successfully', [
                'report_id' => $this->report->id,
                'filename' => $filename,
            ]);

            // TODO: Send notification to user
            // $this->report->user->notify(new ReportReadyNotification($this->report));

        } catch (\Exception $e) {
            // Update status to failed
            $this->report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Report generation failed', [
                'report_id' => $this->report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->report->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        Log::error('Report generation job failed permanently', [
            'report_id' => $this->report->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get report data with chunking for memory efficiency
     */
    private function getReportDataChunked(): array
    {
        $controller = new \App\Http\Controllers\Api\ReportsApiController();
        $reflection = new \ReflectionClass($controller);

        // Access private method via reflection
        $method = $reflection->getMethod('getReportData');
        $method->setAccessible(true);

        return $method->invoke($controller, $this->reportType, $this->startDate, $this->endDate, $this->selectedColumns);
    }
}
