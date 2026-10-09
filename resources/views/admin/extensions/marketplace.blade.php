@extends('layouts.admin')

@section('title')
  Extension Marketplace
@endsection

@section('content-header')
  <h1>
    Extension Marketplace
    <small>Discover Blueprint extensions for your Panasaurus panel</small>
  </h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('admin.index') }}"><i class="bi bi-house-fill"></i> Admin</a></li>
    <li><a href="{{ route('admin.extensions') }}">Extensions</a></li>
    <li class="active">Marketplace</li>
  </ol>
@endsection

@section('content')
  <div class="row">
    <div class="col-xs-12">
      @if(isset($fetchError) && $fetchError)
        <div class="alert alert-warning">
          <i class="bi bi-wifi-off"></i> {{ $fetchError }}
        </div>
      @endif

      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title" style="display:flex;align-items:center;gap:10px;">
            <i class="bi bi-shop" style="color:#0e9f6e;"></i> Browse Extensions
            <span class="label label-default" style="margin-left:6px;">{{ count($extensions) }} available</span>
          </h3>
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;align-items:center;">
            <input
              type="text"
              id="marketplace-search"
              class="form-control"
              placeholder="Search extensions by name, author, or keyword…"
              style="max-width:420px;"
              autocomplete="off"
            />
            <div class="btn-group" role="group" aria-label="Filter">
              <button type="button" class="btn btn-default filter-btn active" data-filter="all">All</button>
              <button type="button" class="btn btn-default filter-btn" data-filter="installed">Installed</button>
              <button type="button" class="btn btn-default filter-btn" data-filter="available">Not installed</button>
            </div>
            <a href="{{ route('admin.extensions.marketplace.refresh') }}" class="btn btn-default" title="Refresh cache">
              <i class="bi bi-arrow-clockwise"></i> Refresh
            </a>
          </div>
        </div>

        <div class="box-body">
          @if(count($extensions) === 0)
            <div class="text-center" style="padding:48px 0;color:#7c8794;">
              <i class="bi bi-shop" style="font-size:42px;opacity:.4;"></i>
              <p style="margin-top:12px;">The marketplace directory couldn't be loaded right now.</p>
              <p class="small">Extensions can still be installed manually — see the docs:
                <code>panasaurus -install &lt;identifier&gt;</code></p>
            </div>
          @else
            <div class="row" id="marketplace-grid">
              @foreach($extensions as $extension)
                @php
                  $isInstalled = isset($installed[$extension['identifier']]);
                @endphp
                <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12 marketplace-card"
                     data-name="{{ strtolower($extension['name']) }} {{ strtolower($extension['identifier']) }} {{ strtolower($extension['summary']) }} {{ strtolower($extension['author']) }} {{ strtolower(implode(' ', $extension['keywords'])) }}"
                     data-installed="{{ $isInstalled ? '1' : '0' }}">
                  <div class="box box-solid" style="height:100%;margin-bottom:16px;">
                    <div class="box-body" style="min-height:150px;">
                      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                        <h4 style="margin:0 0 4px;font-weight:700;">
                          {{ $extension['name'] }}
                          @if($isInstalled)
                            <span class="label label-success" title="Installed: {{ $installed[$extension['identifier']] }}">installed</span>
                          @endif
                        </h4>
                      </div>
                      <p class="text-muted small" style="margin-bottom:6px;">
                        by {{ $extension['author'] }}
                        @if($extension['latest_version'])
                          &middot; v{{ $extension['latest_version'] }}
                        @endif
                      </p>
                      <p style="font-size:13px;color:#9aa5b1;min-height:38px;">
                        {{ \Illuminate\Support\Str::limit($extension['summary'], 120) }}
                      </p>
                      @if(count($extension['keywords']))
                        <div style="margin-top:6px;">
                          @foreach($extension['keywords'] as $keyword)
                            <span class="label label-primary" style="margin-right:4px;background:#0e9f6e;">{{ $keyword }}</span>
                          @endforeach
                        </div>
                      @endif
                    </div>
                    <div class="box-footer" style="background:transparent;border-top:1px solid rgba(255,255,255,.06);">
                      <button type="button" class="btn btn-xs btn-default install-instructions-btn" data-identifier="{{ $extension['identifier'] }}">
                        <i class="bi bi-terminal"></i> Install
                      </button>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
            <div id="marketplace-empty" class="text-center" style="display:none;padding:32px 0;color:#7c8794;">
              <i class="bi bi-search" style="font-size:28px;opacity:.4;"></i>
              <p style="margin-top:8px;">No extensions match your filters.</p>
            </div>
          @endif
        </div>
      </div>

      <div class="callout callout-info">
        <h4><i class="bi bi-info-circle"></i> Installing extensions</h4>
        <p>
          Panasaurus ships with the Blueprint extension framework built in. Download an extension's
          <code>.blueprint</code> file from its page, upload it into your panel directory, then run:
        </p>
        <p><code>panasaurus -install &lt;extension-name&gt;</code> &nbsp;(or <code>blueprint -install</code>)</p>
      </div>
    </div>
  </div>

  <div class="modal fade" id="installInstructionsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content box box-primary" style="margin-bottom:0;">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="bi bi-terminal"></i> Install <span id="install-ext-name"></span></h3>
        </div>
        <div class="box-body">
          <ol style="padding-left:18px;line-height:1.9;">
            <li>Grab the <strong>.blueprint</strong> file for <span id="install-ext-id"></span> from
              <a href="https://blueprint.zip/browse" target="_blank" rel="noopener">blueprint.zip/browse</a>.</li>
            <li>Copy it into your panel directory:<br>
              <code>cp &lt;file&gt;.blueprint /app/</code> <span class="text-muted small">(outside Docker: your panel folder)</span></li>
            <li>Install it:<br>
              <code id="install-cmd">panasaurus -install &lt;extension-name&gt;</code></li>
            <li>Rebuild assets if prompted, then hard-refresh your browser.</li>
          </ol>
        </div>
        <div class="box-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('footer-scripts')
  @parent
  <script>
    (function () {
      var cards = Array.prototype.slice.call(document.querySelectorAll('.marketplace-card'));
      var emptyState = document.getElementById('marketplace-empty');
      var searchInput = document.getElementById('marketplace-search');
      var activeFilter = 'all';

      function apply() {
        var query = (searchInput.value || '').trim().toLowerCase();
        var visible = 0;

        cards.forEach(function (card) {
          var matchesQuery = !query || card.getAttribute('data-name').indexOf(query) !== -1;
          var matchesFilter =
            activeFilter === 'all' ||
            (activeFilter === 'installed' && card.getAttribute('data-installed') === '1') ||
            (activeFilter === 'available' && card.getAttribute('data-installed') !== '1');

          var show = matchesQuery && matchesFilter;
          card.style.display = show ? '' : 'none';
          if (show) visible++;
        });

        emptyState.style.display = visible === 0 ? '' : 'none';
      }

      if (searchInput) searchInput.addEventListener('input', apply);

      document.querySelectorAll('.filter-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          document.querySelectorAll('.filter-btn').forEach(function (b) { b.classList.remove('active'); });
          btn.classList.add('active');
          activeFilter = btn.getAttribute('data-filter');
          apply();
        });
      });

      document.querySelectorAll('.install-instructions-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var identifier = btn.getAttribute('data-identifier');
          document.getElementById('install-ext-name').textContent = identifier;
          document.getElementById('install-ext-id').textContent = identifier;
          document.getElementById('install-cmd').textContent = 'panasaurus -install ' + identifier;
          $('#installInstructionsModal').modal('show');
        });
      });
    })();
  </script>
@endsection
