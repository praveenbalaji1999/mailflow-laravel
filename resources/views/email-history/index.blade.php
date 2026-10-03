@extends('layouts.app')
@section('title', 'Email History')
@section('nav-title', 'Email History')

@section('content')
<div class="page-header">
  <div><h2>Email History</h2><p>Complete log of all sent, pending and failed emails.</p></div>
</div>

<div class="card-surface">
  <form class="toolbar-row p-3 d-flex gap-2 flex-wrap" method="GET" action="{{ route('email-history.index') }}">
    <div class="search-box">
      <i class="bi bi-search"></i>
      <input type="search" class="form-control" name="q" value="{{ $q }}" placeholder="Search email or subject...">
    </div>
    <select class="form-select form-select-sm w-auto" name="status" onchange="this.form.submit()">
      <option value="">All Status</option>
      <option value="sent" {{ $statusFilter === 'sent' ? 'selected' : '' }}>Sent</option>
      <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
      <option value="failed" {{ $statusFilter === 'failed' ? 'selected' : '' }}>Failed</option>
    </select>
    <button class="btn btn-sm btn-primary">Search</button>
  </form>

  @if($history->isEmpty())
  <div class="empty-state">
    <div class="empty-state-icon"><i class="bi bi-clock-history"></i></div>
    <h3>No email history yet</h3>
    <p>Email history will appear here once you send campaigns.</p>
  </div>
  @else
  <div class="table-wrap">
    <table class="table-modern">
      <thead><tr><th>Recipient</th><th>Subject</th><th>Campaign</th><th>Status</th><th>Sent At</th></tr></thead>
      <tbody>
        @foreach($history as $h)
        <tr>
          <td>{{ $h->recipient_email }}</td>
          <td>{{ Str::limit($h->subject, 50) }}</td>
          <td>{{ $h->campaign?->campaign_name ?? '—' }}</td>
          <td>@include('partials.status-badge', ['status' => $h->status])</td>
          <td>{{ $h->sent_at ? \Carbon\Carbon::parse($h->sent_at)->format('M j, Y · g:i A') : '—' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="pagination-bar p-3">{{ $history->withQueryString()->links() }}</div>
  @endif
</div>
@endsection
