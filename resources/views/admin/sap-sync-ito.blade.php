@extends('layouts.main')

@section('title_page')
    SAP ITO Sync
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
    <li class="breadcrumb-item active">SAP ITO Sync</li>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">SAP ITO Sync</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.sap-sync-ito') }}" id="sync-form">
                            @csrf
                            <div class="form-group">
                                <label>Date Range <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="date_range" id="date_range_today"
                                        value="today" {{ old('date_range', 'today') === 'today' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="date_range_today">
                                        TODAY
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="date_range"
                                        id="date_range_yesterday" value="yesterday"
                                        {{ old('date_range') === 'yesterday' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="date_range_yesterday">
                                        YESTERDAY
                                    </label>
                                </div>
                                @if (auth()->user()->hasAnyRole(['admin', 'superadmin', 'accounting']))
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="date_range"
                                            id="date_range_custom" value="custom"
                                            {{ old('date_range') === 'custom' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="date_range_custom">
                                            CUSTOM
                                        </label>
                                    </div>
                                @endif
                                @error('date_range')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="row" id="custom-date-range"
                                style="display: {{ old('date_range') === 'custom' ? 'block' : 'none' }};">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                            id="start_date" name="start_date" value="{{ old('start_date') }}">
                                        @error('start_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="end_date">End Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                            id="end_date" name="end_date" value="{{ old('end_date') }}">
                                        @error('end_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" id="sync-btn">
                                    <i class="fas fa-sync"></i> Sync from SAP
                                </button>
                                <span class="ml-2 text-muted" id="sync-status"></span>
                            </div>
                        </form>

                        @if (session('sync_results'))
                            <div class="alert alert-info mt-3">
                                <h5><i class="icon fas fa-info"></i> Sync Results</h5>
                                <ul class="mb-0">
                                    <li><strong>Created:</strong> {{ session('sync_results')['success'] }} record(s)</li>
                                    <li><strong>Skipped:</strong> {{ session('sync_results')['skipped'] }} record(s)</li>
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Sync activity (today &amp; yesterday)</h3>
                        <span class="text-muted small">SAP ITO sync runs by calendar day ({{ config('app.timezone') }})</span>
                    </div>
                    <div class="card-body p-0">
                        @foreach ([
                            'Today' => $itoSyncLogsToday,
                            'Yesterday' => $itoSyncLogsYesterday,
                        ] as $periodLabel => $itoSyncLogs)
                            @php
                                $headingDate = $periodLabel === 'Today' ? $todayDate : $yesterdayDate;
                            @endphp
                            <div class="@if (!$loop->last) border-bottom @endif">
                                <div class="px-3 py-2 bg-light">
                                    <strong>{{ $periodLabel }}</strong>
                                    <span class="text-muted">({{ $headingDate }})</span>
                                    <span class="badge badge-secondary ml-1">{{ $itoSyncLogs->count() }}</span>
                                </div>
                                <div class="table-responsive">
                                    @if ($itoSyncLogs->isEmpty())
                                        <p class="p-3 mb-0 text-muted">No sync activity for this day.</p>
                                    @else
                                        <table class="table table-hover table-striped table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Synced at</th>
                                                    <th>Status</th>
                                                    <th>SAP date range</th>
                                                    <th>Method</th>
                                                    <th>Created</th>
                                                    <th>Skipped</th>
                                                    <th>Trigger</th>
                                                    <th>User</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($itoSyncLogs as $log)
                                                    @php
                                                        $req = json_decode($log->request_payload, true);
                                                        if (!is_array($req)) {
                                                            $req = [];
                                                        }
                                                        $res = json_decode($log->response_payload, true);
                                                        if (!is_array($res)) {
                                                            $res = [];
                                                        }
                                                        $syncedAt = \Illuminate\Support\Carbon::parse($log->created_at)
                                                            ->timezone(config('app.timezone'))
                                                            ->format('Y-m-d H:i:s');
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $syncedAt }}</td>
                                                        <td>
                                                            @if ($log->status === 'success')
                                                                <span class="badge badge-success">Success</span>
                                                            @else
                                                                <span class="badge badge-danger">Failed</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if (!empty($req['start_date']) || !empty($req['end_date']))
                                                                {{ $req['start_date'] ?? '?' }} → {{ $req['end_date'] ?? '?' }}
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                        <td><code class="small">{{ $req['method'] ?? '—' }}</code></td>
                                                        <td>{{ $res['success'] ?? '—' }}</td>
                                                        <td>{{ $res['skipped'] ?? '—' }}</td>
                                                        <td>{{ $req['trigger'] ?? '—' }}</td>
                                                        <td>{{ $req['triggered_by_user_id'] ?? '—' }}</td>
                                                    </tr>
                                                    @if ($log->status !== 'success' && $log->error_message)
                                                        <tr class="bg-light">
                                                            <td colspan="8" class="small text-danger">
                                                                {{ \Illuminate\Support\Str::limit($log->error_message, 200) }}
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
@endsection

@section('scripts')
    <!-- Toastr -->
    <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize Toastr
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": false,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "8000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };

            // Show session messages
            @if (session('success'))
                toastr.success('{{ session('success') }}', 'Success');
            @endif

            @if (session('error'))
                toastr.error('{{ session('error') }}', 'Error');
            @endif

            @if (session('warning'))
                toastr.warning('{{ session('warning') }}', 'Warning');
            @endif

            @if (session('info'))
                toastr.info('{{ session('info') }}', 'Info');
            @endif

            // Helper function to set dates
            function setDatesForRange(range) {
                const today = new Date();
                let dateToSet;

                if (range === 'today') {
                    dateToSet = today;
                } else if (range === 'yesterday') {
                    dateToSet = new Date(today);
                    dateToSet.setDate(dateToSet.getDate() - 1);
                }

                if (dateToSet) {
                    const dateStr = dateToSet.toISOString().split('T')[0];
                    $('#start_date').val(dateStr);
                    $('#end_date').val(dateStr);
                }
            }

            // Handle date range selection
            $('input[name="date_range"]').on('change', function() {
                const selectedValue = $(this).val();
                const $customDateRange = $('#custom-date-range');
                const $startDate = $('#start_date');
                const $endDate = $('#end_date');

                if (selectedValue === 'custom') {
                    $customDateRange.slideDown();
                    $startDate.prop('required', true);
                    $endDate.prop('required', true);
                } else {
                    $customDateRange.slideUp();
                    $startDate.prop('required', false);
                    $endDate.prop('required', false);
                    setDatesForRange(selectedValue);
                }
            });

            // Initialize dates on page load
            const initialDateRange = $('input[name="date_range"]:checked').val();
            if (initialDateRange && initialDateRange !== 'custom') {
                setDatesForRange(initialDateRange);
            }

            // Handle form submission
            $('#sync-form').on('submit', function(e) {
                const $btn = $('#sync-btn');
                const $status = $('#sync-status');
                const selectedRange = $('input[name="date_range"]:checked').val();

                // If not custom, ensure dates are set
                if (selectedRange !== 'custom') {
                    setDatesForRange(selectedRange);
                }

                // Disable button and show loading
                $btn.prop('disabled', true);
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
                $status.html(
                    '<i class="fas fa-spinner fa-spin"></i> Please wait, this may take a moment...');
            });
        });
    </script>
@endsection
