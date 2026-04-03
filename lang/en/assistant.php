<?php

return [
    'title' => 'Domain Assistant',
    'intro' => 'Ask about DDS workflows: additional documents, invoices, distributions, reconcile, SAP processes, and related areas. Tools return permission-filtered counts and short lists.',
    'show_all_hint' => 'If you have the “see all records” permission, use the toggle so invoice and additional-document tools match the list screens with “Show all records” switched on.',
    'stream_label' => 'Stream response (SSE; only when tools are off)',
    'suggested_title' => 'Suggested prompts',
    'governance_hint' => 'Access to this page is controlled by the :permission permission (assign via user roles in Administration).',
    'permission_name' => 'access-domain-assistant',
    'prompts' => [
        'summary' => 'Give me a short summary of what I can see in DDS right now (counts only).',
        'invoices' => 'List up to 10 recent invoices I am allowed to see.',
        'distributions' => 'What distributions can I see recently?',
        'reconcile' => 'Show my recent reconcile rows.',
        'suppliers' => 'Search suppliers containing “PT” in the name or code.',
    ],
    'daily_limit' => 'You have reached the daily limit for assistant messages. Try again tomorrow or contact an administrator.',
    'threads_heading' => 'Conversations',
    'new_chat' => 'New chat',
    'untitled_thread' => 'New conversation',
    'delete_thread' => 'Delete',
    'switch_thread' => 'Open',
];
