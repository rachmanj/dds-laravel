@extends('layouts.main')

@section('title', 'Message: ' . $message->subject)

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-envelope mr-2"></i>
                        {{ $message->subject }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('messages.create', ['reply_to' => $message->id]) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-reply mr-1"></i>
                            Reply
                        </a>
                        <a href="{{ route('messages.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Back to Inbox
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="message-header mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div
                                        class="avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center mr-3">
                                        <span class="text-white font-weight-bold" style="font-size: 18px;">
                                            {{ strtoupper(substr($message->sender->name ?? 'U', 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">{{ $message->sender->name ?? 'Unknown User' }}</h5>
                                        <p class="text-muted mb-1">{{ $message->sender->email ?? '' }}</p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock mr-1"></i>
                                            {{ $message->created_at->format('M d, Y \a\t h:i A') }}
                                        </small>
                                    </div>
                                </div>

                                <div class="message-meta">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <strong>To:</strong> {{ $message->receiver->name ?? 'Unknown User' }}
                                        </div>
                                        <div class="col-sm-6">
                                            <strong>Status:</strong>
                                            @if ($message->isRead())
                                                <span class="badge badge-success">Read</span>
                                            @else
                                                <span class="badge badge-warning">Unread</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="message-content">
                                <div class="border rounded p-3 bg-light">
                                    {!! nl2br(e($message->body)) !!}
                                </div>
                            </div>

                            @if ($message->attachments->count() > 0)
                                <div class="message-attachments mt-4">
                                    <h6><i class="fas fa-paperclip mr-2"></i>Attachments</h6>
                                    <div class="list-group">
                                        @foreach ($message->attachments as $attachment)
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-file mr-2"></i>
                                                    {{ $attachment->file_original_name }}
                                                    <small
                                                        class="text-muted ml-2">({{ $attachment->file_size_human }})</small>
                                                </div>
                                                <a href="{{ Storage::url($attachment->file_path) }}"
                                                    class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-download"></i>
                                                    Download
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title">Message Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('messages.create', ['reply_to' => $message->id]) }}"
                                            class="btn btn-success">
                                            <i class="fas fa-reply mr-1"></i>
                                            Reply
                                        </a>
                                        <a href="{{ route('messages.create') }}" class="btn btn-primary">
                                            <i class="fas fa-edit mr-1"></i>
                                            New Message
                                        </a>
                                        <form action="{{ route('messages.destroy', $message) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this message?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="fas fa-trash mr-1"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            @if ($message->replies->count() > 0)
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h6 class="card-title">Replies ({{ $message->replies->count() }})</h6>
                                    </div>
                                    <div class="card-body">
                                        @foreach ($message->replies as $reply)
                                            <div class="border-bottom pb-2 mb-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong>{{ $reply->sender->name ?? 'Unknown' }}</strong>
                                                        <br>
                                                        <small
                                                            class="text-muted">{{ $reply->created_at->format('M d, h:i A') }}</small>
                                                    </div>
                                                    <a href="{{ route('messages.show', $reply) }}"
                                                        class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                </div>
                                                <p class="mb-0 mt-1">{{ Str::limit($reply->body, 100) }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .avatar-lg {
            width: 48px;
            height: 48px;
        }

        .message-content {
            line-height: 1.6;
        }
    </style>
@endpush
