@extends('layouts.app')
@section('title', 'Recipients')
@section('nav-title', 'Recipients')

@section('content')
<div class="page-header">
  <div><h2>Recipients</h2><p>Manage your email recipients.</p></div>
  <div class="page-actions">
    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importCsvModal">
      <i class="bi bi-upload"></i> Import CSV
    </button>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecipientModal">
      <i class="bi bi-plus-lg"></i> Add Recipient
    </button>
  </div>
</div>

<div class="card-surface">
  <form class="toolbar-row p-3" method="GET" action="{{ route('recipients.index') }}">
    <div class="search-box">
      <i class="bi bi-search"></i>
      <input type="search" class="form-control" name="q" value="{{ $q }}" placeholder="Search by name or email...">
    </div>
    <div class="toolbar-filters d-flex gap-2 flex-wrap">
      <select class="form-select form-select-sm" name="group" onchange="this.form.submit()">
        <option value="0">All Groups</option>
        @foreach($groups as $g)
          <option value="{{ $g->id }}" {{ $groupFilter == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
      <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
        <option value="">All Status</option>
        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive</option>
      </select>
      <button class="btn btn-sm btn-primary">Search</button>
    </div>
  </form>

  @if($recipients->isEmpty())
  <div class="empty-state">
    <div class="empty-state-icon"><i class="bi bi-people"></i></div>
    <h3>No recipients found</h3>
    <p>Add your first recipient or import a CSV.</p>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecipientModal">
      <i class="bi bi-plus-lg"></i> Add Recipient
    </button>
  </div>
  @else
  <div class="table-wrap">
    <table class="table-modern">
      <thead><tr><th>Name</th><th>Email</th><th>Group</th><th>Status</th><th>Added</th><th>Actions</th></tr></thead>
      <tbody>
        @foreach($recipients as $r)
        <tr>
          <td>
            <div class="user-cell">
              <div class="avatar sm">{{ $r->initials }}</div>
              <span>{{ $r->name }}</span>
            </div>
          </td>
          <td>{{ $r->email }}</td>
          <td>{{ $r->groups->first()?->name ?? '—' }}</td>
          <td>@include('partials.status-badge', ['status' => $r->status])</td>
          <td>{{ $r->created_at?->format('M j, Y') ?? '—' }}</td>
          <td>
            <div class="action-btns">
              <button class="btn-action" data-bs-toggle="modal" data-bs-target="#editRecipientModal"
                data-id="{{ $r->id }}" data-name="{{ $r->name }}" data-email="{{ $r->email }}"
                data-status="{{ $r->status }}" data-group="{{ $r->groups->first()?->id ?? 0 }}">
                <i class="bi bi-pencil"></i>
              </button>
              <form method="POST" action="{{ route('recipients.destroy', $r) }}" class="d-inline"
                onsubmit="return confirm('Delete {{ addslashes($r->name) }}?')">
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
  <div class="pagination-bar p-3">
    {{ $recipients->withQueryString()->links() }}
  </div>
  @endif
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addRecipientModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('recipients.store') }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">Add Recipient</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" required></div>
          <div class="mb-3"><label class="form-label">Email Address <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" required></div>
          <div class="mb-3"><label class="form-label">Group</label>
            <select class="form-select" name="group_id">
              <option value="">No group</option>
              @foreach($groups as $g)
                <option value="{{ $g->id }}">{{ $g->name }}</option>
              @endforeach
            </select></div>
          <div class="mb-0"><label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Recipient</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editRecipientModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="editRecipientForm" action="">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Edit Recipient</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" id="editName" required></div>
          <div class="mb-3"><label class="form-label">Email Address <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" id="editEmail" required></div>
          <div class="mb-3"><label class="form-label">Group</label>
            <select class="form-select" name="group_id" id="editGroup">
              <option value="">No group</option>
              @foreach($groups as $g)
                <option value="{{ $g->id }}">{{ $g->name }}</option>
              @endforeach
            </select></div>
          <div class="mb-0"><label class="form-label">Status</label>
            <select class="form-select" name="status" id="editStatus">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Import CSV Modal --}}
<div class="modal fade" id="importCsvModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('recipients.import') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-header"><h5 class="modal-title">Import CSV</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="attachment-zone mb-3">
            <i class="bi bi-cloud-arrow-up"></i>
            <p class="mb-1"><strong>Choose a CSV file</strong></p>
            <p class="text-muted small mb-2">Required columns: name, email — Optional: group</p>
            <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Import</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
<script>
document.getElementById('editRecipientModal')?.addEventListener('show.bs.modal', function (e) {
  const btn = e.relatedTarget;
  if (!btn) return;
  document.getElementById('editName').value   = btn.dataset.name  || '';
  document.getElementById('editEmail').value  = btn.dataset.email || '';
  document.getElementById('editStatus').value = btn.dataset.status || 'active';
  document.getElementById('editGroup').value  = btn.dataset.group || '';
  document.getElementById('editRecipientForm').action = '/recipients/' + btn.dataset.id;
});
</script>
@endpush
