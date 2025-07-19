<?php

namespace App\Console\Commands;

use App\Services\PerformanceMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class PerformanceReport extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'performance:report 
                            {--period=24 : Period in hours to generate report for}
                            {--email= : Email address to send report to}
                            {--format=text : Report format (text, json, html)}';

    /**
     * The console command description.
     */
    protected $description = 'Generate and optionally email a performance report';

    private PerformanceMonitorService $performanceMonitor;

    public function __construct(PerformanceMonitorService $performanceMonitor)
    {
        parent::__construct();
        $this->performanceMonitor = $performanceMonitor;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $period = (int) $this->option('period');
        $email = $this->option('email');
        $format = $this->option('format');

        $this->info("Generating performance report for the last {$period} hours...");

        try {
            // Get performance data
            $summary = $this->performanceMonitor->getPerformanceSummary($period);
            $alerts = $this->performanceMonitor->getPerformanceAlerts();

            // Generate report based on format
            $report = match ($format) {
                'json' => $this->generateJsonReport($summary, $alerts, $period),
                'html' => $this->generateHtmlReport($summary, $alerts, $period),
                default => $this->generateTextReport($summary, $alerts, $period),
            };

            // Output report
            if ($format === 'json') {
                $this->line($report);
            } else {
                $this->info($report);
            }

            // Send email if requested
            if ($email) {
                $this->sendEmailReport($email, $report, $format, $period);
                $this->info("Report sent to {$email}");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate performance report: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Generate text format report
     */
    private function generateTextReport(array $summary, array $alerts, int $period): string
    {
        $report = "=== PERFORMANCE REPORT ({$period}h) ===\n";
        $report .= 'Generated: '.now()->format('Y-m-d H:i:s')."\n\n";

        // Summary
        $report .= "SUMMARY:\n";
        $report .= '- Total Requests: '.number_format($summary['requests_count'])."\n";
        $report .= "- Average Response Time: {$summary['avg_response_time']}ms\n";
        $report .= "- Max Response Time: {$summary['max_response_time']}ms\n";
        $report .= "- Average Memory Usage: {$summary['avg_memory_usage']}\n";
        $report .= "- Average Queries/Request: {$summary['avg_queries_per_request']}\n";
        $report .= "- Slow Requests: {$summary['slow_requests']}\n";
        $report .= "- Error Rate: {$summary['error_rate']}%\n\n";

        // Alerts
        if (! empty($alerts)) {
            $report .= "ALERTS:\n";
            foreach ($alerts as $alert) {
                $report .= "- [{$alert['severity']}] {$alert['title']}: {$alert['message']}\n";
            }
        } else {
            $report .= "ALERTS: None\n";
        }

        $report .= "\n=== END REPORT ===\n";

        return $report;
    }

    /**
     * Generate JSON format report
     */
    private function generateJsonReport(array $summary, array $alerts, int $period): string
    {
        $report = [
            'generated_at' => now()->toISOString(),
            'period_hours' => $period,
            'summary' => $summary,
            'alerts' => $alerts,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
        ];

        return json_encode($report, JSON_PRETTY_PRINT);
    }

    /**
     * Generate HTML format report
     */
    private function generateHtmlReport(array $summary, array $alerts, int $period): string
    {
        $html = '<!DOCTYPE html><html><head><title>Performance Report</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;margin:20px;}';
        $html .= '.alert{padding:10px;margin:10px 0;border-left:4px solid #f39c12;}';
        $html .= '.alert.error{border-color:#e74c3c;background:#fdf2f2;}';
        $html .= '.alert.warning{border-color:#f39c12;background:#fefcf3;}';
        $html .= 'table{border-collapse:collapse;width:100%;}';
        $html .= 'th,td{border:1px solid #ddd;padding:8px;text-align:left;}';
        $html .= 'th{background-color:#f2f2f2;}</style></head><body>';

        $html .= "<h1>Performance Report ({$period}h)</h1>";
        $html .= '<p><strong>Generated:</strong> '.now()->format('Y-m-d H:i:s').'</p>';

        // Summary table
        $html .= '<h2>Summary</h2>';
        $html .= '<table>';
        $html .= '<tr><th>Metric</th><th>Value</th></tr>';
        $html .=
          '<tr><td>Total Requests</td><td>'.number_format($summary['requests_count']).'</td></tr>';
        $html .= "<tr><td>Average Response Time</td><td>{$summary['avg_response_time']}ms</td></tr>";
        $html .= "<tr><td>Max Response Time</td><td>{$summary['max_response_time']}ms</td></tr>";
        $html .= "<tr><td>Average Memory Usage</td><td>{$summary['avg_memory_usage']}</td></tr>";
        $html .= "<tr><td>Average Queries/Request</td><td>{$summary['avg_queries_per_request']}</td></tr>";
        $html .= "<tr><td>Slow Requests</td><td>{$summary['slow_requests']}</td></tr>";
        $html .= "<tr><td>Error Rate</td><td>{$summary['error_rate']}%</td></tr>";
        $html .= '</table>';

        // Alerts
        $html .= '<h2>Alerts</h2>';
        if (! empty($alerts)) {
            foreach ($alerts as $alert) {
                $class = $alert['type'] === 'error' ? 'error' : 'warning';
                $html .= "<div class='alert {$class}'>";
                $html .= "<strong>[{$alert['severity']}] {$alert['title']}:</strong> {$alert['message']}";
                $html .= '</div>';
            }
        } else {
            $html .= '<p>No alerts to report.</p>';
        }

        $html .= '</body></html>';

        return $html;
    }

    /**
     * Send email report
     */
    private function sendEmailReport(string $email, string $report, string $format, int $period): void
    {
        $subject = "Performance Report - Last {$period} Hours";

        if ($format === 'html') {
            Mail::html($report, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } else {
            Mail::raw($report, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        }
    }
}
