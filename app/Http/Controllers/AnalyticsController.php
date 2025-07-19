<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Services\ExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    protected $exportService;

    public function __construct(AnalyticsService $analyticsService, ExportService $exportService)
    {
        $this->analyticsService = $analyticsService;
        $this->exportService = $exportService;
    }

    /**
     * Display the main analytics dashboard
     */
    public function index(Request $request)
    {
        // Default date range (current month)
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $dateRange = [
            'start' => Carbon::parse($startDate),
            'end' => Carbon::parse($endDate),
        ];

        // Get analytics data
        $analytics = $this->analyticsService->getDashboardAnalytics($dateRange);

        return view('pages.analytics.dashboard', [
            'analytics' => $analytics,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dateRangeOptions' => $this->getDateRangeOptions(),
        ]);
    }

    /**
     * Get analytics data via API for AJAX requests
     */
    public function getAnalyticsData(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $dateRange = [
            'start' => Carbon::parse($startDate),
            'end' => Carbon::parse($endDate),
        ];

        $analytics = $this->analyticsService->getDashboardAnalytics($dateRange);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get real-time analytics data
     */
    public function getRealTimeData(): JsonResponse
    {
        $realTimeData = $this->analyticsService->getRealTimeAnalytics();

        return response()->json([
            'success' => true,
            'data' => $realTimeData,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Attendance analytics page
     */
    public function attendance(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $dateRange = [
            'start' => Carbon::parse($startDate),
            'end' => Carbon::parse($endDate),
        ];

        $attendanceAnalytics = $this->analyticsService->getAttendanceOverview(
            $dateRange['start'],
            $dateRange['end'],
        );
        $kpis = $this->analyticsService->getKPIs($dateRange['start'], $dateRange['end']);

        return view('pages.analytics.attendance', [
            'analytics' => $attendanceAnalytics,
            'kpis' => $kpis['attendance'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Get attendance analytics data via API
     */
    public function getAttendanceData(Request $request): JsonResponse
    {
        $startDate = Carbon::parse(
            $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
        );
        $endDate = Carbon::parse(
            $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
        );

        $analytics = $this->analyticsService->getAttendanceOverview($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Leave analytics page
     */
    public function leave(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $dateRange = [
            'start' => Carbon::parse($startDate),
            'end' => Carbon::parse($endDate),
        ];

        $leaveAnalytics = $this->analyticsService->getLeaveOverview(
            $dateRange['start'],
            $dateRange['end'],
        );
        $kpis = $this->analyticsService->getKPIs($dateRange['start'], $dateRange['end']);

        return view('pages.analytics.leave', [
            'analytics' => $leaveAnalytics,
            'kpis' => $kpis['leave'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Get leave analytics data via API
     */
    public function getLeaveData(Request $request): JsonResponse
    {
        $startDate = Carbon::parse(
            $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
        );
        $endDate = Carbon::parse(
            $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
        );

        $analytics = $this->analyticsService->getLeaveOverview($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Payroll analytics page
     */
    public function payroll(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $dateRange = [
            'start' => Carbon::parse($startDate),
            'end' => Carbon::parse($endDate),
        ];

        $payrollAnalytics = $this->analyticsService->getPayrollOverview(
            $dateRange['start'],
            $dateRange['end'],
        );
        $kpis = $this->analyticsService->getKPIs($dateRange['start'], $dateRange['end']);

        return view('pages.analytics.payroll', [
            'analytics' => $payrollAnalytics,
            'kpis' => $kpis['payroll'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Get payroll analytics data via API
     */
    public function getPayrollData(Request $request): JsonResponse
    {
        $startDate = Carbon::parse(
            $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
        );
        $endDate = Carbon::parse(
            $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
        );

        $analytics = $this->analyticsService->getPayrollOverview($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Employee performance analytics page
     */
    public function performance(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $dateRange = [
            'start' => Carbon::parse($startDate),
            'end' => Carbon::parse($endDate),
        ];

        $performanceAnalytics = $this->analyticsService->getEmployeePerformance(
            $dateRange['start'],
            $dateRange['end'],
        );

        return view('pages.analytics.performance', [
            'analytics' => $performanceAnalytics,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Get employee performance data via API
     */
    public function getPerformanceData(Request $request): JsonResponse
    {
        $startDate = Carbon::parse(
            $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
        );
        $endDate = Carbon::parse(
            $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
        );

        $analytics = $this->analyticsService->getEmployeePerformance($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Trends and forecasting page
     */
    public function trends(Request $request)
    {
        $startDate = $request->get(
            'start_date',
            Carbon::now()->subMonths(6)->startOfMonth()->format('Y-m-d'),
        );
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $dateRange = [
            'start' => Carbon::parse($startDate),
            'end' => Carbon::parse($endDate),
        ];

        $trendsData = $this->analyticsService->getTrends($dateRange['start'], $dateRange['end']);

        return view('pages.analytics.trends', [
            'trends' => $trendsData,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Get trends data via API
     */
    public function getTrendsData(Request $request): JsonResponse
    {
        $startDate = Carbon::parse(
            $request->get('start_date', Carbon::now()->subMonths(6)->startOfMonth()->format('Y-m-d')),
        );
        $endDate = Carbon::parse(
            $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
        );

        $trends = $this->analyticsService->getTrends($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $trends,
        ]);
    }

    /**
     * Export analytics summary
     */
    public function exportSummary(Request $request)
    {
        $request->validate([
            'format' => 'required|in:pdf,csv',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $dateRange = [
            'start' => Carbon::parse($request->start_date),
            'end' => Carbon::parse($request->end_date),
        ];

        $analytics = $this->analyticsService->getDashboardAnalytics($dateRange);

        return $this->exportService->exportAnalyticsSummary($analytics, $request->format);
    }

    /**
     * Get chart data for specific chart type
     */
    public function getChartData(Request $request): JsonResponse
    {
        $chartType = $request->get('chart_type');
        $startDate = Carbon::parse(
            $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d')),
        );
        $endDate = Carbon::parse(
            $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d')),
        );

        switch ($chartType) {
            case 'attendance_overview':
                $data = $this->analyticsService->getAttendanceOverview($startDate, $endDate);
                break;
            case 'leave_overview':
                $data = $this->analyticsService->getLeaveOverview($startDate, $endDate);
                break;
            case 'payroll_overview':
                $data = $this->analyticsService->getPayrollOverview($startDate, $endDate);
                break;
            case 'performance':
                $data = $this->analyticsService->getEmployeePerformance($startDate, $endDate);
                break;
            case 'trends':
                $data = $this->analyticsService->getTrends($startDate, $endDate);
                break;
            default:
                return response()->json(['error' => 'Invalid chart type'], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'chart_type' => $chartType,
        ]);
    }

    /**
     * Get analytics filters and options
     */
    public function getFilters(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'filters' => [
                'date_ranges' => $this->getDateRangeOptions(),
                'chart_types' => $this->getChartTypes(),
                'export_formats' => $this->exportService->getSupportedFormats(),
            ],
        ]);
    }

    /**
     * Custom analytics query builder
     */
    public function customQuery(Request $request): JsonResponse
    {
        $request->validate([
            'query_type' => 'required|in:attendance,leave,payroll,employee',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'filters' => 'array',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $filters = $request->get('filters', []);

        switch ($request->query_type) {
            case 'attendance':
                $data = $this->analyticsService->getAttendanceOverview($startDate, $endDate);
                break;
            case 'leave':
                $data = $this->analyticsService->getLeaveOverview($startDate, $endDate);
                break;
            case 'payroll':
                $data = $this->analyticsService->getPayrollOverview($startDate, $endDate);
                break;
            case 'employee':
                $data = $this->analyticsService->getEmployeePerformance($startDate, $endDate);
                break;
            default:
                return response()->json(['error' => 'Invalid query type'], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'query_type' => $request->query_type,
            'filters_applied' => $filters,
        ]);
    }

    /**
     * Get available date range options
     */
    private function getDateRangeOptions(): array
    {
        return [
            'today' => [
                'label' => 'Today',
                'start' => Carbon::today()->format('Y-m-d'),
                'end' => Carbon::today()->format('Y-m-d'),
            ],
            'yesterday' => [
                'label' => 'Yesterday',
                'start' => Carbon::yesterday()->format('Y-m-d'),
                'end' => Carbon::yesterday()->format('Y-m-d'),
            ],
            'this_week' => [
                'label' => 'This Week',
                'start' => Carbon::now()->startOfWeek()->format('Y-m-d'),
                'end' => Carbon::now()->endOfWeek()->format('Y-m-d'),
            ],
            'last_week' => [
                'label' => 'Last Week',
                'start' => Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d'),
                'end' => Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d'),
            ],
            'this_month' => [
                'label' => 'This Month',
                'start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'end' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            ],
            'last_month' => [
                'label' => 'Last Month',
                'start' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
                'end' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
            ],
            'this_quarter' => [
                'label' => 'This Quarter',
                'start' => Carbon::now()->startOfQuarter()->format('Y-m-d'),
                'end' => Carbon::now()->endOfQuarter()->format('Y-m-d'),
            ],
            'last_quarter' => [
                'label' => 'Last Quarter',
                'start' => Carbon::now()->subQuarter()->startOfQuarter()->format('Y-m-d'),
                'end' => Carbon::now()->subQuarter()->endOfQuarter()->format('Y-m-d'),
            ],
            'this_year' => [
                'label' => 'This Year',
                'start' => Carbon::now()->startOfYear()->format('Y-m-d'),
                'end' => Carbon::now()->endOfYear()->format('Y-m-d'),
            ],
            'last_year' => [
                'label' => 'Last Year',
                'start' => Carbon::now()->subYear()->startOfYear()->format('Y-m-d'),
                'end' => Carbon::now()->subYear()->endOfYear()->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get available chart types
     */
    private function getChartTypes(): array
    {
        return [
            'line' => 'Line Chart',
            'bar' => 'Bar Chart',
            'pie' => 'Pie Chart',
            'doughnut' => 'Doughnut Chart',
            'area' => 'Area Chart',
            'scatter' => 'Scatter Plot',
            'bubble' => 'Bubble Chart',
        ];
    }
}
