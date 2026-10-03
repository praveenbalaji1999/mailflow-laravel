@php
$map = [
    'completed'  => 'badge-completed',
    'sent'       => 'badge-completed',
    'active'     => 'badge-completed',
    'processing' => 'badge-processing',
    'pending'    => 'badge-pending',
    'draft'      => 'badge-pending',
    'failed'     => 'badge-failed',
    'inactive'   => 'badge-failed',
    'cancelled'  => 'badge-failed',
];
$class = $map[strtolower($status)] ?? 'badge-pending';
@endphp
<span class="badge-status {{ $class }}">{{ ucfirst($status) }}</span>
