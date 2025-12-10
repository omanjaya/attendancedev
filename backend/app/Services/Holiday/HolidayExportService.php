<?php

namespace App\Services\Holiday;

use Illuminate\Http\JsonResponse;

class HolidayExportService
{
    /**
     * Export to CSV
     */
    public function exportToCsv($holidays, $filename): JsonResponse
    {
        return response()->json([
            'success' => true,
            'download_url' => url("/holidays/download/{$filename}.csv"),
        ]);
    }

    /**
     * Export to Excel
     */
    public function exportToExcel($holidays, $filename): JsonResponse
    {
        return response()->json([
            'success' => true,
            'download_url' => url("/holidays/download/{$filename}.xlsx"),
        ]);
    }

    /**
     * Export to JSON
     */
    public function exportToJson($holidays, $filename): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $holidays->toArray(),
        ]);
    }

    /**
     * Export to PDF
     */
    public function exportToPdf($holidays, $filename): JsonResponse
    {
        return response()->json([
            'success' => true,
            'download_url' => url("/holidays/download/{$filename}.pdf"),
        ]);
    }
}
