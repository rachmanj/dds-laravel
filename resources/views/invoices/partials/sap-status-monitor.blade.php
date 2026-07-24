@once
    <style>
        #sap-status-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2050;
            background: rgba(15, 23, 42, 0.55);
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        #sap-status-overlay.is-visible {
            display: flex;
        }

        .sap-status-panel {
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25);
            max-width: 28rem;
            width: 100%;
            padding: 1.5rem;
            text-align: center;
        }

        .sap-status-spinner {
            width: 3rem;
            height: 3rem;
            border: 0.35rem solid #e9ecef;
            border-top-color: #007bff;
            border-radius: 50%;
            animation: sap-status-spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }

        .sap-status-spinner.is-cancel {
            border-top-color: #dc3545;
        }

        .sap-status-panel.is-cancel-mode {
            border-top: 4px solid #dc3545;
        }

        @keyframes sap-status-spin {
            to {
                transform: rotate(360deg);
            }
        }

        .sap-status-pulse {
            animation: sap-status-pulse 1.5s ease-in-out infinite;
        }

        @keyframes sap-status-pulse {
            0%, 100% {
                opacity: 1;
            }

            50% {
                opacity: 0.55;
            }
        }
    </style>
@endonce

<div id="sap-status-overlay" aria-live="polite" aria-busy="true">
    <div class="sap-status-panel">
        <div id="sap-status-spinner" class="sap-status-spinner"></div>
        <h5 id="sap-status-title" class="mb-2">Submitting to SAP</h5>
        <p id="sap-status-message" class="text-muted mb-3 sap-status-pulse">Please wait while the AP Invoice is processed…</p>
        <div id="sap-status-result" class="d-none">
            <div id="sap-status-badge" class="mb-3"></div>
            <p id="sap-status-detail" class="text-muted small mb-3"></p>
            <a id="sap-status-invoice-link" href="#" class="btn btn-primary btn-sm">View Invoice</a>
            <button type="button" id="sap-status-close-btn" class="btn btn-secondary btn-sm ml-2">Close</button>
        </div>
    </div>
</div>

