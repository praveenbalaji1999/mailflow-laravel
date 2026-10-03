@extends('layouts.app')
@section('title', $campaign->campaign_name)
@section('nav-title', 'Campaign Details')

@section('content')
<div class="page-header">
  <div>
    <nav class="breadcrumb-nav mb-2">
      <a href="{{ route('campaigns.index') }}">Campaigns</a>
      <i class="bi bi-chevron-right"></i>
      <span>{{ $campaign->campaign_name }}</span>
    </nav>
    <h2>{{ $campaign->campaign_name }}</h2>
    <p>Campaign delivery summary and recipient status.</p>
  </div>
  <div class="page-actions">
    @include('partials.status-badge', ['status' => $campaign->status])
    @if(in_array($campaign->status, ['pending', 'processing']))
    <form method="POST" action="{{ route('campaigns.process', $campaign) }}" class="d-inline">
      @csrf
      <button class="btn btn-outline-primary"><i class="bi bi-play-fill"></i> Process Queue</button>
    </form>
    <form method="POST" action="{{ route('campaigns.cancel', $campaign) }}" class="d-inline"
      onsubmit="return confirm('Cancel this campaign?')">
      @csrf
      <button class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Cancel</button>
    </form>
    @endif
    <a href="{{ route('campaigns.compose') }}" class="btn btn-outline-primary"><i class="bi bi-files"></i> New Campaign</a>
    <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" class="d-inline"
      onsubmit="return confirm('Delete this campaign permanently?')">
      @csrf @method('DELETE')
      <button class="btn btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
    </form>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-card compact"><div class="stat-label">Total</div><div class="stat-value">{{ $campaign->total_recipients }}</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card compact"><div class="stat-label">Sent</div><div class="stat-value text-success">{{ $campaign->total_sent }}</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card compact"><div class="stat-label">Failed</div><div class="stat-value text-danger">{{ $campaign->total_failed }}</div></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card compact"><div class="stat-label">Pending</div><div class="stat-value text-warning">{{ $campaign->total_pending }}</div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="card-surface h-100">
      <div class="card-header-row"><h3>Campaign Information</h3></div>
      <dl class="info-list p-3">
        <div class="d-flex justify-content-between py-2 border-bottom"><dt class="text-muted">Campaign Name</dt><dd class="mb-0">{{ $campaign->campaign_name }}</dd></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><dt class="text-muted">Subject</dt><dd class="mb-0">{{ $campaign->subject ?: '—' }}</dd></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><dt class="text-muted">Created</dt><dd class="mb-0">{{ $campaign->created_at?->format('M j, Y · g:i A') ?? '—' }}</dd></div>
        <div class="d-flex justify-content-between py-2"><dt class="text-muted">Updated</dt><dd class="mb-0">{{ $campaign->updated_at?->format('M j, Y · g:i A') ?? '—' }}</dd></div>
      </dl>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card-surface h-100">
      <div class="card-header-row"><h3>Delivery Progress</h3></div>
      <div class="p-3">
        <div class="d-flex justify-content-between mb-2">
          <span class="text-secondary">Success rate</span>
          <strong>{{ $successRate }}%</strong>
        </div>
        <div class="progress progress-lg mb-4" style="height:10px;">
          <div class="progress-bar bg-success" style="width:{{ min(100, $successRate) }}%"></div>
        </div>
        <div class="delivery-breakdown d-flex gap-4">
          <div><span class="legend-dot d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#16A34A;"></span> Sent <strong>{{ $campaign->total_sent }}</strong></div>
          <div><span class="legend-dot d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#DC2626;"></span> Failed <strong>{{ $campaign->total_failed }}</strong></div>
          <div><span class="legend-dot d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:#F59E0B;"></span> Pending <strong>{{ $campaign->total_pending }}</strong></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card-surface">
  <div class="card-header-row">
    <h3>Recipient Delivery</h3>
    <form class="search-box sm" method="GET" action="{{ route('campaigns.show', $campaign) }}">
      <i class="bi bi-search"></i>
      <input type="search" class="form-control" name="q" value="{{ $q }}" placeholder="Search email...">
    </form>
  </div>
  <div class="table-wrap">
    <table class="table-modern">
      <thead><tr><th>Email</th><th>Status</th><th>Sent At</th><th>Error</th></tr></thead>
      <tbody>
        @forelse($deliveries as $d)
        <tr>
          <td>{{ $d->email }}</td>
          <td>@include('partials.status-badge', ['status' => $d->status])</td>
          <td>{{ $d->sent_at ? \Carbon\Carbon::parse($d->sent_at)->format('M j, Y · g:i A') : '—' }}</td>
          <td>{{ $d->error_message ? '<span class="text-danger small">'.e($d->error_message).'</span>' : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center text-muted py-4">No delivery rows.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="pagination-bar p-3">{{ $deliveries->links() }}</div>
</div>
@endsection
