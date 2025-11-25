<?php

namespace Mrclln\MassMailer\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use Mrclln\MassMailer\Models\MassMailerLog;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class MassMailerLogs extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filter properties
    public $search = '';
    public $statusFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 25;

    // Statistics
    public $totalLogs = 0;
    public $sentLogs = 0;
    public $failedLogs = 0;
    public $pendingLogs = 0;
    public $successRate = 0;

    // UI State
    public $showFilters = false;
    public $selectedLog = null;
    public $showLogDetails = false;

    // Export
    public $exportFormat = 'csv';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        $this->updateStatistics();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = $this->getFilteredLogs();

        // Update statistics when data changes
        $this->updateStatistics();

        $framework = config('mass-mailer.ui.framework', 'bootstrap');

        return view("mass-mailer::{$framework}.mass-mailer-logs", [
            'logs' => $logs,
            'totalLogs' => $this->totalLogs,
            'sentLogs' => $this->sentLogs,
            'failedLogs' => $this->failedLogs,
            'pendingLogs' => $this->pendingLogs,
            'successRate' => $this->successRate,
        ]);
    }

    public function getFilteredLogs()
    {
        $query = MassMailerLog::query();

        // Apply user filter
        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        }

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('recipient_email', 'like', '%' . $this->search . '%')
                  ->orWhere('subject', 'like', '%' . $this->search . '%')
                  ->orWhere('error_message', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Apply date filters
        if (!empty($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        // Order by latest first
        $query->latest();

        return $query->paginate($this->perPage);
    }

    public function updateStatistics()
    {
        $query = MassMailerLog::query();

        // Apply user filter
        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        }

        // Apply current filters for statistics
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('recipient_email', 'like', '%' . $this->search . '%')
                  ->orWhere('subject', 'like', '%' . $this->search . '%')
                  ->orWhere('error_message', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        if (!empty($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $this->totalLogs = (clone $query)->count();
        $this->sentLogs = (clone $query)->where('status', 'sent')->count();
        $this->failedLogs = (clone $query)->where('status', 'failed')->count();
        $this->pendingLogs = (clone $query)->where('status', 'pending')->count();

        $this->successRate = $this->totalLogs > 0
            ? round(($this->sentLogs / $this->totalLogs) * 100, 2)
            : 0;
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();

        LivewireAlert::success()
            ->title('Success!')
            ->text('Filters cleared successfully')
            ->toast(true)->timer(2000)
            ->show();
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function viewLogDetails($logId)
    {
        $this->selectedLog = MassMailerLog::find($logId);

        if ($this->selectedLog) {
            $this->showLogDetails = true;
        }
    }

    public function closeLogDetails()
    {
        $this->selectedLog = null;
        $this->showLogDetails = false;
    }

    public function exportLogs()
    {
        $logs = $this->getFilteredLogs()->getCollection();

        if ($logs->isEmpty()) {
            LivewireAlert::warning()
                ->title('No Data!')
                ->text('No logs found to export')
                ->show();
            return;
        }

        $filename = 'mass-mailer-logs-' . now()->format('Y-m-d-H-i-s');

        switch ($this->exportFormat) {
            case 'csv':
                return $this->exportToCsv($logs, $filename);
            case 'json':
                return $this->exportToJson($logs, $filename);
            default:
                LivewireAlert::error()
                    ->title('Export Error!')
                    ->text('Invalid export format')
                    ->show();
        }
    }

    protected function exportToCsv($logs, $filename)
    {
        $csvData = [];

        // Add headers
        $csvData[] = [
            'Date',
            'Recipient Email',
            'Subject',
            'Status',
            'Attempts',
            'Error Message',
            'Sent At'
        ];

        // Add data rows
        foreach ($logs as $log) {
            $csvData[] = [
                $log->created_at->format('Y-m-d H:i:s'),
                $log->recipient_email,
                $log->subject,
                $log->status,
                $log->attempts,
                $log->error_message ?? '',
                $log->sent_at ? $log->sent_at->format('Y-m-d H:i:s') : '',
            ];
        }

        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ];

        return response($csvContent, 200, $headers);
    }

    protected function exportToJson($logs, $filename)
    {
        $jsonData = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'date' => $log->created_at->format('Y-m-d H:i:s'),
                'recipient_email' => $log->recipient_email,
                'subject' => $log->subject,
                'status' => $log->status,
                'attempts' => $log->attempts,
                'error_message' => $log->error_message,
                'sent_at' => $log->sent_at ? $log->sent_at->format('Y-m-d H:i:s') : null,
                'variables' => $log->variables,
                'attachments' => $log->attachments,
            ];
        })->toArray();

        $jsonContent = json_encode($jsonData, JSON_PRETTY_PRINT);

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.json"',
        ];

        return response($jsonContent, 200, $headers);
    }

    public function clearOldLogs()
    {
        $cutoffDate = now()->subMonths(6);

        $query = MassMailerLog::where('created_at', '<', $cutoffDate);

        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        }

        $count = $query->count();

        if ($count === 0) {
            LivewireAlert::info()
                ->title('No Logs!')
                ->text('No old logs found to clear')
                ->show();
            return;
        }

        $query->delete();

        LivewireAlert::success()
            ->title('Logs Cleared!')
            ->text("Cleared {$count} old log entries")
            ->show();

        $this->updateStatistics();
    }

    public function retryFailedEmail($logId)
    {
        $log = MassMailerLog::find($logId);

        if (!$log || $log->status !== 'failed') {
            LivewireAlert::error()
                ->title('Cannot Retry!')
                ->text('Only failed emails can be retried')
                ->show();
            return;
        }

        // Here you would implement the retry logic
        // For now, we'll just mark it as pending for retry
        $log->update([
            'status' => 'pending',
            'error_message' => null,
            'attempts' => $log->attempts + 1,
        ]);

        // TODO: Implement actual retry mechanism
        // This would involve creating a new job to send the email again

        LivewireAlert::success()
            ->title('Email Queued!')
            ->text('Failed email has been queued for retry')
            ->show();

        $this->updateStatistics();
    }

    public function getStatusBadgeClass($status)
    {
        switch ($status) {
            case 'sent':
                return 'badge badge-success';
            case 'failed':
                return 'badge badge-danger';
            case 'pending':
                return 'badge badge-warning';
            default:
                return 'badge badge-secondary';
        }
    }

    public function getStatusIcon($status)
    {
        switch ($status) {
            case 'sent':
                return '✅';
            case 'failed':
                return '❌';
            case 'pending':
                return '⏳';
            default:
                return '❓';
        }
    }
}
