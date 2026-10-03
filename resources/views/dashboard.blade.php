@extends('layouts.app')
@section('title', 'Dashboard')
@section('nav-title', 'Dashboard')

@section('content')
<div class="page-header">
  <div>
    <h2>Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }}, {{ explode(' ', session('admin_name', 'Admin'))[0] }} 👋</h2>
    <p>Manage your email campaigns and recipients from one place.</p>
  </div>
  <div class="page-actions">
    <a href="{{ route('campaigns.compose') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Compose Email</a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-card-top"><div class="stat-icon primary"><i class="bi bi-people-fill"></i></div></div>
      <div class="stat-value">{{ number_format($totalRecipients) }}</div>
      <div class="stat-label">Total Recipients</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-card-top"><div class="stat-icon success"><i class="bi bi-send-fill"></i></div></div>
      <div class="stat-value">{{ number_format($emailsSent) }}</div>
      <div class="stat-label">Emails Sent</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-card-top"><div class="stat-icon warning"><i class="bi bi-clock-fill"></i></div></div>
      <div class="stat-value">{{ number_format($pending) }}</div>
      <div class="stat-label">Pending</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-card-top"><div class="stat-icon danger"><i class="bi bi-exclamation-triangle-fill"></i></div></div>
      <div class="stat-value">{{ number_format($failed) }}</div>
      <div class="stat-label">Failed</div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card-surface h-100">
      <div class="card-header-row"><h3>Email Activity</h3></div>
      <div class="chart-container"><canvas id="emailActivityChart"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-surface h-100">
      <div class="card-header-row"><h3>Email Status</h3></div>
      <div class="chart-container doughnut position-relative">
        <canvas id="emailStatusChart"></canvas>
        <div class="doughnut-center">
          <div class="total">{{ number_format($emailsSent + $pending + $failed) }}</div>
          <div class="label">Total</div>
        </div>
      </div>
      <div class="chart-legend">
        <div class="legend-item"><span class="legend-dot" style="background:#16A34A"></span> Sent</div>
        <div class="legend-item"><span class="legend-dot" style="background:#F59E0B"></span> Pending</div>
        <div class="legend-item"><span class="legend-dot" style="background:#DC2626"></span> Failed</div>
      </div>
    </div>
  </div>
</div>

<div class="card-surface">
  <div class="card-header-row">
    <h3>Recent Campaigns</h3>
    <a href="{{ route('campaigns.index') }}" class="link-muted">View All →</a>
  </div>
  @if($recent->isEmpty())
  <div class="empty-state">
    <div class="empty-state-icon"><i class="bi bi-envelope"></i></div>
    <h3>No campaigns yet</h3>
    <p>Create your first email campaign.</p>
    <a href="{{ route('campaigns.compose') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Compose Email</a>
  </div>
  @else
  <div class="table-wrap">
    <table class="table-modern">
      <thead><tr><th>Campaign</th><th>Recipients</th><th>Sent</th><th>Failed</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
        @foreach($recent as $c)
        <tr>
          <td><strong>{{ $c->campaign_name }}</strong></td>
          <td>{{ $c->total_recipients }}</td>
          <td>{{ $c->total_sent }}</td>
          <td>{{ $c->total_failed }}</td>
          <td>@include('partials.status-badge', ['status' => $c->status])</td>
          <td>{{ $c->created_at?->format('M j, Y') ?? '—' }}</td>
          <td><a href="{{ route('campaigns.show', $c) }}" class="btn btn-sm btn-outline-primary">View</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer-link">
    <a href="{{ route('campaigns.index') }}">View All Campaigns <i class="bi bi-arrow-right"></i></a>
  </div>
  @endif
</div>
@endsection

@push('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const labels = {!! json_encode($activityLabels) !!};
  const data   = {!! json_encode($activityData) !!};
  const ctx    = document.getElementById('emailActivityChart');
  if (ctx) {
    const g = ctx.getContext('2d').createLinearGradient(0, 0, 0, 260);
    g.addColorStop(0, 'rgba(37,99,235,0.22)');
    g.addColorStop(1, 'rgba(37,99,235,0.01)');
    new Chart(ctx, { type:'line', data:{ labels, datasets:[{ label:'Emails Sent', data, borderColor:'#2563EB', backgroundColor:g, borderWidth:2.5, fill:true, tension:0.4, pointRadius:4 }]}, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false }}, scales:{ x:{ grid:{ display:false }}, y:{ beginAtZero:true }}}});
  }
  const s = document.getElementById('emailStatusChart');
  if (s) {
    new Chart(s, { type:'doughnut', data:{ labels:['Sent','Pending','Failed'], datasets:[{ data:[{{ $emailsSent }},{{ $pending }},{{ $failed }}], backgroundColor:['#16A34A','#F59E0B','#DC2626'], borderWidth:0 }]}, options:{ responsive:true, maintainAspectRatio:false, cutout:'72%', plugins:{ legend:{ display:false }}}});
  }
});
</script>
@endpush
