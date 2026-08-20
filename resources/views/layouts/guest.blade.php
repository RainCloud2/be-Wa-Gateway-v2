<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ asset('assets/img/kaiadmin/favicon.ico') }}" type="image/x-icon" />
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/plugins.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/kaiadmin.min.css') }}" />
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    @stack('styles')
</head>
<body>
    {{ $slot }}

    <script src="{{ asset('assets/js/core/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>

    @stack('scripts')
    @livewireScripts

    <script>
        document.addEventListener('notify', function(e) {
            var detail = e.detail;
            if (typeof $.notify === 'function') {
                $.notify({
                    title: detail.title || (detail.type === 'success' ? 'Success' : detail.type === 'danger' ? 'Error' : detail.type === 'warning' ? 'Warning' : 'Info'),
                    message: detail.message,
                    icon: detail.type === 'success' ? 'fa fa-check-circle' : 'fa fa-bell',
                }, {
                    type: detail.type === 'danger' ? 'danger' : detail.type || 'info',
                    placement: { from: 'bottom', align: 'right' },
                    time: 3000,
                });
            }
        });
    </script>
</body>
</html>
