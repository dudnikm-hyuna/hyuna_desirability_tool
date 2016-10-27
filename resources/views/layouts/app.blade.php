<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Desirability tool') }}</title>

    <!-- Styles -->
    <link href="https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css" rel="stylesheet"/>
    <link href="/css/app.css" rel="stylesheet">

    <!-- Scripts -->
    <script>
        window.Laravel = <?php echo json_encode([
                'csrfToken' => csrf_token(),
        ]); ?>
    </script>
</head>
<body>
<div id="app">
    <nav class="navbar navbar-default navbar-static-top">
        <div class="container">
            <div class="navbar-header">

                <!-- Collapsed Hamburger -->
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                        data-target="#app-navbar-collapse">
                    <span class="sr-only">Toggle Navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <!-- Branding Image -->
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Desirability tool') }}
                </a>
            </div>

            <div class="collapse navbar-collapse" id="app-navbar-collapse">
                <!-- Left Side Of Navbar -->
                <ul class="nav navbar-nav">
                    &nbsp;
                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="nav navbar-nav navbar-right">
                    <!-- Authentication Links -->
                    @if (Auth::guest())
                        <li><a href="{{ url('/login') }}">Login</a></li>
                        <li><a href="{{ url('/register') }}">Register</a></li>
                    @else
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                               aria-expanded="false">
                                {{ Auth::user()->name }} <span class="caret"></span>
                            </a>

                            <ul class="dropdown-menu" role="menu">
                                <li>
                                    <a href="{{ url('/logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        Logout
                                    </a>

                                    <form id="logout-form" action="{{ url('/logout') }}" method="POST"
                                          style="display: none;">
                                        {{ csrf_field() }}
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    @yield('content')
</div>

<!-- Scripts -->

<script src="/js/app.js"></script>
<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function () {
        var table = $('#undesirable_affiliates').DataTable({
            ajax: '{{ url("undesirable-affiliates-data") }}',
            "columns": [
                {"data": "affiliate_name", "className": "affiliate-name"},
                {"data": "affiliate_id"},
                {"data": "affiliate_status"},
                {"data": "country_code"},
                {"data": "affiliate_type"},
                {"data": "affiliate_size"},
                {"data": "date_added"},
                {"data": "reviwed_date"},
                {"data": "affiliate_price"},
                {"data": "total_sales_126"},
                {"data": "total_cost_126"},
                {"data": "gross_margin_126"},
                {"data": "num_disputes_126"},
                {"data": "desirability_score"},
                {"data": "workout_program_id", "className": "wp-id"},
                {"data": "updated_price_name"},
                {"data": "updated_price"},
                {"data": "workout_duration"},
                {"data": "workout_set_date"},
                {"data": "in_program"},
            ],
            "columnDefs": [
                {
                    "render": function (data, type, row) {
                        return data + ' <span data-affiliate-id="' + row.affiliate_id + '" class="btn-history glyphicon glyphicon-header" data-toggle="tooltip" title="Show history"></span>';
                    },
                    "targets": 0
                },
                {
                    "render": function (data, type, row) {
                        var button = data + ' <span data-affiliate-id="' + row.affiliate_id + '" class="btn-history glyphicon glyphicon-header" data-toggle="tooltip" title="Show history"></span>';

                        console.log(data);
                        return (data) ? button : '';
                    },
                    "targets": 0
                },
                {

                    "render": function (data, type, row) {
                        var options = '';
                        var wp_id;
                        for (wp_id = 0; wp_id < 3; wp_id++) {
                            var is_selected = (wp_id == data) ? 'selected' : '';
                            options += '<option ' + is_selected + ' value="' + wp_id + '">' + wp_id + '</option>';
                        }

                        var select_template = '<div class="wp-list-container">' +
                                '<select name="wp-list" id="" class="wp-list">'
                                + options +
                                '</select>' +
                                '</div>';

                        return select_template;
                    },
                    "targets": 14
                },
                {
                    "render": function (data, type, row) {
                        return (data == 1) ? 'in program' : ' set program';
                    },
                    "targets": 19
                }
            ]
        });

        $('#undesirable_affiliates tbody').on('click', '.btn-history', function () {
            var id = $(this).attr('data-affiliate-id');
            var tr = $(this).closest( "tr" );

            tr.children().css('vertical-align','text-top').append( "<p>Test</p>" );

            $.ajax({
                        method: "GET",
                        url: '{{ url("undesirable-affiliates-history-data") }}' + '/' + id,
                    })
                    .done(function (history) {
                        if(history.data.length  == 0) {
                            alert('History not exist');
                            return false;
                        }

                        showUndesirableAffiliateHistory(history.data, tr);
                    });
        });

        function showUndesirableAffiliateHistory(history, tr) {
//                var count = 0;
//                var currentPage = table.page();
//
//                //insert a test row
//                count++;
//
//                table.row.add({
//                            "affiliate_name": "",
//                            "affiliate_id": "",
//                            "affiliate_status": "",
//                            "country_code": "",
//                            "affiliate_type": "",
//                            "affiliate_size": "",
//                            "date_added": "",
//                            "reviwed_date": "test",
//                            "affiliate_price": "test",
//                            "total_sales_126": "test",
//                            "total_cost_126": "test",
//                            "gross_margin_126": "test",
//                            "num_disputes_126": "test",
//                            "desirability_score": "test",
//                            "workout_program_id": "test",
//                            "updated_price_name": "test",
//                            "updated_price": "test",
//                            "workout_duration": "test",
//                            "workout_set_date": "test",
//                            "in_program": "test"
//                        }).draw();
//
//                var index = table.row(tr).index(),
//                        rowCount = table.data().length-1,
//                        insertedRow = table.row(rowCount).data(),
//                        tempRow;
//
//
//                for (var i=rowCount;i>index;i--) {
//                    tempRow = table.row(i-1).data();
//                    table.row(i).data(tempRow);
//                    table.row(i-1).data(insertedRow);
//                }
//                //refresh the page
//                table.page(currentPage).draw(false);

        }
    });
</script>
</body>
</html>