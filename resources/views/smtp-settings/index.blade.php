@extends('layouts.app')
@section('title', 'SMTP Settings')
@section('nav-title', 'SMTP Settings')

@section('content')
<div class="page-header">
  <div><h2>SMTP Settings</h2><p>Configure your outgoing mail server for campaign delivery.</p></div>
</div>

<div class="alert-security mb-4 d-flex gap-3 p-3 rounded" style="background:var(--warning-soft);border:1px solid rgba(245,158,11,.25);">
  <i class="bi bi-shield-lock-fill text-warning fs-5 mt-1"></i>
  <div>
    <strong>Your SMTP credentials are sensitive information.</strong>
    <p class="mb-0 small">Never share them publicly. These values are stored in the database.</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <form method="POST" action="{{ route('smtp-settings.save') }}">
      @csrf
      <input type="hidden" name="has_existing_password" value="{{ $hasPassword ? '1' : '0' }}">

      <div class="card-surface mb-3">
        <div class="card-header-row"><h3>SMTP Configuration</h3></div>
        <div class="row g-3 p-3">
          <div class="col-md-8">
            <label class="form-label">SMTP Host <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="host" value="{{ old('host', $smtp->host) }}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">SMTP Port <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="port" value="{{ old('port', $smtp->port) }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Encryption</label>
            <select class="form-select" name="encryption">
              <option value="tls" {{ $smtp->encryption === 'tls' ? 'selected' : '' }}>TLS</option>
              <option value="ssl" {{ $smtp->encryption === 'ssl' ? 'selected' : '' }}>SSL</option>
              <option value="none" {{ $smtp->encryption === 'none' ? 'selected' : '' }}>None</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">SMTP Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="username" value="{{ old('username', $smtp->username) }}" required>
          </div>
          <div class="col-12">
            <label class="form-label">SMTP Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="smtpPassword" name="password" value=""
                placeholder="{{ $hasPassword ? 'Leave blank to keep existing password' : 'Enter your SMTP password' }}">
              <button class="btn btn-outline-secondary" type="button" data-password-toggle="#smtpPassword">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            @if($hasPassword)
              <div class="form-text text-success"><i class="bi bi-check-circle"></i> Password saved. Leave blank to keep it.</div>
            @else
              <div class="form-text text-warning"><i class="bi bi-exclamation-circle"></i> No password saved yet.</div>
            @endif
          </div>
        </div>
      </div>

      <div class="card-surface mb-3">
        <div class="card-header-row"><h3>Sender Information</h3></div>
        <div class="row g-3 p-3">
          <div class="col-md-6">
            <label class="form-label">From Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="from_name" value="{{ old('from_name', $smtp->from_name) }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">From Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="from_email" value="{{ old('from_email', $smtp->from_email) }}" required>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Configuration</button>
      </div>
    </form>

    <form method="POST" action="{{ route('smtp-settings.test') }}" class="mt-2">
      @csrf
      <button type="submit" class="btn btn-outline-primary"><i class="bi bi-plug"></i> Test Connection</button>
    </form>
  </div>

  <div class="col-lg-5">
    <div class="card-surface h-100">
      <div class="card-header-row"><h3>Connection Tips</h3></div>
      <div class="p-3">
        <ul class="list-unstyled small text-secondary">
          <li class="mb-2"><i class="bi bi-info-circle text-primary me-2"></i>Use port <strong>587</strong> with TLS for most providers.</li>
          <li class="mb-2"><i class="bi bi-info-circle text-primary me-2"></i>Use port <strong>465</strong> with SSL if TLS fails.</li>
          <li class="mb-2"><i class="bi bi-info-circle text-primary me-2"></i>For Gmail, use an <strong>App Password</strong> (not your regular password).</li>
          <li class="mb-2"><i class="bi bi-info-circle text-primary me-2"></i>Enable 2-Step Verification in Gmail first, then generate an App Password.</li>
          <li><i class="bi bi-info-circle text-primary me-2"></i>Ensure the from email matches your authenticated domain.</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
