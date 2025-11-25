<div class="mass-mailer-logs">
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 mb-1">Email Logs</h1>
                        <p class="text-muted">Monitor and manage your mass email campaigns</p>
                    </div>
                    <div class="btn-group" role="group">
                        <button
                            wire:click="exportLogs"
                            class="btn btn-primary"
                        >
                            <i class="fas fa-download mr-2"></i>
                            Export Logs
                        </button>
                        <button
                            wire:click="clearOldLogs"
                            class="btn btn-outline-secondary"
                        >
                            <i class="fas fa-trash mr-2"></i>
                            Clear Old Logs
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-envelope text-muted fa-2x"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="small text-uppercase text-muted">Total Emails</div>
                                <div class="h5 mb-0">{{ number_format($totalLogs) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-success fa-2x"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="small text-uppercase text-muted">Sent</div>
                                <div class="h5 mb-0">{{ number_format($sentLogs) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-times-circle text-danger fa-2x"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="small text-uppercase text-muted">Failed</div>
                                <div class="h5 mb-0">{{ number_format($failedLogs) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-percentage text-warning fa-2x"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="small text-uppercase text-muted">Success Rate</div>
                                <div class="h5 mb-0">{{ $successRate }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Filters</h5>
                <button
                    wire:click="toggleFilters"
                    class="btn btn-sm btn-outline-primary"
                >
                    <i class="fas fa-filter mr-1"></i>
                    {{ $showFilters ? 'Hide' : 'Show' }} Filters
                </button>
            </div>

            @if($showFilters)
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="search" class="form-label">Search</label>
                        <input
                            wire:model.live="search"
                            type="text"
                            id="search"
                            class="form-control"
                            placeholder="Email, subject, or error..."
                        >
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select
                            wire:model.live="statusFilter"
                            id="status"
                            class="form-select"
                        >
                            <option value="">All Statuses</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="dateFrom" class="form-label">From Date</label>
                        <input
                            wire:model.live="dateFrom"
                            type="date"
                            id="dateFrom"
                            class="form-control"
                        >
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="dateTo" class="form-label">To Date</label>
                        <input
                            wire:model.live="dateTo"
                            type="date"
                            id="dateTo"
                            class="form-control"
                        >
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button
                            wire:click="clearFilters"
                            class="btn btn-outline-secondary"
                        >
                            <i class="fas fa-times mr-1"></i>
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Export Options -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1">Export Options</h5>
                        <p class="text-muted mb-0">Download your email logs in various formats</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="btn-group" role="group">
                            <select
                                wire:model="exportFormat"
                                class="form-select"
                                style="width: auto; display: inline-block;"
                            >
                                <option value="csv">CSV</option>
                                <option value="json">JSON</option>
                            </select>
                            <button
                                wire:click="exportLogs"
                                class="btn btn-primary"
                            >
                                <i class="fas fa-download mr-1"></i>
                                Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Email Logs</h5>
                <div class="d-flex align-items-center">
                    <label for="perPage" class="form-label me-2 mb-0">Show:</label>
                    <select
                        wire:model.live="perPage"
                        id="perPage"
                        class="form-select"
                        style="width: auto;"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <div class="card-body p-0">
                @if($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td>
                                    <small class="text-muted">
                                        {{ $log->created_at->format('M j, Y g:i A') }}
                                    </small>
                                </td>
                                <td>{{ $log->recipient_email }}</td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $log->subject }}">
                                        {{ $log->subject }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->status === 'sent')
                                        <span class="badge bg-success">Sent</span>
                                    @elseif($log->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $log->attempts }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button
                                            wire:click="viewLogDetails({{ $log->id }})"
                                            class="btn btn-outline-primary btn-sm"
                                            title="View Details"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($log->status === 'failed')
                                        <button
                                            wire:click="retryFailedEmail({{ $log->id }})"
                                            class="btn btn-outline-success btn-sm"
                                            title="Retry Email"
                                        >
                                            <i class="fas fa-redo"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer">
                    {{ $logs->links() }}
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No email logs found</h5>
                    <p class="text-muted">
                        {{ $search || $statusFilter || $dateFrom || $dateTo ? 'Try adjusting your filters' : 'Start sending emails to see logs here' }}
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Log Details Modal -->
    @if($showLogDetails && $selectedLog)
    <div class="modal fade show" style="display: block;" tabindex="-1" aria-labelledby="logDetailsModal" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Email Log Details</h5>
                    <button
                        wire:click="closeLogDetails"
                        type="button"
                        class="btn-close"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <strong>Date Sent:</strong><br>
                            <span class="text-muted">{{ $selectedLog->created_at->format('M j, Y g:i A') }}</span>
                        </div>
                        <div class="col-sm-6">
                            <strong>Status:</strong><br>
                            @if($selectedLog->status === 'sent')
                                <span class="badge bg-success">Sent</span>
                            @elseif($selectedLog->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <strong>Recipient:</strong><br>
                            <span class="text-muted">{{ $selectedLog->recipient_email }}</span>
                        </div>
                        <div class="col-sm-6">
                            <strong>Attempts:</strong><br>
                            <span class="badge bg-secondary">{{ $selectedLog->attempts }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>Subject:</strong>
                        <div class="mt-1 p-3 bg-light border rounded">
                            {{ $selectedLog->subject }}
                        </div>
                    </div>

                    @if($selectedLog->error_message)
                    <div class="mb-3">
                        <strong class="text-danger">Error Message:</strong>
                        <div class="mt-1 p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded text-danger">
                            {{ $selectedLog->error_message }}
                        </div>
                    </div>
                    @endif

                    @if($selectedLog->sent_at)
                    <div class="mb-3">
                        <strong>Sent At:</strong><br>
                        <span class="text-muted">{{ $selectedLog->sent_at->format('M j, Y g:i A') }}</span>
                    </div>
                    @endif

                    @if($selectedLog->variables)
                    <div class="mb-3">
                        <strong>Variables Used:</strong>
                        <div class="mt-1 p-3 bg-light border rounded">
                            <pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($selectedLog->variables, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                    @endif

                    @if($selectedLog->attachments)
                    <div class="mb-3">
                        <strong>Attachments:</strong>
                        <div class="mt-1 p-3 bg-light border rounded">
                            <pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($selectedLog->attachments, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button
                        wire:click="closeLogDetails"
                        type="button"
                        class="btn btn-secondary"
                    >
                        Close
                    </button>
                    @if($selectedLog->status === 'failed')
                    <button
                        wire:click="retryFailedEmail({{ $selectedLog->id }})"
                        type="button"
                        class="btn btn-success"
                    >
                        <i class="fas fa-redo mr-1"></i>
                        Retry Email
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('closeModal', () => {
        const modal = document.querySelector('.modal.show');
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }
    });
});
</script>
