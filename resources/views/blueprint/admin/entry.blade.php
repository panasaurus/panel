@if(isset($EXTENSION_ID))
  @php
    $latest = true;
    if(isset($EXTENSION_METADATA)) {
      $diff = str_replace($EXTENSION_METADATA['latest_version'], '', $EXTENSION_VERSION);

      if(
        $EXTENSION_METADATA['latest_version'] != $EXTENSION_VERSION
        && $EXTENSION_METADATA['local_version'] == $EXTENSION_VERSION
        && $diff != "v"
      ) {
        $latest = false;
      }
    }
  @endphp

    <a href="{{ route('admin.extensions.'.$EXTENSION_ID.'.index') }}" class="admin-extension-card">
        <img src="{{ $EXTENSION_ICON }}" alt="" class="admin-extension-icon" />
        <span class="admin-extension-details"><strong>{{ $EXTENSION_NAME }}</strong><span>
          {{ $EXTENSION_VERSION }}
          @if(!$latest)
          <span class="admin-status admin-status--success">
            <i class="bi bi-caret-up-fill"></i>
            <span>{{ $EXTENSION_METADATA['latest_version'] }}</span>
          </span>
          @endif
        </span></span>
        @include('partials.admin.icon', ['name' => 'arrow'])
    </a>
@endif
