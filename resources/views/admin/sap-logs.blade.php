@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>SAP Integration Logs</h1>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>Error</th>
                    <th>Attempts</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->status }}</td>
                        <td>{{ $log->error_message ?? '-' }}</td>
                        <td>{{ $log->attempt_count }}</td>
                        <td>{{ $log->created_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $logs->links() }}
    </div>
@endsection
