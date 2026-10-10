@extends('layouts.admin')

@section('title')
  Extensions
@endsection

@php
$is_installed=(($PlaceholderService->installed() != "NOTINSTALLED") && ($PlaceholderService->version() != "::"."v"));
@endphp

@section('content-header')
  <h1>Extensions<small>Manage installed extensions and configure your panel.</small></h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('admin.index') }}">Admin</a></li>
    <li class="active">Extensions</li>
  </ol>
  @if($is_installed)
    @if(($PlaceholderService->version() != $latestBlueprintVersion) && ($PlaceholderService->version() != "rolling") && $latestBlueprintVersion != "unknown")
      <div class="blueprint-statusbar blueprint-statusbar-danger">
        <div style="margin-right: 14px;">
          <i class="bi bi-exclamation-triangle-fill" style="font-size: 24px; color: #f52e98"></i>
        </div>
        <div>
          <span class="text-bold" style="color: #ff6ab9;">
            Blueprint is out-of-date.
          </span>
          You're running Blueprint
          <code style="border: unset; background-color: unset; color: #cad1d8;">
            {{ $PlaceholderService->version() }}
          </code>
          which is outdated. Update to version
          <code style="border: unset; background-color: unset; color: #cad1d8;">
            {{ $latestBlueprintVersion }}
          </code>
          to access the latest features and improvements.
        </div>
      </div>
    @endif

    @if($PlaceholderService->version() == "rolling")
      <div class="blueprint-statusbar blueprint-statusbar-warning">
        <div style="margin-right: 14px;">
          <i class="bi bi-bug-fill" style="font-size: 24px; color: #f5952e"></i>
        </div>
        <div>
          <span class="text-bold" style="color: #f9a040;">
          This instance is running a development-preview of Blueprint.
          </span>
          You may run into bugs, extension incompatibilities and more. If you run into any issues, please <a href="https://github.com/blueprintframework/framework/issues">let us know</a>.
        </div>
      </div>
    @endif

    @if($latestBlueprintVersion == "unknown" && $PlaceholderService->version() != "rolling")
      <div class="blueprint-statusbar blueprint-statusbar-warning">
        <div style="margin-right: 14px;">
          <i class="bi bi-wifi-off" style="font-size: 24px; color: #f5952e"></i>
        </div>
        <div>
          <span class="text-bold" style="color: #f9a040;">
          Could not fetch version info.
          </span>
          Blueprint failed to fetch the latest release name from the API.
        </div>
      </div>
    @endif

  @endif
@endsection

