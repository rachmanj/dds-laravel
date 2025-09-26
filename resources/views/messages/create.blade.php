@extends('layouts.main')

@section('title', 'Compose Message')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        @if ($replyTo)
                            Reply to Message
                        @else
                            Compose New Message
                        @endif
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('messages.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Back to Inbox
                        </a>
                    </div>
                </div>
                <form action="{{ route('messages.store') }}" method="POST" enctype="multipart/form-data" id="message-form">
                    @csrf
                    <div class="card-body">
                        @if ($replyTo)
                            <input type="hidden" name="parent_id" value="{{ $replyTo->id }}">
                            <div class="alert alert-info">
                                <strong>Replying to:</strong> {{ $replyTo->subject }}
                                <br>
                                <small>From: {{ $replyTo->sender->name }} ({{ $replyTo->sender->email }})</small>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="receiver_id">To <span class="text-danger">*</span></label>
                            <select name="receiver_id" id="receiver_id"
                                class="form-control select2bs4 @error('receiver_id') is-invalid @enderror" required>
                                <option value="">Select recipient...</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ old('receiver_id') == $user->id || ($replyTo && $replyTo->sender_id == $user->id) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('receiver_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" id="subject"
                                class="form-control @error('subject') is-invalid @enderror"
                                value="{{ old('subject', $replyTo ? 'Re: ' . $replyTo->subject : '') }}" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="body">Message <span class="text-danger">*</span></label>
                            <textarea name="body" id="body" rows="10" class="form-control @error('body') is-invalid @enderror"
                                placeholder="Type your message here..." required>{{ old('body', $replyTo ? "\n\n--- Original Message ---\n" . $replyTo->body : '') }}</textarea>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="attachments">Attachments</label>
                            <div class="custom-file">
                                <input type="file" name="attachments[]" id="attachments"
                                    class="custom-file-input @error('attachments.*') is-invalid @enderror" multiple>
                                <label class="custom-file-label" for="attachments">Choose files...</label>
                            </div>
                            <small class="form-text text-muted">
                                You can select multiple files. Maximum file size: 10MB per file.
                            </small>
                            @error('attachments.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="send-button">
                            <i class="fas fa-paper-plane mr-1"></i>
                            <span class="btn-text">Send Message</span>
                            <span class="btn-loading d-none">
                                <i class="fas fa-spinner fa-spin mr-1"></i>
                                Sending...
                            </span>
                        </button>
                        <a href="{{ route('messages.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        .btn-sending {
            animation: pulse 1.5s ease-in-out infinite;
            transform: scale(1.05);
        }

        .btn-success {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            animation: successPulse 0.6s ease-in-out;
        }

        @keyframes pulse {
            0% {
                transform: scale(1.05);
                box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7);
            }

            70% {
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(0, 123, 255, 0);
            }

            100% {
                transform: scale(1.05);
                box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
            }
        }

        @keyframes successPulse {
            0% {
                transform: scale(1.05);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1.05);
            }
        }

        /* Smooth transitions for button states */
        #send-button {
            transition: all 0.3s ease;
        }

        .btn-loading {
            transition: all 0.3s ease;
        }

        /* Form validation animation */
        .is-invalid {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }
    </style>
@endpush

@push('js')
    <script>
        $(document).ready(function() {
            // Initialize Select2 for recipient dropdown
            $('#receiver_id').select2({
                theme: 'bootstrap4',
                placeholder: 'Select recipient...',
                allowClear: true,
                width: '100%'
            });

            // Update file input label when files are selected
            $('#attachments').on('change', function() {
                var files = this.files;
                var label = $(this).next('.custom-file-label');

                if (files.length > 0) {
                    if (files.length === 1) {
                        label.text(files[0].name);
                    } else {
                        label.text(files.length + ' files selected');
                    }
                } else {
                    label.text('Choose files...');
                }
            });

            // Auto-focus on body textarea
            $('#body').focus();

            // Handle form submission with animation
            $('#message-form').on('submit', function(e) {
                e.preventDefault();

                var $form = $(this);
                var $sendButton = $('#send-button');
                var $btnText = $('.btn-text');
                var $btnLoading = $('.btn-loading');
                var formData = new FormData(this);

                // Show loading state
                $sendButton.prop('disabled', true);
                $btnText.addClass('d-none');
                $btnLoading.removeClass('d-none');

                // Add sending animation to the button
                $sendButton.addClass('btn-sending');

                // Submit form via AJAX
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Show success animation
                        $sendButton.removeClass('btn-sending').addClass('btn-success');
                        $btnLoading.html('<i class="fas fa-check mr-1"></i>Sent!');

                        // Show success toast
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message, 'Success!', {
                                timeOut: 3500,
                                onHidden: function() {
                                    // Redirect after animation
                                    window.location.href = response.redirect;
                                }
                            });
                        } else {
                            // Fallback redirect
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 2500);
                        }
                    },
                    error: function(xhr) {
                        // Reset button state
                        $sendButton.prop('disabled', false).removeClass(
                            'btn-sending btn-success');
                        $btnText.removeClass('d-none');
                        $btnLoading.addClass('d-none').html(
                            '<i class="fas fa-spinner fa-spin mr-1"></i>Sending...');

                        // Show error message
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Failed to send message. Please try again.', 'Error!');
                        } else {
                            alert('Failed to send message. Please try again.');
                        }

                        // Handle validation errors
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            for (var field in errors) {
                                var $field = $('[name="' + field + '"]');
                                $field.addClass('is-invalid');
                                $field.siblings('.invalid-feedback').text(errors[field][0]);
                            }
                        }
                    }
                });
            });
        });
    </script>
@endpush
