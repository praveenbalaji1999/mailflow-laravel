@extends('layouts.app')
@section('title', 'Groups')
@section('nav-title', 'Groups')

@section('content')
<div class="page-header">
  <div><h2>Groups</h2><p>Organize recipients into groups for targeted campaigns.</p></div>
  <div class="page-actions">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
      <i class="bi bi-plus-lg"></i> Create Group
    </button>
  </div>
</div>

@if($groups->isEmpty())
<div class="empty-state">
  <div class="empty-state-icon"><i class="bi bi-collection"></i></div>
  <h3>No groups yet</h3>
  <p>Create a group to organize your recipients.</p>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
    <i class="bi bi-plus-lg"></i> Create Group
  </button>
</div>
@else
<div class="row g-3">
  @foreach($groups as $group)
  <div class="col-sm-6 col-xl-4">
    <div class="group-card">
      <div class="group-card-top">
        <div class="group-icon"><i class="bi bi-people-fill"></i></div>
        <div class="action-btns">
          <button class="btn-action" data-bs-toggle="modal" data-bs-target="#editGroupModal"
            data-id="{{ $group->id }}" data-name="{{ $group->name }}" data-description="{{ $group->description }}">
            <i class="bi bi-pencil"></i>
          </button>
          <form method="POST" action="{{ route('groups.destroy', $group) }}" class="d-inline"
            onsubmit="return confirm('Delete {{ addslashes($group->name) }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn-action danger"><i class="bi bi-trash"></i></button>
          </form>
        </div>
      </div>
      <h4>{{ $group->name }}</h4>
      <div class="count">{{ $group->recipients_count }} {{ Str::plural('recipient', $group->recipients_count) }}</div>
      @if($group->description)
        <p class="text-secondary small mt-2 mb-0">{{ $group->description }}</p>
      @endif
      <div class="group-card-actions">
        <a href="{{ route('groups.show', $group) }}" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-eye"></i> View Members
        </a>
        <a href="{{ route('campaigns.compose', ['group_id' => $group->id]) }}" class="btn btn-sm btn-primary">
          <i class="bi bi-send"></i> Send Email
        </a>
      </div>
    </div>
  </div>
  @endforeach
</div>
@endif

{{-- Create Group Modal --}}
<div class="modal fade" id="createGroupModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('groups.store') }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">Create Group</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Group Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" required></div>
          <div class="mb-0"><label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Group</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Group Modal --}}
<div class="modal fade" id="editGroupModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="editGroupForm" action="">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Edit Group</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Group Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" id="editGroupName" required></div>
          <div class="mb-0"><label class="form-label">Description</label>
            <textarea class="form-control" name="description" id="editGroupDesc" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
<script>
document.getElementById('editGroupModal')?.addEventListener('show.bs.modal', function (e) {
  const btn = e.relatedTarget;
  if (!btn) return;
  document.getElementById('editGroupName').value = btn.dataset.name || '';
  document.getElementById('editGroupDesc').value  = btn.dataset.description || '';
  document.getElementById('editGroupForm').action = '/groups/' + btn.dataset.id;
});
</script>
@endpush
