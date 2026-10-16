<!DOCTYPE html>
@include("blueprint.admin.admin")
@yield('blueprint.lib')
<html lang="{{ config('app.locale', 'en') }}">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>{{ config('app.name', 'Panel') }} - @yield('title')</title>
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <meta name="_token" content="{{ csrf_token() }}">

  <link rel="icon" type="image/png" href="/favicons/favicon-96x96.png?v=20261016" sizes="96x96" />
  <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg?v=20261016" />
  <link rel="shortcut icon" href="/favicons/favicon.ico?v=20261016" />
  <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png?v=20261016" />
  <meta name="apple-mobile-web-app-title" content="Panasaurus" />
  <link rel="manifest" href="/favicons/site.webmanifest?v=20261016" />

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

  @show

  @yield("blueprint.import")
  @yield('blueprint.cache')
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
  <link href="{{ asset('themes/panasaurus/css/admin.css') }}?v={{ filemtime(public_path('themes/panasaurus/css/admin.css')) }}" rel="stylesheet">
</head>

<body class="skin-blue admin-app" data-layout='{"slimscroll":false,"resetHeight":false}'>
  <a class="admin-skip-link" href="#admin-main">Skip to content</a>
  <div class="wrapper">
    @include('partials.admin.navigation')
    <div class="admin-workspace" id="admin-workspace">
    <main class="content-wrapper" id="admin-main" tabindex="-1">
      <section class="content-header {{ Route::currentRouteName() === 'admin.index' ? 'admin-overview-header' : '' }}">
        @yield('blueprint.introduction')
        @yield('content-header')
      </section>
      <section class="content">
        <div class="row">
          <div class="col-xs-12">
            @if (count($errors) > 0)
              <div class="alert alert-danger" role="alert">
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
    </main>
    <footer class="main-footer">
      <div class="pull-right small text-zinc" style="margin-right:10px;margin-top:-7px;">
        <strong><i class="fa fa-fw {{ $appIsGit ? 'fa-git-square' : 'fa-code-fork' }}"></i></strong>
        {{ $appVersion }}<br />
        <strong><i class="fa fa-fw fa-clock-o"></i></strong> {{ round(microtime(true) - LARAVEL_START, 3) }}s
      </div>
      <p class="admin-footer-attribution">Copyright &copy; 2015 - {{ date('Y') }} <a href="https://pterodactyl.io/">Pterodactyl</a>, <a
        href="https://pyrodactyl.dev">Pyrodactyl</a> & the <a href="https://github.com/panasaurus/panel">Panasaurus</a> projects.</p>
    </footer>
    </div>
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
  <script src="{{ asset('themes/panasaurus/js/admin/ui.js') }}?v={{ filemtime(public_path('themes/panasaurus/js/admin/ui.js')) }}" defer></script>

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
