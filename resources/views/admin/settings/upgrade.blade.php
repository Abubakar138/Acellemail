@extends('layouts.core.backend', [
    'menu' => 'setting',
])

@section('title', trans('messages.upgrade.title.upgrade'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
        </ul>
        <h1>
            <span class="material-symbols-rounded">cloud_download</span> {{ trans('messages.upgrade.title.upgrade') }}</span>
        </h1>
    </div>

@endsection

@section('content')

    <div class="tabbable">
        @include("admin.settings._tabs")

        <div class="tab-content">
            <div class="row">
                <div class="col-md-6">
                    @if (session('alert-error'))
                        @include('elements._notification', [
                            'level' => 'warning',
                            'title' => 'Cannot upgrade',
                            'message' => session('alert-error')
                        ])
                    @endif

                    @if (isset($failed))
                        <p class="alert alert-warning">
                            {{ trans('messages.upgrade.error.something_wrong') }}
                        </p>

                        <h3>{{ trans('messages.upgrade.title.in_progress') }}</h3>
                        <p>{!! trans('messages.upgrade.error.cannot_write') !!}</p>
                        <p>
                            <pre>{!! implode("\n", $failed) !!}</pre>
                        </p>
                        <p>
                            <a href="{{ action('Admin\SettingController@doUpgrade') }}" role="button" class="btn btn-primary me-1 upgrade-now">
                                {{ trans('messages.upgrade.button.retry') }}
                            </a>
                            <a link-confirm="{{ trans('messages.upgrade.upgrade_cancel') }}" href="{{ action('Admin\SettingController@cancelUpgrade') }}" role="button" class="btn btn-secondary btn-icon" link-method="POST">
                                {{ trans('messages.upgrade.button.cancel') }}
                            </a>
                        </p>
                    @elseif ($manager->isNewVersionAvailable())
                        <h3>{{ trans('messages.upgrade.title.upgrade_confirm') }}</h3>
                        <p>{!! trans('messages.upgrade.wording.upgrade', [ 'current' => "<code>{$manager->getCurrentVersion()}</code>", 'new' => "<code>{$manager->getNewVersion()}</code>" ]) !!}</p>
                        <p>
                            <a href="{{ action('Admin\SettingController@doUpgrade') }}" role="button" class="btn btn-primary me-1 upgrade-now">
                                {{ trans('messages.upgrade.button.upgrade_now') }}
                            </a>
                            <a link-confirm="{{ trans('messages.upgrade.upgrade_cancel') }}" href="{{ action('Admin\SettingController@cancelUpgrade') }}" role="button" class="btn btn-secondary btn-icon" link-method="POST">
                                {{ trans('messages.upgrade.button.cancel') }}
                            </a>
                        </p>
                    @elseif (!$phpversion)
                        <h3>{{ trans('messages.upgrade.title.current') }}</h3>
                        <p>{!! trans('messages.upgrade.wording.upload', [ 'current' => "<code>{$manager->getCurrentVersion()}</code>" ]) !!}</p>
                        
                        @include('elements._notification', [
                            'level' => 'warning',
                            'title' => trans('messages.requirement.php_version.not_supuported.title'),
                            'message' => trans('messages.requirement.php_version.not_supuported.description', ['current' => '<strong>'.PHP_VERSION.'</strong>', 'required' => '<strong>'.config('custom.php_recommended').'</strong>']),
                        ])
                    @else
                        @if ($license && ($license->isExpired() || $license->isInactive()))
                            @php
                                $admin = Auth::user()->admin;
                                $spportedUntil = $license->getSupportedUntil($admin->getTimezone());

                                if (config('custom.japan')) {
                                    $entitlementLink = '#';
                                } else {
                                    $entitlementLink = 'https://codecanyon.net/item/acelle-email-marketing-web-application/17796082';
                                }

                                $style = 'alert-danger';

                                if ($license->isExpired()) {
                                    $title = trans('messages.support.expired.explanation', [
                                        'expr' =>  $admin->formatDateTime($spportedUntil, 'datetime_full_with_timezone'),
                                        'diffs' => $spportedUntil->diffForHumans()]
                                    );
                                } elseif ($license->isInactive()) {
                                    $title = trans('messages.license.error.invalid');
                                }
                            @endphp

                            <div class="sub-section">
                                <h3>{{ trans('messages.license.your_license') }}</h3>
                                <p>{{ $title }}</p>
                                <div class="alert {{ $style }}" style="display: flex; flex-direction: row; align-items: center;">
                                    <div style="display: flex; flex-direction: row; align-items: center;">
                                        <p style="padding-left: 5px;padding-right: 40px">{{ $license->getLicenseNumber() }}{!! $license->isExpired() ? ' | <a href="'.$entitlementLink.'"><strong style="text-decoration: underline;">'.trans('messages.support.expired.note').'</strong></a>' : '' !!}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <h3>{{ trans('messages.upgrade.title.current') }}</h3>
                        <p>{!! trans('messages.upgrade.wording.upload', [ 'current' => "<code>{$manager->getCurrentVersion()}</code>" ]) !!}</p>
                        <p>{{ trans('messages.upgrade.notice') }}</p>
                        <ul>
                            <li><code>post_max_size</code> <strong>{{ ini_get('post_max_size') }}</strong></li>
                            <li><code>upload_max_filesize</code> <strong>{{ ini_get('upload_max_filesize') }}</strong></li>
                        </ul>
                        <form id="upgradeUploadForm" action="{{ action('Admin\SettingController@uploadApplicationPatch') }}"  class="form-validate-jquery" method="POST"  enctype="multipart/form-data">
                            {{ csrf_field() }}

                            @include('helpers.form_control', ['required' => true, 'type' => 'file', 'label' => '', 'name' => 'file', 'value' => 'Upload'])
                            <button class="btn btn-secondary upload-button">{{ trans('messages.upload') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            $('#upgradeUploadForm').on('submit', function() {
                if ($(this).valid()) {
                    addMaskLoading('{{ trans('messages.upgrade.uploading') }}');
                }
            });

            $('.upgrade-now').on('click', function(e) {
                e.preventDefault();
                    
                var url = $(this).attr('href');
                var confirm = '{{ trans('messages.upgrade.upgrade_confirm') }}';
                var method = 'POST';
                var type = 'link';

                new Link({
                    type: 'link',
                    url: url,
                    confirm: confirm,
                    method: method,
                    before: function() {
                        addMaskLoading('{{ trans('messages.upgrade.checking_dependencies') }}');
                    }
                });
            });
        });
    </script>
@endsection
