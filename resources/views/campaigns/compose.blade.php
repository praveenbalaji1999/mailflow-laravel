@extends('layouts.app')
@section('title', 'Compose Email')
@section('nav-title', 'Compose Email')

@section('content')
<div class="page-header">
  <div><h2>Compose Email</h2><p>Create and send a new email campaign.</p></div>
</div>

<form method="POST" action="{{ route('campaigns.store') }}" enctype="multipart/form-data" id="composeForm">
  @csrf
  <input type="hidden" name="campaign_id" value="{{ $campaign?->id ?? 0 }}">
  <input type="hidden" name="existing_attachment" value="{{ $campaign?->attachment_path ?? '' }}">

  <div class="card-surface p-4">
    <div class="mb-4">
      <label class="form-label">Campaign Name <span class="text-danger">*</span></label>
      <input type="text" class="form-control form-control-lg" name="campaign_name"
        value="{{ old('campaign_name', $campaign?->campaign_name ?? 'Monthly Customer Update') }}" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Recipients (Group)</label>
      <select class="form-select" name="group_id" id="recipientGroup">
        <option value="0">Select Group (optional)</option>
        @foreach($groups as $g)
          <option value="{{ $g->id }}" {{ request('group_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
      </select>
      <div class="form-text">Selecting a group includes all active members.</div>
    </div>

    <div class="mb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="selected-count" id="selectedCount"><strong>0</strong> recipients selected</div>
      <div class="form-check mb-0">
        <input class="form-check-input" type="checkbox" id="selectAllRecipients">
        <label class="form-check-label" for="selectAllRecipients">Select all listed</label>
      </div>
    </div>
    <div class="chips-wrap mb-3" id="recipientChips"></div>

    <div class="recipient-list mb-4" style="max-height:220px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm);">
      @foreach($recipients as $r)
      <label class="recipient-row d-flex align-items-center gap-2 p-2 border-bottom" style="cursor:pointer;">
        <input type="checkbox" class="form-check-input recipient-check" name="recipients[]"
          value="{{ $r->id }}" data-name="{{ $r->name }}"
          {{ in_array($r->id, $selectedIds) ? 'checked' : '' }}>
        <div class="avatar sm">{{ $r->initials }}</div>
        <div class="recipient-meta">
          <strong>{{ $r->name }}</strong>
          <span class="text-muted small d-block">{{ $r->email }}</span>
        </div>
      </label>
      @endforeach
      @if($recipients->isEmpty())
        <div class="p-3 text-muted">No active recipients. <a href="{{ route('recipients.index') }}">Add recipients</a> first.</div>
      @endif
    </div>

    <div class="mb-4">
      <label class="form-label">Subject</label>
      <input type="text" class="form-control" id="emailSubject" name="subject"
        value="{{ old('subject', $campaign?->subject ?? 'Important Company Update') }}">
    </div>

    <div class="mb-4">
      <label class="form-label">Message</label>
      <div class="rich-editor">
        <div class="editor-toolbar">
          <button type="button" data-command="bold"><i class="bi bi-type-bold"></i></button>
          <button type="button" data-command="italic"><i class="bi bi-type-italic"></i></button>
          <button type="button" data-command="underline"><i class="bi bi-type-underline"></i></button>
          <span class="toolbar-divider"></span>
          <button type="button" data-command="insertUnorderedList"><i class="bi bi-list-ul"></i></button>
          <button type="button" data-command="createLink"><i class="bi bi-link-45deg"></i></button>
        </div>
        <div class="editor-body" id="emailEditor" contenteditable="true">{!! $campaign?->message ?? '<p>Dear Customer,</p><p>We would like to inform you about important updates.</p><p>Regards,<br>MailFlow Team</p>' !!}</div>
        <textarea name="message" id="messageField" class="d-none">{{ old('message', $campaign?->message ?? '') }}</textarea>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label">Attachment</label>
      <div class="attachment-zone">
        <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.gif,.txt,.csv">
        @if($campaign?->attachment_path)
          <div class="small text-muted mt-2">Current: {{ basename($campaign->attachment_path) }}</div>
        @endif
      </div>
    </div>

    <div class="compose-actions">
      <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
        <i class="bi bi-file-earmark"></i> Save Draft
      </button>
      <button type="button" class="btn btn-outline-primary" id="btnPreview" data-bs-toggle="modal" data-bs-target="#previewModal">
        <i class="bi bi-eye"></i> Preview
      </button>
      <button type="submit" name="action" value="send" class="btn btn-primary" id="btnSendEmail">
        Send Email <i class="bi bi-send-fill"></i>
      </button>
    </div>
  </div>
</form>

{{-- Preview Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Email Preview</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="email-preview-meta p-3 border-bottom small">
        <div><strong>From:</strong> {{ $smtp ? $smtp->from_name . ' <' . $smtp->from_email . '>' : 'MailFlow' }}</div>
        <div><strong>To:</strong> <span id="previewTo">0 recipients</span></div>
        <div><strong>Subject:</strong> <span id="previewSubject"></span></div>
      </div>
      <div class="modal-body" id="previewBody" style="min-height:160px;"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnConfirmSend">Confirm &amp; Send <i class="bi bi-send-fill"></i></button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
<script>
(function () {
  const editor = document.getElementById('emailEditor');
  const messageField = document.getElementById('messageField');
  const form = document.getElementById('composeForm');

  function syncMessage() {
    if (editor && messageField) messageField.value = editor.innerHTML;
  }

  document.querySelectorAll('.editor-toolbar button[data-command]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const cmd = btn.getAttribute('data-command');
      if (cmd === 'createLink') {
        const url = prompt('Enter URL');
        if (url) document.execCommand('createLink', false, url);
      } else {
        document.execCommand(cmd, false, null);
      }
      editor?.focus();
    });
  });

  form?.addEventListener('submit', syncMessage);

  document.getElementById('btnPreview')?.addEventListener('click', function () {
    syncMessage();
    const checked = document.querySelectorAll('.recipient-check:checked').length;
    document.getElementById('previewSubject').textContent = document.getElementById('emailSubject')?.value || '(No subject)';
    document.getElementById('previewBody').innerHTML = editor?.innerHTML || '';
    document.getElementById('previewTo').textContent = checked + ' recipients';
  });

  document.getElementById('btnConfirmSend')?.addEventListener('click', function () {
    syncMessage();
    const input = document.createElement('input');
    input.type = 'hidden'; input.name = 'action'; input.value = 'send';
    form.appendChild(input);
    form.submit();
  });

  // Chips
  function updateChips() {
    const wrap = document.getElementById('recipientChips');
    const countEl = document.getElementById('selectedCount');
    if (!wrap || !countEl) return;
    wrap.innerHTML = '';
    let count = 0;
    document.querySelectorAll('.recipient-check').forEach(function (cb) {
      if (!cb.checked) return;
      count++;
      const chip = document.createElement('span');
      chip.className = 'chip';
      chip.innerHTML = (cb.dataset.name || 'Recipient') + ' <button type="button" aria-label="Remove"><i class="bi bi-x"></i></button>';
      chip.querySelector('button').addEventListener('click', function () { cb.checked = false; updateChips(); });
      wrap.appendChild(chip);
    });
    countEl.innerHTML = '<strong>' + count + '</strong> recipients selected';
  }
  document.querySelectorAll('.recipient-check').forEach(cb => cb.addEventListener('change', updateChips));
  document.getElementById('selectAllRecipients')?.addEventListener('change', function () {
    document.querySelectorAll('.recipient-check').forEach(cb => cb.checked = this.checked);
    updateChips();
  });
  updateChips();
})();
</script>
@endpush
