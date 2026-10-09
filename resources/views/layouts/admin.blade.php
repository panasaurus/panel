<!DOCTYPE html>
@include("blueprint.admin.admin")
@yield('blueprint.lib')
<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>{{ config('app.name', 'Panel') }} - @yield('title')</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <meta name="_token" content="{{ csrf_token() }}">

  <link rel="icon" type="image/png" href="/favicons/favicon-96x96.png" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg" />
  <link rel="shortcut icon" href="/favicons/favicon.ico" />
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png" />
  <meta name="apple-mobile-web-app-title" content="Panasaurus" />
  <link rel="manifest" href="/favicons/site.webmanifest" />

  <meta name="theme-color" content="#000000">
  <meta name="darkreader-lock">

  @include('layouts.scripts')

  @section('scripts')
  {!! Theme::css('vendor/select2/select2.min.css?t={cache-version}') !!}
  {!! Theme::css('vendor/bootstrap/bootstrap.min.css?t={cache-version}') !!}
  {!! Theme::css('vendor/adminlte/admin.min.css?t={cache-version}') !!}
  {!! Theme::css('vendor/adminlte/colors/skin-blue.min.css?t={cache-version}') !!}
  {!! Theme::css('vendor/sweetalert/sweetalert.min.css?t={cache-version}') !!}
  {!! Theme::css('vendor/animate/animate.min.css?t={cache-version}') !!}
  {!! Theme::css('css/panasaurus.css?t={cache-version}') !!}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
            <![endif]-->
  @show

  @yield("blueprint.import")
  @yield('blueprint.cache')
</head>

