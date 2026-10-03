@extends('layouts.app')
@section('title', $group->name)
@section('nav-title', 'Group Members')

@section('content')
<div class="page-header">
  <div>
    <nav class="breadcrumb-nav mb-2">
      <a href="{{ route('groups.index') }}">Groups</a>
      <i class="bi bi-chevron-right"></i>
      <span>{{ $group->name }}</span>
    </nav>
    <h2>{{ $group->name }}</h2>
    <p>{{ $group->recipients_count ?? $recipients->total() }} members</p>
  </div>
  <div class="page-actions">
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
      <i class="bi bi-person-plus"></i> Add Member
    </button>
    <a href="{{ route('campaigns.compose', ['group_id' => $group->id]) }}" class="btn btn-primary">
      <i class="bi bi-send"></i> Send Email to Group
    </a>
  </div>
</div>

<div class="card-surface">
  <form class="p-3" method="GET">
    <div class="search-box">
      <i class="bi bi-search"></i>
      <input type="search" class="form-control" name="q" value="{{ $q }}" placeholder="Search members...">
    </div>
  </form>

  @if($recipients->isEmpty())
  <div class="empty-state">
    <div class="empty-state-icon"><i class="bi bi-people"></i></div>
    <h3>No members in this group</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">Add Member</button>
  </div>
  @else
  <div class="table-wrap">
    <table class="table-modern">
      <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        @foreach($recipients as $r)
        <tr>
          <td><div class="user-cell"><div class="avatar sm">{{ $r->initials }}</div><span>{{ $r->name }}</span></div></td>
          <td>{{ $r->email }}</td>
          <td>@include('partials.status-badge', ['status' => $r->status])</td>
          <td>
            <form method="POST" action="{{ route('groups.members.remove', [$group, $r]) }}"
              onsubmit="return confirm('Remove from group?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn-action danger"><i class="bi bi-person-dash"></i></button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="pagination-bar p-3">{{ $recipients->withQueryString()->links() }}</div>
  @endif
</div>

{{-- Add Member Modal --}}
<div class="modal fade" id="addMemberModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('groups.members.add', $group) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">Add Member</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">Select Recipient</label>
          <select class="form-select" name="recipient_id" required>
            <option value="">Choose a recipient...</option>
            @foreach($allRecipients as $r)
              <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->email }})</option>
            @endforeach
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Member</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
