@extends('layouts.app')
@section('title', 'Campaigns')
@section('nav-title', 'Campaigns')

@section('content')
<div class="page-header">
  <div><h2>Campaigns</h2><p>Manage and track all your email campaigns.</p></div>
  <div class="page-actions">
    <a href="{{ route('campaigns.compose') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Campaign</a>
  </div>
</div>

<div class="card-surface">
  @if($campaigns->isEmpty())
  <div class="empty-state">
    <div class="empty-state-icon"><i class="bi bi-megaphone"></i></div>
    <h3>No campaigns yet</h3>
    <p>Create your first campaign to start sending emails.</p>
    <a href="{{ route('campaigns.compose') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Campaign</a>
  </div>
  @else
  <div class="table-wrap">
    <table class="table-modern">
      <thead><tr><th>Campaign</th><th>Recipients</th><th>Sent</th><th>Failed</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
      <tbody>
        @foreach($campaigns as $c)
        <tr>
          <td><strong>{{ $c->campaign_name }}</strong><br><small class="text-muted">{{ Str::limit($c->subject, 50) }}</small></td>
          <td>{{ $c->total_recipients }}</td>
          <td>{{ $c->total_sent }}</td>
          <td>{{ $c->total_failed }}</td>
          <td>@include('partials.status-badge', ['status' => $c->status])</td>
          <td>{{ $c->created_at?->format('M j, Y') ?? '—' }}</td>
          <td>
            <div class="action-btns">
              <a href="{{ route('campaigns.show', $c) }}" class="btn-action"><i class="bi bi-eye"></i></a>
              <form method="POST" action="{{ route('campaigns.destroy', $c) }}" class="d-inline"
                onsubmit="return confirm('Delete this campaign?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-action danger"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="pagination-bar p-3">{{ $campaigns->links() }}</div>
  @endif
</div>
@endsection