@section('content')
  @if($is_installed)
    <div class="admin-extension-intro">
      <div><h2>Powered by Blueprint</h2><p>Extend your panel with themes, plugins, and admin tools.</p></div>
      <a href="https://blueprint.zip/" target="_blank" rel="noopener noreferrer">Learn more @include('partials.admin.icon', ['name' => 'external'])</a>
    </div>
    <div class="admin-extension-grid">
      <button type="button" class="admin-extension-card" data-toggle="modal" data-target="#blueprintConfigModal">
        <img src="/assets/extensions/blueprint/logo.jpg" alt="" class="admin-extension-icon" />
        <span class="admin-extension-details"><strong>Blueprint</strong><span><span class="admin-status">System</span> {{ $PlaceholderService->version() }}</span></span>
        @include('partials.admin.icon', ['name' => 'settings'])
      </button>

      @foreach($blueprint->extensionsConfigs() as $extension)
        <?php
          $extension = $extension['info'];
          $metadataScoped = null;
          if(isset($metadata[$extension['identifier']])) {
            $metadataScoped = $metadata[$extension['identifier']];
          }
        ?>
        @include("blueprint.admin.entry", [
          'EXTENSION_ID' => $extension['identifier'],
          'EXTENSION_NAME' => $extension['name'],
          'EXTENSION_VERSION' => $extension['version'],
          'EXTENSION_METADATA' => $metadataScoped,
          'EXTENSION_ICON' => !empty($extension['icon'])
            ? '/assets/extensions/'.$extension['identifier'].'/icon.'.pathinfo($extension['icon'], PATHINFO_EXTENSION)
            : '/assets/extensions/'.$extension['identifier'].'/icon.jpg'
        ])
      @endforeach

      <a href="{{ route('admin.extensions.marketplace') }}" class="admin-extension-card admin-extension-browse">
        <span class="admin-extension-icon">@include('partials.admin.icon', ['name' => 'plus'])</span>
        <span class="admin-extension-details"><strong>Browse extensions</strong><span>Explore the marketplace</span></span>
        @include('partials.admin.icon', ['name' => 'arrow'])
      </a>
    </div>



    <div class="modal fade" id="blueprintConfigModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content" style="background-color:transparent">
          <form action="/admin/extensions/blueprint" method="POST" autocomplete="off">
            <div class="modal-header" style="border-color:transparent; border-radius:12px 12px 5px 5px; margin-bottom: 8px">
              <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;box-shadow:none"><span aria-hidden="true"><i class="bi bi-x"></i></span></button>
              <h3 class="modal-title">
                <img src="/assets/extensions/blueprint/logo.jpg" alt="logo" height="34" width="34" class="pull-left" style="border-radius:3px;margin-right:10px"/>
                Configure <b>Blueprint</b>
              </h3>
            </div>

            <div class="modal-body" style="border-color:transparent; border-radius:5px; margin-bottom: 8px; padding-bottom: 5px;">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th style="width:50%">Flag</th>
                    <th>Value</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($configuration as $key => $value)
                    @if(strpos($key, 'flags:') === 0)
                      @php
                        $flagKey = str_replace('flags:', '', $key);
                        $schema = $seeder->getSchema();
                        $flagConfig = $schema['flags'][$flagKey] ?? null;
                        $flagType = $flagConfig['type'] ?? 'string';
                        $flagHidden = $flagConfig['hidden'] ?? false;
                      @endphp
                      @if($flagHidden != true)
                        <tr data-flag-row data-flag-name="{{ $key }}" data-flag-type="{{ $flagType }}" data-default-value="{{ $defaults[$key] }}">
                          <td>
                            <code>
                              {{ $flagKey }}
                            </code>
                          </td>
                          <td>
                            @switch($flagType)
                              @case('boolean')
                                <select class="form-control" name="{{ $key }}" style="border-radius:6px">
                                  <option value="1" {{ $value ? 'selected' : '' }}>true</option>
                                  <option value="0" {{ !$value ? 'selected' : '' }}>false</option>
                                </select>
                                @break
                              @case('number')
                                <input type="number" class="form-control" name="{{ $key }}" value="{{ $value }}" step="any" style="border-radius:6px">
                                @break
                              @case('integer')
                                <input type="number" class="form-control" name="{{ $key }}" value="{{ $value }}" step="1" style="border-radius:6px">
                                @break

                              @default
                                <input type="text" class="form-control" name="{{ $key }}" value="{{ $value }}" style="border-radius:6px">
                            @endswitch
                          </td>
                        </tr>
                      @endif
                    @endif
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="modal-footer" style="border-color:transparent; border-radius:5px 5px 12px 12px">
              {{ csrf_field() }}
              <input type="hidden" name="_method" value="PATCH">
              <div class="row">
                <div class="col-sm-10">
                  <p class="text-muted small text-left">Flags enable certain features that may be experimental, unstable, or in development. Modifying these values can affect Blueprint's functionality, stability, and security.</p>
                </div>
                <div class="col-sm-2">
                  <button type="submit" class="btn btn-primary btn-sm" style="width:100%; margin-top:10px; margin-bottom:10px; border-radius:6px">Save</button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

  @else
    <center>
      <div style="padding-top: 50px;">
        <span style="font-size: 36px">
          <i class="bi bi-info-circle-fill"></i>
        </span>
        <br style="margin-bottom: 12px;">
        <span style="font-size: 20px" class="text-bold">
          Unfinished installation
        </span>
        <br style="margin-bottom: 12px;">
        <span>
          Blueprint is currently only partially installed.<br>
          Finish the
          <a href="https://blueprint.zip/guides/admin/install">installation guide</a>,
          then return to this page afterwards.
        </span>
        <br style="margin-bottom: 12px;">
        <span>
          You may be able to fix this issue by running <code>blueprint -upgrade</code>.
        </span>
      </div>
    </center>
  @endif

@endsection

@section('footer-scripts')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const flagRows = document.querySelectorAll('[data-flag-row]');

      const createResetButton = (row, input) => {
        const button = document.createElement('button');
        button.className = 'blueprint-button-link reset-flag';
        button.style.padding = '0';
        button.style.marginLeft = '8px';
        button.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>';

        const defaultValue = getDefaultValue(row);

        button.addEventListener('click', (e) => {
          e.preventDefault();
          input.value = defaultValue;
          input.dispatchEvent(new Event('change'));
        });

        return button;
      };

      const getDefaultValue = (row) => {
        const flagType = row.dataset.flagType;
        const defaultValue = row.dataset.defaultValue;

        if (flagType === 'boolean' && (!defaultValue || defaultValue === '')) {
          return '0';
        }

        return defaultValue;
      };

      const compareValues = (input, defaultValue) => {
        const flagType = input.closest('[data-flag-row]').dataset.flagType;
        const currentValue = input.value;

        const effectiveDefault = flagType === 'boolean' && (!defaultValue || defaultValue === '')
          ? '0'
          : defaultValue;

        switch (flagType) {
          case 'boolean':
            return currentValue !== effectiveDefault;
          case 'number':
          case 'integer':
            return Number(currentValue) !== Number(effectiveDefault);
          default:
            return String(currentValue) !== String(effectiveDefault);
        }
      };

      const handleValueChange = (row, input) => {
        const defaultValue = getDefaultValue(row);

        const existingButton = row.querySelector('td .reset-flag');
        if (existingButton) {
          existingButton.remove();
        }

        if (compareValues(input, defaultValue)) {
          const resetButton = createResetButton(row, input);
          row.querySelector('td').appendChild(resetButton);
        }
      };

      flagRows.forEach(row => {
        const input = row.querySelector('select, input');
        if (!input) return;

        handleValueChange(row, input);

        input.addEventListener('change', () => {
          handleValueChange(row, input);
        });

        if (input.tagName.toLowerCase() === 'input') {
          input.addEventListener('keyup', () => {
            handleValueChange(row, input);
          });
        }
      });
    });
  </script>
@endsection
