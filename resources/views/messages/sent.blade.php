@extends('layouts.main')

@section('title', 'Messages - Sent')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Sent Messages
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('messages.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i>
                            New Message
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if ($messages->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>To</th>
                                        <th>Subject</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($messages as $message)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div
                                                        class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center mr-2">
                                                        <span class="text-white font-weight-bold">
                                                            {{ strtoupper(substr($message->receiver->name ?? 'U', 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="font-weight-bold">
                                                            {{ $message->receiver->name ?? 'Unknown User' }}</div>
                                                        <small
                                                            class="text-muted">{{ $message->receiver->email ?? '' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <a href="{{ route('messages.show', $message) }}"
                                                        class="text-decoration-none">
                                                        {{ $message->subject }}
                                                    </a>
                                                    @if ($message->attachments->count() > 0)
                                                        <i class="fas fa-paperclip text-muted ml-1"></i>
                                                    @endif
                                                </div>
                                                <small class="text-muted">
                                                    {{ Str::limit(strip_tags($message->body), 100) }}
                                                </small>
                                            </td>
                                            <td>
                                                <div>{{ $message->created_at->format('M d, Y') }}</div>
                                                <small
                                                    class="text-muted">{{ $message->created_at->format('h:i A') }}</small>
                                            </td>
                                            <td>
                                                @if ($message->isRead())
                                                    <span class="badge badge-success">Read</span>
                                                @else
                                                    <span class="badge badge-secondary">Unread</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('messages.show', $message) }}"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('messages.create', ['reply_to' => $message->id]) }}"
                                                        class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-reply"></i>
                                                    </a>
                                                    <form action="{{ route('messages.destroy', $message) }}" method="POST"
                                                        class="d-inline"
                                                        onsubmit="return confirm('Are you sure you want to delete this message?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center">
                            {{ $messages->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-paper-plane fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No sent messages</h5>
                            <p class="text-muted">You haven't sent any messages yet.</p>
                            <a href="{{ route('messages.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i>
                                Send your first message
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 14px;
        }
    </style>
@endpush