<body class="hold-transition skin-blue fixed sidebar-mini">
  <div class="wrapper">
    <header class="main-header">
      <a href="{{ route('index') }}" class="logo">
        <!-- <span>{{ config('app.name', 'Panel') }}</span> -->
        <svg width="30" height="30" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block align-middle">
            <defs><linearGradient id="panasaurusAdminLogo" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="#3EE6A0"/><stop offset="1" stop-color="#0E9F6E"/>
            </linearGradient></defs>
            <g fill="url(#panasaurusAdminLogo)">
              <path d="M4 41 L13 31.5 L18.5 37 L11 45 Q6.5 46.5 4 41 Z"/>
              <rect x="11.5" y="21" width="22.5" height="15.5" rx="7.75"/>
              <circle cx="18" cy="30.5" r="6"/>
              <rect x="27.5" y="11.5" width="10.5" height="17" rx="5.25"/>
              <rect x="30.5" y="10.5" width="14" height="9.5" rx="4.25"/>
              <rect x="38" y="16" width="6.5" height="4.5" rx="2.25"/>
              <rect x="14" y="33" width="5" height="10.5" rx="2.5"/>
              <rect x="24.5" y="33" width="5" height="10.5" rx="2.5"/>
            </g>
            <circle cx="37.8" cy="14.4" r="1.35" fill="#0B1220"/>
          </svg>
          <span class="logoText" style="color:#fff;font-size:17px;font-weight:700;vertical-align:middle;margin-left:6px;">Panasaurus</span>
      </a>
      <nav class="navbar navbar-static-top">
        <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </a>
        <div class="navbar-custom-menu">
          <ul class="nav navbar-nav">
            <li class="user-menu">
              <a href="{{ route('account') }}">

                <span class="hidden-xs">{{ Auth::user()->name_first }} {{ Auth::user()->name_last }}</span>
              </a>
            </li>
            <li>
            <li><a href="{{ route('index') }}" data-toggle="tooltip" data-placement="bottom"
                title="Exit Admin Control"><i class="fa fa-server"></i></a></li>
            </li>
            <li>
            <li><a href="{{ route('auth.logout') }}" id="logoutButton" data-toggle="tooltip" data-placement="bottom"
                title="Logout"><i class="fa fa-sign-out"></i></a></li>
            </li>
            @yield("blueprint.navigation")
          </ul>
        </div>
      </nav>
    </header>
    <aside class="main-sidebar">
      <section class="sidebar">
        <ul class="sidebar-menu">
          <li class="header">BASIC ADMINISTRATION</li>
          <li class="{{ Route::currentRouteName() !== 'admin.index' ?: 'active' }}">
            <a href="{{ route('admin.index') }}">
              <i class="bi bi-house-fill"></i> <span>Overview</span>
            </a>
          </li>
          <li class="{{ !starts_with(Route::currentRouteName(), 'admin.settings') ?: 'active' }}">
            <a href="{{ route('admin.settings')}}">
              <i class="bi bi-gear-fill"></i> <span>Settings</span>
            </a>
          </li>
          <li class="{{ !starts_with(Route::currentRouteName(), 'admin.api') ?: 'active' }}">
            <a href="{{ route('admin.api.index')}}">
              <i class="bi bi-globe"></i> <span>Application API</span>
            </a>
          </li>
          <li class="header">MANAGEMENT</li>
          <li class="{{ !starts_with(Route::currentRouteName(), 'admin.databases') ?: 'active' }}">
            <a href="{{ route('admin.databases') }}">
              <i class="bi bi-database-fill"></i> <span>Databases</span>
            </a>
          </li>
          <li class="{{ !starts_with(Route::currentRouteName(), 'admin.locations') ?: 'active' }}">
            <a href="{{ route('admin.locations') }}">
              <i class="bi bi-globe-americas"></i> <span>Locations</span>
            </a>
          </li>
          <li class="{{ !starts_with(Route::currentRouteName(), 'admin.nodes') ?: 'active' }}">
            <a href="{{ route('admin.nodes') }}">
              <i class="bi bi-hdd-fill"></i> <span>Nodes</span>
            </a>
          </li>
          <li class="{{ !starts_with(Route::currentRouteName(), 'admin.servers') ?: 'active' }}">
            <a href="{{ route('admin.servers') }}">
              <i class="bi bi-hdd-stack-fill"></i> <span>Servers</span>
            </a>
          </li>
          <li class="{{ !starts_with(Route::currentRouteName(), 'admin.users') ?: 'active' }}">
            <a href="{{ route('admin.users') }}">
              <i class="bi bi-people-fill"></i> <span>Users</span>
            </a>
          </li>
          <li class="header">SERVICE MANAGEMENT</li>
          <li class="{{ !starts_with(Route::currentRouteName(), 'admin.mounts') ?: 'active' }}">
            <a href="{{ route('admin.mounts') }}">
              <i class="bi bi-magic"></i> <span>Mounts</span>
            </a>
          </li>
          <li class="{{ !starts_with(Route::currentRouteName(), 'admin.nests') ?: 'active' }}">
            <a href="{{ route('admin.nests') }}">
              <i class="bi bi-egg-fill"></i> <span>Nests</span>
            </a>
          </li>
          <li class="header">PANASAURUS</li>
          <li class="{{ !starts_with(Route::currentRouteName(), 'admin.extensions') ?: 'active' }}">
            <a href="{{ route('admin.extensions') }}">
              <i class="bi bi-puzzle-fill"></i> <span>Extensions</span>
            </a>
          </li>
          <li class="{{ !starts_with(Route::currentRouteName(), 'admin.extensions.marketplace') ?: 'active' }}">
            <a href="{{ route('admin.extensions.marketplace') }}">
              <i class="bi bi-shop"></i> <span>Marketplace</span>
            </a>
          </li>
          @yield("blueprint.sidenav")
        </ul>
      </section>
    </aside>
    <div class="content-wrapper">
      <section class="content-header">
        @yield('blueprint.introduction')
        @yield('content-header')
      </section>
      <section class="content">
        <div class="row">
          <div class="col-xs-12">
            @if (count($errors) > 0)
              <div class="alert alert-danger">
                There was an error validating the data provided.<br><br>
                <ul>
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif
            @foreach (Alert::getMessages() as $type => $messages)
              @foreach ($messages as $message)
                <div class="alert alert-{{ $type }} alert-dismissable" role="alert">
                  {{ $message }}
                </div>
              @endforeach
            @endforeach
          </div>
        </div>
        @yield('content')
      </section>
    </div>
    <footer class="main-footer">
      <div class="pull-right small text-zinc" style="margin-right:10px;margin-top:-7px;">
        <strong><i class="fa fa-fw {{ $appIsGit ? 'fa-git-square' : 'fa-code-fork' }}"></i></strong>
        {{ $appVersion }}<br />
        <strong><i class="fa fa-fw fa-clock-o"></i></strong> {{ round(microtime(true) - LARAVEL_START, 3) }}s
      </div>
      Copyright &copy; 2015 - {{ date('Y') }} <a href="https://pterodactyl.io/">Pterodactyl</a>, <a
        href="https://pyrodactyl.dev">Pyrodactyl</a> & the <a href="https://github.com/panasaurus/panel">Panasaurus</a> projects.
    </footer>
    @yield('blueprint.wrappers')
  </div>
  @section('footer-scripts')
  <script src="/js/keyboard.polyfill.js" type="application/javascript"></script>
  <script>keyboardeventKeyPolyfill.polyfill();</script>

  {!! Theme::js('vendor/jquery/jquery.min.js?t={cache-version}') !!}
  {!! Theme::js('vendor/sweetalert/sweetalert.min.js?t={cache-version}') !!}
  {!! Theme::js('vendor/bootstrap/bootstrap.min.js?t={cache-version}') !!}
  {!! Theme::js('vendor/slimscroll/jquery.slimscroll.min.js?t={cache-version}') !!}
  {!! Theme::js('vendor/adminlte/app.min.js?t={cache-version}') !!}
  {!! Theme::js('vendor/bootstrap-notify/bootstrap-notify.min.js?t={cache-version}') !!}
  {!! Theme::js('vendor/select2/select2.full.min.js?t={cache-version}') !!}
  {!! Theme::js('js/admin/functions.js?t={cache-version}') !!}
  <script src="/js/autocomplete.js" type="application/javascript"></script>

  @if(Auth::user()->root_admin)
    <script>
      $('#logoutButton').on('click', function (event) {
        event.preventDefault();

        var that = this;
        swal({
          title: 'Do you want to log out?',
          type: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d9534f',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Log out'
        }, function () {
          $.ajax({
            type: 'POST',
            url: '{{ route('auth.logout') }}',
            data: {
              _token: '{{ csrf_token() }}'
            }, complete: function () {
              window.location.href = '{{route('auth.login')}}';
            }
          });
        });
      });
    </script>
  @endif

  <script>
    $(function () {
      $('[data-toggle="tooltip"]').tooltip();
    })
  </script>
  @show
</body>

</html>
