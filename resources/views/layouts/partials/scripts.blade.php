<!-- jQuery -->
<script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- SweetAlert2 -->
<script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>
<!-- Select2 -->
<script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('adminlte/dist/js/adminlte.min.js') }}"></script>

<script>
    // Logout confirmation function
    function confirmLogout() {
        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out of the system.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, logout!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('logout-form').submit();
            }
        });
    }

    // Message notification system
    function updateUnreadMessageCount() {
        $.get('{{ route('messages.unread-count') }}', function(data) {
            const count = data.count;
            $('#unread-messages-count').text(count);
            $('#sidebar-unread-count').text(count);

            // Hide badge if no unread messages
            if (count === 0) {
                $('#unread-messages-count').hide();
                $('#sidebar-unread-count').hide();
            } else {
                $('#unread-messages-count').show();
                $('#sidebar-unread-count').show();
            }
        }).fail(function() {
            console.log('Failed to update unread message count');
        });
    }

    // Update message count every 30 seconds
    setInterval(updateUnreadMessageCount, 30000);

    // Initialize Toastr for notifications
    if (typeof toastr !== "undefined") {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 5000,
            extendedTimeOut: 1000,
            preventDuplicates: true,
        };
    }

    // Disable Dropzone auto-discovery to prevent unwanted file choosers
    if (typeof Dropzone !== "undefined") {
        Dropzone.autoDiscover = false;
    }
</script>

@yield('scripts')
