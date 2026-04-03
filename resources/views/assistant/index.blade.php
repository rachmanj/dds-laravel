@extends('layouts.main')

@section('title_page')
    {{ __('assistant.title') }}
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
    <li class="breadcrumb-item active">{{ __('assistant.title') }}</li>
@endsection

@push('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style>
        .assistant-terminal,
        .assistant-terminal-input {
            font-family: 'Share Tech Mono', 'Courier New', Courier, monospace;
            background-color: #000000 !important;
            color: #33ff33 !important;
            border: 1px solid #1a3d1a !important;
            box-shadow: inset 0 0 12px rgba(51, 255, 51, 0.08);
        }

        .assistant-terminal::placeholder,
        .assistant-terminal-input::placeholder {
            color: #22cc22 !important;
            opacity: 0.85;
        }

        .assistant-terminal-input:focus {
            color: #39ff14 !important;
            background-color: #000000 !important;
            border-color: #33ff33 !important;
            box-shadow: 0 0 0 0.15rem rgba(51, 255, 51, 0.25);
        }

        .assistant-terminal-msg {
            font-family: 'Share Tech Mono', 'Courier New', Courier, monospace;
            background-color: #050505;
            color: #33ff33;
            border: 1px solid #1f3320;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .assistant-terminal-label {
            font-family: 'Share Tech Mono', 'Courier New', Courier, monospace;
            color: #39ff14;
            text-shadow: 0 0 6px rgba(57, 255, 20, 0.35);
        }

        .assistant-terminal-empty {
            font-family: 'Share Tech Mono', 'Courier New', Courier, monospace;
            color: #33ff33 !important;
            margin: 0;
        }

        .assistant-suggestion-btn {
            font-size: 0.8rem;
            margin: 0 0.35rem 0.35rem 0;
        }

        .assistant-thread-item {
            text-align: left;
            border: 1px solid #1f3320;
            background: #050505;
            color: #33ff33;
            font-family: 'Share Tech Mono', monospace;
            font-size: 0.8rem;
        }

        .assistant-thread-item:hover {
            border-color: #33ff33;
            color: #39ff14;
        }

        .assistant-thread-item.active {
            border-color: #33ff33;
            box-shadow: 0 0 0 1px rgba(51, 255, 51, 0.35);
        }

        .assistant-thread-item .assistant-thread-delete {
            opacity: 0.65;
        }

        .assistant-thread-item .assistant-thread-delete:hover {
            opacity: 1;
            color: #ff6b6b;
        }
    </style>
@endpush

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-12 col-xl-11 mx-auto">
                <div class="row">
                    <div class="col-12 col-lg-3 mb-3">
                        <div class="card card-outline card-secondary h-100">
                            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                                <span class="small font-weight-bold">{{ __('assistant.threads_heading') }}</span>
                                <button type="button" class="btn btn-xs btn-primary btn-sm" id="assistant-new-chat">
                                    <i class="fas fa-plus mr-1"></i> {{ __('assistant.new_chat') }}
                                </button>
                            </div>
                            <div class="card-body p-2" id="assistant-thread-list-wrap">
                                <p class="text-muted small mb-0" id="assistant-thread-loading">Loading…</p>
                                <div id="assistant-thread-list" class="d-flex flex-column"
                                    style="max-height: 520px; overflow-y: auto;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-9">
                        <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-robot mr-2"></i> {{ __('assistant.title') }}
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="assistant-clear"
                                title="Clear conversation">
                                <i class="fas fa-trash-alt"></i> Clear
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">{{ __('assistant.intro') }}</p>
                        <p class="text-muted small mb-2">{{ __('assistant.show_all_hint') }}</p>
                        @can('view-admin')
                            <p class="text-muted small mb-3">
                                {{ __('assistant.governance_hint', ['permission' => __('assistant.permission_name')]) }}
                            </p>
                        @endcan
                        @can('see-all-record-switch')
                            <div class="form-group form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="assistant-show-all">
                                <label class="form-check-label small" for="assistant-show-all">
                                    Show all records (invoice &amp; additional documents scope — same as list switch)
                                </label>
                            </div>
                        @endcan
                        @if (config('services.domain_assistant.streaming_enabled') && ! config('services.domain_assistant.tools_enabled'))
                            <div class="form-group form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="assistant-stream">
                                <label class="form-check-label small" for="assistant-stream">{{ __('assistant.stream_label') }}
                                </label>
                            </div>
                        @endif
                        <div class="mb-3">
                            <span class="text-muted small d-block mb-1">{{ __('assistant.suggested_title') }}</span>
                            @foreach (__('assistant.prompts') as $key => $promptText)
                                <button type="button" class="btn btn-sm btn-outline-secondary assistant-suggestion-btn"
                                    data-prompt="{{ $promptText }}">{{ \Illuminate\Support\Str::limit($promptText, 42) }}</button>
                            @endforeach
                        </div>
                        <div id="assistant-thread" class="assistant-terminal rounded p-3 mb-3"
                            style="min-height: 280px; max-height: 480px; overflow-y: auto;">
                            <p class="assistant-terminal-empty small mb-0" id="assistant-empty">Start by typing a question
                                below.</p>
                        </div>
                        <form id="assistant-form" autocomplete="off">
                            @csrf
                            <div class="form-group mb-2">
                                <label for="assistant-input" class="sr-only">Message</label>
                                <textarea id="assistant-input" class="form-control assistant-terminal-input" rows="3"
                                    maxlength="12000" placeholder="Type your question…" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" id="assistant-send">
                                <i class="fas fa-paper-plane mr-1"></i> Send
                            </button>
                            <span class="text-muted small ml-2 d-none" id="assistant-status">Thinking…</span>
                        </form>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('js')
    <script>
        window.DOMAIN_ASSISTANT_CAN_STREAM = @json(config('services.domain_assistant.streaming_enabled') && ! config('services.domain_assistant.tools_enabled'));
        (function() {
            const thread = document.getElementById('assistant-thread');
            const input = document.getElementById('assistant-input');
            const form = document.getElementById('assistant-form');
            const sendBtn = document.getElementById('assistant-send');
            const status = document.getElementById('assistant-status');
            const clearBtn = document.getElementById('assistant-clear');
            const newChatBtn = document.getElementById('assistant-new-chat');
            const threadListEl = document.getElementById('assistant-thread-list');
            const threadLoadingEl = document.getElementById('assistant-thread-loading');
            const showAllEl = document.getElementById('assistant-show-all');
            const streamEl = document.getElementById('assistant-stream');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const LS_SHOW_ALL = 'dds_assistant_show_all';
            const assistantBase = @json(url('/assistant'));
            const untitledLabel = @json(__('assistant.untitled_thread'));
            let currentConversationId = null;

            const jsonHeaders = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            };

            async function fetchJson(url, options = {}) {
                const res = await fetch(url, {
                    credentials: 'same-origin',
                    ...options,
                    headers: {
                        ...jsonHeaders,
                        ...options.headers,
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const err = data.error || ('HTTP ' + res.status);
                    throw new Error(err);
                }
                return data;
            }

            document.querySelectorAll('.assistant-suggestion-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const t = btn.getAttribute('data-prompt');
                    if (t) {
                        input.value = t;
                        input.focus();
                    }
                });
            });

            if (showAllEl) {
                const saved = localStorage.getItem(LS_SHOW_ALL);
                if (saved === '1') {
                    showAllEl.checked = true;
                }
                showAllEl.addEventListener('change', function() {
                    localStorage.setItem(LS_SHOW_ALL, showAllEl.checked ? '1' : '0');
                });
            }

            function appendBubble(role, text) {
                const placeholder = document.getElementById('assistant-empty');
                if (placeholder) {
                    placeholder.remove();
                }
                const wrap = document.createElement('div');
                wrap.className = 'mb-3';
                const label = document.createElement('div');
                label.className = 'small font-weight-bold mb-1 assistant-terminal-label';
                label.textContent = role === 'user' ? '> you' : '> assistant';
                const body = document.createElement('div');
                body.className = 'p-2 rounded assistant-terminal-msg';
                body.textContent = text;
                wrap.appendChild(label);
                wrap.appendChild(body);
                thread.appendChild(wrap);
                thread.scrollTop = thread.scrollHeight;
                return body;
            }

            function setLoading(on) {
                sendBtn.disabled = on;
                input.disabled = on;
                if (streamEl) {
                    streamEl.disabled = on;
                }
                status.classList.toggle('d-none', !on);
            }

            async function readSseStream(response, bodyEl) {
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                while (true) {
                    const {
                        done,
                        value
                    } = await reader.read();
                    if (done) {
                        break;
                    }
                    buffer += decoder.decode(value, {
                        stream: true
                    });
                    const parts = buffer.split('\n\n');
                    buffer = parts.pop() || '';
                    for (const block of parts) {
                        for (const line of block.split('\n')) {
                            const t = line.trim();
                            if (!t.startsWith('data: ')) {
                                continue;
                            }
                            let payload;
                            try {
                                payload = JSON.parse(t.slice(6));
                            } catch (e) {
                                continue;
                            }
                            if (payload.c) {
                                bodyEl.textContent += payload.c;
                                thread.scrollTop = thread.scrollHeight;
                            }
                            if (payload.error && typeof toastr !== 'undefined') {
                                toastr.error(payload.error);
                            }
                        }
                    }
                }
            }

            function renderThreadList(conversations) {
                if (!threadListEl) {
                    return;
                }
                threadListEl.innerHTML = '';
                conversations.forEach(function(c) {
                    const row = document.createElement('div');
                    row.className = 'd-flex align-items-stretch mb-1';
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'assistant-thread-item flex-grow-1 rounded px-2 py-1';
                    if (Number(c.id) === Number(currentConversationId)) {
                        btn.classList.add('active');
                    }
                    btn.textContent = c.title || untitledLabel;
                    btn.addEventListener('click', async function() {
                        if (Number(c.id) === Number(currentConversationId)) {
                            return;
                        }
                        try {
                            await fetchJson(assistantBase + '/conversations/' + c.id + '/select', {
                                method: 'PATCH',
                                body: '{}',
                            });
                            currentConversationId = c.id;
                            renderThreadList(conversations);
                            await loadMessages(c.id);
                        } catch (e) {
                            if (typeof toastr !== 'undefined') {
                                toastr.error(e.message || 'Could not switch conversation.');
                            }
                        }
                    });
                    const del = document.createElement('button');
                    del.type = 'button';
                    del.className = 'btn btn-sm btn-link assistant-thread-delete p-1 ml-1';
                    del.setAttribute('title', @json(__('assistant.delete_thread')));
                    del.innerHTML = '<i class="fas fa-times"></i>';
                    del.addEventListener('click', async function(ev) {
                        ev.stopPropagation();
                        if (!window.confirm('Delete this conversation?')) {
                            return;
                        }
                        try {
                            const delRes = await fetch(assistantBase + '/conversations/' + c.id, {
                                method: 'DELETE',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });
                            if (!delRes.ok) {
                                throw new Error('HTTP ' + delRes.status);
                            }
                            if (Number(c.id) === Number(currentConversationId)) {
                                currentConversationId = null;
                            }
                            await initThreads();
                        } catch (err) {
                            if (typeof toastr !== 'undefined') {
                                toastr.error('Delete failed.');
                            }
                        }
                    });
                    row.appendChild(btn);
                    row.appendChild(del);
                    threadListEl.appendChild(row);
                });
            }

            async function loadMessages(conversationId) {
                const data = await fetchJson(assistantBase + '/conversations/' + conversationId + '/messages');
                thread.innerHTML = '';
                if (!data.messages || !data.messages.length) {
                    thread.innerHTML =
                        '<p class="assistant-terminal-empty small mb-0" id="assistant-empty">Start by typing a question below.</p>';
                    return;
                }
                data.messages.forEach(function(m) {
                    appendBubble(m.role === 'user' ? 'user' : 'assistant', m.content || '');
                });
            }

            async function refreshSidebarTitles() {
                try {
                    const state = await fetchJson(assistantBase + '/conversations');
                    renderThreadList(state.conversations || []);
                } catch (e) {
                    /* ignore */
                }
            }

            async function initThreads() {
                if (threadLoadingEl) {
                    threadLoadingEl.classList.remove('d-none');
                }
                try {
                    let state = await fetchJson(assistantBase + '/conversations');
                    if (!state.conversations || !state.conversations.length) {
                        const created = await fetchJson(assistantBase + '/conversations', {
                            method: 'POST',
                            body: '{}',
                        });
                        currentConversationId = created.conversation.id;
                        state = await fetchJson(assistantBase + '/conversations');
                    } else {
                        let activeId = state.active_conversation_id;
                        const ids = new Set(state.conversations.map(function(x) {
                            return x.id;
                        }));
                        if (!activeId || !ids.has(activeId)) {
                            activeId = state.conversations[0].id;
                            await fetchJson(assistantBase + '/conversations/' + activeId + '/select', {
                                method: 'PATCH',
                                body: '{}',
                            });
                        }
                        currentConversationId = activeId;
                    }
                    renderThreadList(state.conversations);
                    await loadMessages(currentConversationId);
                } catch (e) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(e.message || 'Could not load conversations.');
                    }
                } finally {
                    if (threadLoadingEl) {
                        threadLoadingEl.classList.add('d-none');
                    }
                }
            }

            function buildChatPayload(messageText) {
                const payload = {
                    message: messageText,
                    conversation_id: currentConversationId,
                };
                if (showAllEl && showAllEl.checked) {
                    payload.show_all_records = true;
                }
                return payload;
            }

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const msg = (input.value || '').trim();
                if (!msg) {
                    return;
                }
                if (!currentConversationId) {
                    await initThreads();
                }
                appendBubble('user', msg);
                input.value = '';
                setLoading(true);
                try {
                    const payload = buildChatPayload(msg);
                    const useStream = window.DOMAIN_ASSISTANT_CAN_STREAM && streamEl && streamEl.checked;
                    if (useStream) {
                        payload.stream = true;
                        const assistantBody = appendBubble('assistant', '');
                        const res = await fetch(@json(route('assistant.chat')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'text/event-stream',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(payload),
                        });
                        if (!res.ok) {
                            const errText = await res.text();
                            let err = 'HTTP ' + res.status;
                            try {
                                const j = JSON.parse(errText);
                                err = j.error || err;
                            } catch (x) {
                                /* ignore */
                            }
                            if (typeof toastr !== 'undefined') {
                                toastr.error(err);
                            }
                            return;
                        }
                        await readSseStream(res, assistantBody);
                        await refreshSidebarTitles();
                    } else {
                        const res = await fetch(@json(route('assistant.chat')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(payload),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const err = data.error || ('HTTP ' + res.status);
                            if (typeof toastr !== 'undefined') {
                                toastr.error(err);
                            } else {
                                alert(err);
                            }
                            return;
                        }
                        appendBubble('assistant', data.message || '');
                        await refreshSidebarTitles();
                    }
                } catch (err) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(err.message || 'Request failed. Please try again.');
                    }
                } finally {
                    setLoading(false);
                }
            });

            if (newChatBtn) {
                newChatBtn.addEventListener('click', async function() {
                    try {
                        const created = await fetchJson(assistantBase + '/conversations', {
                            method: 'POST',
                            body: '{}',
                        });
                        currentConversationId = created.conversation.id;
                        const state = await fetchJson(assistantBase + '/conversations');
                        renderThreadList(state.conversations || []);
                        thread.innerHTML =
                            '<p class="assistant-terminal-empty small mb-0" id="assistant-empty">Start by typing a question below.</p>';
                    } catch (e) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error(e.message || 'Could not start a new chat.');
                        }
                    }
                });
            }

            clearBtn.addEventListener('click', async function() {
                try {
                    const res = await fetch(@json(route('assistant.clear')), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (res.ok) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Conversation cleared.');
                        }
                        await initThreads();
                    }
                } catch (e) {
                    /* ignore */
                }
            });

            initThreads();
        })();
    </script>
@endpush