@once
    <script>
        window.DdsSapStatusMonitor = (function() {
            const activePollers = {};

            function badgeForStatus(data) {
                if (data.sap_status_badge) {
                    return data.sap_status_badge;
                }

                if (data.sap_status === 'posted') {
                    return '<span class="badge bg-success">SAP Posted: ' + (data.sap_doc_num || 'N/A') + '</span>';
                }

                if (data.sap_status === 'failed') {
                    return '<span class="badge bg-danger">SAP Failed</span>';
                }

                if (data.sap_status === 'pending') {
                    return '<span class="badge bg-warning">SAP Pending</span>';
                }

                if (data.sap_status === 'cancelling') {
                    return '<span class="badge bg-warning">SAP Cancelling…</span>';
                }

                if (data.sap_status === 'cancelled') {
                    return '<span class="badge bg-secondary">SAP Cancelled' +
                        (data.sap_cancellation_doc_num ? ': ' + data.sap_cancellation_doc_num : '') +
                        '</span>';
                }

                return '<span class="badge bg-secondary">Not Sent to SAP</span>';
            }

            function showOverlay(title, message, showSpinner, mode) {
                const overlay = document.getElementById('sap-status-overlay');
                if (!overlay) {
                    return;
                }

                const panel = overlay.querySelector('.sap-status-panel');
                const spinner = document.getElementById('sap-status-spinner');
                const isCancel = mode === 'cancel';

                if (panel) {
                    panel.classList.toggle('is-cancel-mode', isCancel);
                }
                if (spinner) {
                    spinner.classList.toggle('is-cancel', isCancel);
                }

                document.getElementById('sap-status-title').textContent = title;
                document.getElementById('sap-status-message').textContent = message;
                document.getElementById('sap-status-message').classList.toggle('d-none', !message);
                document.getElementById('sap-status-spinner').classList.toggle('d-none', !showSpinner);
                document.getElementById('sap-status-result').classList.add('d-none');
                overlay.classList.add('is-visible');
                overlay.setAttribute('aria-busy', showSpinner ? 'true' : 'false');
            }

            function isCancelPollTerminal(data) {
                if (data.sap_status === 'cancelled') {
                    return { terminal: true, success: true };
                }

                if (data.sap_status === 'posted' && data.sap_cancel_error_message) {
                    return { terminal: true, success: false };
                }

                return { terminal: false, success: false };
            }

            function showOverlayResult(data, invoiceUrl, operation) {
                const overlay = document.getElementById('sap-status-overlay');
                if (!overlay) {
                    return;
                }

                const isCancelOperation = operation === 'cancel' || data.sap_status === 'cancelled' ||
                    (data.sap_status === 'posted' && data.sap_cancel_error_message);
                const isSuccess = isCancelOperation ?
                    data.sap_status === 'cancelled' :
                    data.sap_status === 'posted';
                document.getElementById('sap-status-title').textContent = isSuccess ?
                    (isCancelOperation ? 'SAP cancellation completed' : 'SAP posting completed') :
                    (isCancelOperation ? 'SAP cancellation failed' : 'SAP posting failed');
                document.getElementById('sap-status-message').classList.add('d-none');
                document.getElementById('sap-status-spinner').classList.add('d-none');
                document.getElementById('sap-status-badge').innerHTML = badgeForStatus(data);
                if (data.sap_status === 'cancelled') {
                    document.getElementById('sap-status-detail').textContent =
                        'Cancellation Document: ' + (data.display_sap_cancellation_document || data.sap_cancellation_doc_num || '—');
                } else if (isCancelOperation && data.sap_cancel_error_message) {
                    document.getElementById('sap-status-detail').textContent = data.sap_cancel_error_message;
                } else if (data.sap_status === 'posted') {
                    document.getElementById('sap-status-detail').textContent =
                        'SAP Document: ' + (data.display_sap_document || data.sap_doc_num || '—');
                } else {
                    document.getElementById('sap-status-detail').textContent =
                        data.sap_cancel_error_message || data.sap_error_message || 'Unknown error';
                }
                document.getElementById('sap-status-invoice-link').href = invoiceUrl;
                document.getElementById('sap-status-result').classList.remove('d-none');
                overlay.setAttribute('aria-busy', 'false');
            }

            function hideOverlay() {
                const overlay = document.getElementById('sap-status-overlay');
                if (overlay) {
                    overlay.classList.remove('is-visible');
                }
            }

            function updateShowPage(data) {
                const statusCell = document.getElementById('invoice-sap-status-cell');
                if (statusCell) {
                    statusCell.innerHTML = badgeForStatus(data);
                }

                const sapDocEl = document.getElementById('invoice-sap-document-value');
                if (sapDocEl && data.display_sap_document) {
                    sapDocEl.textContent = data.display_sap_document;
                }

                const cancelDocEl = document.getElementById('invoice-sap-cancellation-document-value');
                if (cancelDocEl && data.display_sap_cancellation_document) {
                    cancelDocEl.textContent = data.display_sap_cancellation_document;
                }

                const sendRow = document.getElementById('invoice-sap-send-row');
                const retryRow = document.getElementById('invoice-sap-retry-row');
                const cancelRow = document.getElementById('invoice-sap-cancel-row');
                const retryCancelRow = document.getElementById('invoice-sap-cancel-retry-row');
                const cancelErrorRow = document.getElementById('invoice-sap-cancel-error-row');
                if (sendRow) {
                    sendRow.classList.toggle('d-none', !data.show_send_button);
                }
                if (retryRow) {
                    retryRow.classList.toggle('d-none', !data.show_retry_button);
                }
                if (cancelRow) {
                    cancelRow.classList.toggle('d-none', !data.show_cancel_button);
                }
                if (retryCancelRow) {
                    retryCancelRow.classList.toggle('d-none', !data.show_retry_cancel_button);
                }
                if (cancelErrorRow) {
                    cancelErrorRow.classList.toggle('d-none', !data.show_retry_cancel_button);
                    const cancelErrorMessageEl = document.getElementById('invoice-sap-cancel-error-message');
                    if (cancelErrorMessageEl && data.sap_cancel_error_message) {
                        cancelErrorMessageEl.textContent = data.sap_cancel_error_message;
                    }
                }
            }

            function poll(options) {
                const key = options.invoiceId;
                if (activePollers[key]) {
                    clearInterval(activePollers[key]);
                }

                let attempts = 0;
                const maxAttempts = options.maxAttempts || 60;
                const intervalMs = options.intervalMs || 2000;

                return new Promise(function(resolve, reject) {
                    const tick = function() {
                        attempts += 1;

                        fetch(options.statusUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            })
                            .then(function(response) {
                                if (!response.ok) {
                                    throw new Error('Unable to check SAP status');
                                }

                                return response.json();
                            })
                            .then(function(data) {
                                if (options.onUpdate) {
                                    options.onUpdate(data);
                                }

                                const terminalState = options.operation === 'cancel' ?
                                    isCancelPollTerminal(data) :
                                    { terminal: data.is_terminal, success: data.sap_status === 'posted' };

                                if (terminalState.terminal) {
                                    clearInterval(activePollers[key]);
                                    delete activePollers[key];

                                    if (typeof toastr !== 'undefined') {
                                        if (options.operation === 'cancel') {
                                            if (terminalState.success) {
                                                toastr.success('SAP AP Invoice cancelled: ' + (data.sap_cancellation_doc_num || ''));
                                            } else {
                                                toastr.error(data.sap_cancel_error_message || 'SAP cancellation failed');
                                            }
                                        } else if (data.sap_status === 'posted') {
                                            toastr.success('SAP AP Invoice posted: ' + (data.sap_doc_num || ''));
                                        } else if (data.sap_status === 'cancelled') {
                                            toastr.success('SAP AP Invoice cancelled: ' + (data.sap_cancellation_doc_num || ''));
                                        } else if (data.sap_status === 'failed') {
                                            toastr.error(data.sap_error_message || 'SAP posting failed');
                                        }
                                    }

                                    resolve(data);
                                    return;
                                }

                                if (attempts >= maxAttempts) {
                                    clearInterval(activePollers[key]);
                                    delete activePollers[key];
                                    const timeoutMessage = options.operation === 'cancel' ?
                                        'SAP cancellation is taking longer than expected. Please check again shortly.' :
                                        'SAP posting is taking longer than expected. Please check again shortly.';
                                    reject(new Error(timeoutMessage));
                                }
                            })
                            .catch(function(error) {
                                if (attempts >= maxAttempts) {
                                    clearInterval(activePollers[key]);
                                    delete activePollers[key];
                                    reject(error);
                                }
                            });
                    };

                    tick();
                    activePollers[key] = setInterval(tick, intervalMs);
                });
            }

            document.addEventListener('click', function(event) {
                if (event.target && event.target.id === 'sap-status-close-btn') {
                    hideOverlay();
                }
            });

            return {
                poll: poll,
                showOverlay: showOverlay,
                showOverlayResult: showOverlayResult,
                hideOverlay: hideOverlay,
                updateShowPage: updateShowPage,
                isCancelPollTerminal: isCancelPollTerminal,
            };
        })();
    </script>
@endonce
