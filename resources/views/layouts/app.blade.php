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

    <!-- Change Workout Program Modal -->
    <div class="modal fade" id="wp-modal" tabindex="-1" role="dialog" aria-labelledby="wp-modal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="wp-modal-label">Change Workout program</h4>
                </div>
                <div class="modal-body">
                    Are you sure you want to change Workout program?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
                    <button type="button" class="btn btn-primary" id="wp-change-confirm">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Email Modal -->
    <div class="modal fade" id="send-email-modal" tabindex="-1" role="dialog" aria-labelledby="send-email-modal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="send-email-label">Send email to affiliate</h4>
                </div>
                <div class="modal-body">
                    Are you sure you want to send email?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
                    <button type="button" class="btn btn-primary" id="send-email-confirm">Yes</button>
                </div>
            </div>
        </div>
    </div>

<!-- Scripts -->

<script src="/js/app.js"></script>
<script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function () {
        var table = $('#undesirable_affiliates').DataTable({
            ajax: '{{ url("undesirable-affiliates-data") }}',
            "paging": false,
//            "aaSorting": [],
            "columns": [
                {"data": "affiliate_name", "className": "affiliate-name"},
                {"data": "affiliate_id"},
                {"data": "affiliate_status"},
                {"data": "country_code"},
                {"data": "affiliate_type"},
                {"data": "affiliate_size"},
                {"data": "date_added"},
                {"data": "review_date", "className": "review_date"},
                {"data": "affiliate_price", "className": "affiliate_price"},
                {"data": "total_sales_126", "className": "total_sales_126"},
                {"data": "total_cost_126", "className": "total_cost_126"},
                {"data": "gross_margin_126", "className": "gross_margin_126"},
                {"data": "num_disputes_126", "className": "num_disputes_126"},
                {"data": "desirability_score", "className": "desirability_score"},
                {"data": "workout_program_id", "className": "workout_program_id"},
                {"data": "updated_price_name", "className": "updated_price_name"},
                {"data": "updated_price", "className": "updated_price"},
                {"data": "workout_duration", "className": "workout_duration"},
                {"data": "workout_set_date", "className": "workout_set_date"},
                {"data": "in_program"},
                {"data": "id"},
                {"data": "email_sent_date", "className": "email_sent_date"},
                {"data": "email"}
            ],
            "columnDefs": [
                {
                    "render": function (data, type, row) {
                        return '<span class="cell-data-container">' + data + ' ' +
                                    '<span data-affiliate-id="' + row.affiliate_id + '"' +
                                        'class="btn-history glyphicon glyphicon-header"' +
                                        'data-toggle="tooltip" title="Show history">' +
                                    '</span>' +
                                    '<span class="btn-remove-history glyphicon glyphicon-remove"></span>' +
                                '</span>';
                    },
                    "targets": 0
                },
                {
                    "render": function (data, type, row) {
                        var options = '';
                        var wp_id;
                        for (wp_id = 0; wp_id <= 3; wp_id++) {
                            var is_selected = (wp_id == data) ? 'selected' : '';
                            options += '<option class="' + selectWPColor(wp_id) + '" ' + is_selected + ' value="' + wp_id + '">' + wp_id + '</option>';
                        }

                        var select_template = '<span data-id=' + row.id + ' class="cell-data-container wp_' + row.workout_program_id + '">' +
                                '<select name="wp-list" class="wp-list ">'
                                + options +
                                '</select>' +
                                '</span>';

                        return select_template;
                    },
                    "targets": 14
                },
                {
                    "render": function (data, type, row) {
                        var data = (data == 1) ? 'in program' : ' set program';

                        return '<span class="cell-data-container">' + data + '</span>';
                    },
                    "targets": 19
                },
                {
                    "render": function (data, type, row) {
                        var emailSentTime = Date.parse(data) || 0;

                        if(!emailSentTime) {
                            return '<span class="cell-data-container"><button data-id="' + row.id + ' " class="btn-send-email">Send email</button></span>';
                        } else {
                            var currentTime = Date.now();
                            var pendingTime = +new Date(emailSentTime + 2*24*60*60*1000);

                            if (currentTime <= pendingTime) {
                                return '<span class="cell-data-container email-yellow">' + data + '</span>';
                            } else {
                                return '<span class="cell-data-container email-green">' + data + '</span>';
                            }
                        }

                        return '<span class="cell-data-container">' + data + '</span>';
                    },
                    "targets": 21
                },
                {
                    "render": function (data, type, row) {
                        return '<span class="cell-data-container">' + data + '</span>';
                    },
                    "targets": '_all'
                },
                { "visible": false, "targets": [20,22] }
            ]
        });

        $('#undesirable_affiliates tbody').on('click', '.btn-history', function () {
            var id = $(this).attr('data-affiliate-id');
            var tr = $(this).closest("tr");
            var btn_history = $(this);
            var btn_remove_history = $(this).next();
            var history_data = tr.find(".history-data");

            if (history_data.length) {
                btn_history.hide();
                btn_remove_history.show();
                history_data.show();

                return true;
            }

            $.ajax({
                        method: "GET",
                        url: '{{ url("undesirable-affiliates-history-data") }}' + '/' + id,
                    })
                    .done(function (history) {
                        if (history.data.length == 0) {
                            alert('History not exist');
                            return false;
                        }
                        showUndesirableAffiliateHistory(history.data, tr);
                        btn_history.hide();
                        btn_remove_history.show();
                    });
        });

        $('#undesirable_affiliates tbody').on('click', '.btn-remove-history', function () {
            var tr = $(this).closest("tr");
            var btn_remove_history = $(this);
            var btn_history = $(this).prev();
            var history_data = tr.find(".history-data");
            history_data.hide();
            btn_history.show();
            btn_remove_history.hide();
        });

        $("#undesirable_affiliates").on("change",".wp-list", function(){
            var wp_id = $(this).val();
            var select_wrapper = $(this).parent(".cell-data-container");
            var id = select_wrapper.attr("data-id");
            var tr = select_wrapper.closest("tr");

           tr.attr("data-id", id);

            var wp_change_confirm_btn = $("#wp-change-confirm");
            wp_change_confirm_btn.attr("data-id", id);
            wp_change_confirm_btn.attr("data-wp-id", wp_id);

            $('#wp-modal').modal('show');
        });

        $("#wp-change-confirm").on("click", function(){
           var id = $(this).attr("data-id");
           var wp_id = $(this).attr("data-wp-id");
           var tr = $("#undesirable_affiliates").find("tr[data-id=" + id + "]");
            console.log(id, wp_id, tr);

            $.ajax({
                method: "GET",
                    url: '{{ url("update-undesirable-affiliate") }}' + '/' + id + '/' + wp_id,
                })
                .done(function (underisable_affiliate) {
                    table.row(tr).data(underisable_affiliate);
                    $('#wp-modal').modal('hide');
            });
        });

        $("#undesirable_affiliates").on("click", ".btn-send-email", function(){
            console.log('test');
            $('#send-email-modal').modal('show');
        });

        function showUndesirableAffiliateHistory(history, tr) {
            history.forEach(function (e) {
                $.each(e, function( key, value ) {
                    var td = tr.find('td.' + key);
                    var wp_class = ( key == 'workout_program_id') ? "wp_" + value : ""; // background color for wp
                    if (td.length !== 0) {
                        td.append('<span class="cell-data-container history-data ' + wp_class + '">' + value + '</span>');
                    }
                });
            });

            return true;
        }

        function selectWPColor(wp_id) {
            switch (wp_id) {
                case 0:
                    wp_color = "wp_0";
                    break;
                case 1:
                    wp_color = "wp_1";
                    break;
                case 2:
                    wp_color = "wp_2";
                    break;
                case 3:
                    wp_color = "wp_3";
            }

            return wp_color;
        }
    });
</script>
</body>
</html>