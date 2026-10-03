<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Dashboard') — MailFlow</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('css/style.css') }}" rel="stylesheet">
  @stack('styles')
</head>
<body>
  <div class="app-wrapper">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- Sidebar --}}
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="bi bi-envelope-fill"></i></div>
        <div class="sidebar-brand-text">
          <h1>MailFlow</h1>
          <span>Email Management</span>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
          <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>
        <a href="{{ route('recipients.index') }}" class="sidebar-link {{ request()->routeIs('recipients.*') ? 'active' : '' }}">
          <i class="bi bi-people"></i><span>Recipients</span>
        </a>
        <a href="{{ route('groups.index') }}" class="sidebar-link {{ request()->routeIs('groups.*') ? 'active' : '' }}">
          <i class="bi bi-collection"></i><span>Groups</span>
        </a>
        <a href="{{ route('campaigns.compose') }}" class="sidebar-link {{ request()->routeIs('campaigns.compose') ? 'active' : '' }}">
          <i class="bi bi-pencil-square"></i><span>Compose Email</span>
        </a>
        <a href="{{ route('campaigns.index') }}" class="sidebar-link {{ request()->routeIs('campaigns.index') || request()->routeIs('campaigns.show') ? 'active' : '' }}">
          <i class="bi bi-megaphone"></i><span>Campaigns</span>
        </a>
        <a href="{{ route('email-history.index') }}" class="sidebar-link {{ request()->routeIs('email-history.*') ? 'active' : '' }}">
          <i class="bi bi-clock-history"></i><span>Email History</span>
        </a>
        <a href="{{ route('smtp-settings.index') }}" class="sidebar-link {{ request()->routeIs('smtp-settings.*') ? 'active' : '' }}">
          <i class="bi bi-server"></i><span>SMTP Settings</span>
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="{{ route('smtp-settings.index') }}" class="sidebar-link {{ request()->routeIs('smtp-settings.*') ? 'active' : '' }}">
          <i class="bi bi-gear"></i><span>Settings</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="sidebar-link w-100 border-0 bg-transparent text-start" data-logout>
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
          </button>
        </form>
      </div>
    </aside>

    {{-- Main Content --}}
    <div class="main-content">
      <header class="top-navbar">
        <div class="topbar-left">
          <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
          </button>
          <h1 class="page-title-nav">@yield('nav-title', 'Dashboard')</h1>
        </div>
        <div class="topbar-right">
          <div class="dropdown profile-dropdown">
            <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
              <div class="avatar">{{ strtoupper(substr(session('admin_name', 'A'), 0, 2)) }}</div>
              <div class="profile-meta">
                <span class="name">{{ session('admin_name', 'Admin') }}</span>
                <span class="role">Administrator</span>
              </div>
              <i class="bi bi-chevron-down profile-chevron"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="{{ route('smtp-settings.index') }}"><i class="bi bi-gear"></i> Settings</a></li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Logout</button>
                </form>
              </li>
            </ul>
          </div>
        </div>
      </header>

      <main class="page-content">
        @yield('content')
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="{{ asset('js/app.js') }}"></script>

  {{-- Flash messages --}}
  @if(session('success') || session('error') || session('warning') || session('info'))
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    @if(session('success'))
      MailFlow.showToast({{ Js::from(session('success')) }}, 'success');
    @endif
    @if(session('error'))
      MailFlow.showToast({{ Js::from(session('error')) }}, 'danger');
    @endif
    @if(session('warning'))
      MailFlow.showToast({{ Js::from(session('warning')) }}, 'warning');
    @endif
    @if(session('info'))
      MailFlow.showToast({{ Js::from(session('info')) }}, 'info');
    @endif
  });
  </script>
  @endif

  @stack('page-scripts')
</body>
</html>
