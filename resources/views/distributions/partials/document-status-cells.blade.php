<td>
    @if ($doc->skip_verification)
        @if ($standaloneOutOfLocation ?? false)
            <span class="badge badge-warning"
                title="Document was not physically available in origin department at distribution creation">
                <i class="fas fa-exclamation-triangle"></i> Out of Location
            </span>
        @else
            <span class="badge badge-secondary">Not included in this distribution</span>
        @endif
    @elseif ($doc->sender_verified)
        <span
            class="badge badge-{{ $doc->sender_verification_status === 'verified' ? 'success' : ($doc->sender_verification_status === 'missing' ? 'warning' : 'danger') }}">
            {{ ucfirst($doc->sender_verification_status) }}
        </span>
    @else
        <span class="badge badge-secondary">Pending</span>
    @endif
</td>
<td>
    @if ($doc->skip_verification)
        @if ($standaloneOutOfLocation ?? false)
            <span class="badge badge-warning"
                title="Document was not physically available in origin department at distribution creation">
                <i class="fas fa-exclamation-triangle"></i> Out of Location
            </span>
        @else
            <span class="badge badge-secondary">Not included in this distribution</span>
        @endif
    @elseif ($doc->receiver_verified)
        <span
            class="badge badge-{{ $doc->receiver_verification_status === 'verified' ? 'success' : ($doc->receiver_verification_status === 'missing' ? 'warning' : 'danger') }}">
            {{ ucfirst($doc->receiver_verification_status) }}
        </span>
    @else
        <span class="badge badge-secondary">Pending</span>
    @endif
</td>
<td>
    @if ($doc->skip_verification)
        <span class="badge badge-secondary">Not included in this distribution</span>
    @else
        <span class="badge {{ $doc->verification_status_badge_class }}">
            {{ $doc->verification_status_display }}
        </span>
    @endif
</td>
