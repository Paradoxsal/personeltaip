<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Soft UI Dashboard</title>
    <!-- Fonts and icons -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- CSS Files -->
    <link id="pagestyle" href="../assets/css/soft-ui-dashboard.css?v=1.0.3" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>

<body class="g-sidenav-show  bg-gray-100">
    @auth
        @yield('auth')
    @endauth
    @guest
        @yield('guest')
    @endguest

    @if (session('success'))
        <div id="success-message" class="alert alert-success" style="max-width: 600px; margin: 10px auto;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div id="error-message" class="alert alert-danger" style="max-width: 600px; margin: 10px auto;">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/fullcalendar.min.js"></script>
    <script src="../assets/js/plugins/chartjs.min.js"></script>
    @stack('rtl')
    @stack('dashboard')
    <script>
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), {
                damping: '0.5'
            });
        }
    </script>
    <script src="../assets/js/soft-ui-dashboard.min.js?v=1.0.3"></script>

    <script>
        $('.userSelectSearch').select2({
            placeholder: "Kullanıcı arayın veya seçin",
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0
        });
    </script>
    @stack('js')
</body>

</html>
