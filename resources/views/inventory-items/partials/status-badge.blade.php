@php
    $badgeClass = match ($status) {
        'active' => 'success',
        'damaged' => 'danger',
        'inactive', 'replaced' => 'secondary',
        default => 'light text-dark',
    };
@endphp

<span class="badge bg-{{ $badgeClass }}">{{ ucfirst($status) }}</span>
