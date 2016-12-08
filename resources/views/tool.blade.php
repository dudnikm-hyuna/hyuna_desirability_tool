@extends('layouts.app')

@section('content')
    <div class="container undesirable-affiliate-table-container">
        <table id="undesirable_affiliates" class="table table-striped table-bordered" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>Affiliate Name</th>
                <th>Affiliate ID</th>
                <th>Affiliate Status</th>
                <th>Affiliate Country</th>
                <th>Affiliate Type</th>
                <th>Affiliate Size</th>
                <th>Date Added</th>
                <th>Review Date</th>
                <th>Avg CPS</th>
                <th>Total Sales 126</th>
                <th>Total Cost 126</th>
                <th>Gross Margin 126</th>
                <th>Num Disputes 126</th>
                <th>Desirability Score</th>
                <th>Workout Program</th>
                <th>Original Price Program</th>
                <th>Updated Price Program</th>
                <th>Workout duration</th>
                <th>WP Set Date</th>
                <th>Program status</th>
                <th>Id</th>
                <th>Email Status</th>
                <th>Email Sent Date</th>
                <th>Is Informed</th>
                <th>Email</th>
            </tr>
            </thead>
        </table>
    </div>

    <!-- Set Workout Program Modal -->
    <div class="modal fade" id="set-program-modal" tabindex="-1" role="dialog" aria-labelledby="wp-modal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="wp-modal-label">Set Workout program</h4>
                </div>
                <div class="modal-body">
                    Are you sure you want to set Workout program?
                </div>
                <div class="modal-footer">
                    <div class="preloader">
                        <img src="/img/preloader.gif" alt="">
                    </div>
                    <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
                    <button type="button" class="btn btn-primary" id="set-program-confirm">Yes</button>
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
                    <div class="preloader">
                        <img src="/img/preloader.gif" alt="">
                    </div>
                    <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
                    <button type="button" class="btn btn-primary" id="send-email-confirm">Yes</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        var table = $('#undesirable_affiliates').DataTable({
            ajax: '{{ url("undesirable-affiliates-data") }}',
            "paging": true,
            "columns": [
                {"data": "affiliate_name", "className": "affiliate_name"},
                {"data": "affiliate_id"},
                {"data": "aff_status"},
                {"data": "country_code"},
                {"data": "aff_type"},
                {"data": "aff_size"},
                {"data": "date_added", "className": "date_added"},
                {"data": "reviewed_date", "className": "reviewed_date"},
                {"data": "aff_price", "className": "affiliate_price"},
                {"data": "total_sales_126", "className": "total_sales_126"},
                {"data": "total_cost_126", "className": "total_cost_126"},
                {"data": "gross_margin_126", "className": "gross_margin_126"},
                {"data": "num_disputes_126", "className": "num_disputes_126"},
                {"data": "desirability_scores", "className": "desirability_scores"},
                {"data": "workout_program_id", "className": "workout_program_id"},
                {"data": "original_price_program", "className": "original_price_program"},
                {"data": "updated_price_program", "className": "updated_price_program"},
                {"data": "workout_duration", "className": "workout_duration"},
                {"data": "workout_set_date", "className": "workout_set_date"}, //17
                {"data": "program_status", "className": "program_status"},
                {"data": "id"},
                {"data": "email_status", "className": "email_status"},
                {"data": "email_sent_date", "className": "email_sent_date"},
                {"data": "is_informed"},
                {"data": "email"}
            ],
            "columnDefs": [
                {
                    "render": function (data, type, row) {
                        return    '<span class="cell-data-container">' + formatDate(data) + '</span>';
                    },
                    "targets": [6, 7, 18]
                },
                { // affilaite name
                    "render": function (data, type, row) {
                        return '<span class="cell-data-container">' +
                                    '<a href="{{ url("undesirable-affiliate") }}' + '/' + row.affiliate_id +'">' + row.name + '</a> ' +
                                    '<span data-affiliate-id="' + row.affiliate_id + '"' +
                                        'class="btn-history glyphicon glyphicon-header"' +
                                        'data-toggle="tooltip" title="Show history">' +
                                    '</span>' +
                                    '<span class="btn-remove-history glyphicon glyphicon-remove"></span>' +
                                    '<a href="{{ url("undesirable-affiliates-stats-by-country") }}' + '/' + row.affiliate_id +'">' +
                                        '<span data-affiliate-id="' + row.affiliate_id + '"' +
                                        'class="btn-country-info glyphicon glyphicon-globe"' +
                                        'data-toggle="tooltip" title="Stats by contry">' +
                                        '</span>' +
                                    '</a>' +
                                '</span>';
                    },
                    "targets": 0
                },
                { // affilaite price (Avg CPS), total_cost_126
                    "render": function (data, type, row) {
                        return '<span class="cell-data-container">$' + data + '</span>';
                    },
                    "targets": [8,10]
                },
                { // gross_margin_126
                    "render": function (data, type, row) {
                        return '<span class="cell-data-container">' + parseFloat((data*100).toFixed(2)) + '%' + '</span>';
                    },
                    "targets": 11
                },
                { //workout program
                    "render": function (data, type, row) {
                        var emailSentTime = Date.parse(row.email_sent_date) || 0;
                        var currentTime = Date.now();
                        var pendingTime = +new Date(emailSentTime + 2*24*60*60*1000);

                        if (currentTime <= pendingTime) {
                            return '<span class="cell-data-container wp_' + data + '">' + data + '</span>';
                        } else {
                            var options = '';
                            var wp_id;
                            for (wp_id = 1; wp_id <= 4; wp_id++) {
                                var is_selected = (wp_id == data) ? 'selected' : '';
                                options += '<option class="wp_' + wp_id + '" ' + is_selected + ' value="' + wp_id + '">' + wp_id + '</option>';
                            }

                            return '<span data-informed=' + row.is_informed + ' data-id=' + row.id + ' class="cell-data-container wp_' + row.workout_program_id + '">' +
                                        '<select name="wp-list" class="wp-list ">' +
                                            '<option disabled selected value> --select wp-- </option>'
                                            + options +
                                        '</select>' +
                                    '</span>';
                        }
                    },
                    "targets": 14
                },
                { //updated price name
                    "render": function (data, type, row) {
                        return '<span class="cell-data-container" data-program-price=' + data + '>' + data + '</span>';
                    },
                    "targets": 16
                },
                { // program status
                    "render": function (data, type, row) {
                        if (data == 0) {
                            return '<span class="cell-data-container">not in program</span>';
                        } else if(data == 1) {
                            var currentTime = Date.now();
                            var wp_set_date = Date.parse(row.workout_set_date) || 0;
                            var wp_end_date = new Date(wp_set_date + row.workout_duration*24*60*60*1000);
                            var status = (wp_end_date < currentTime) ? 'review' : 'in_program';

                            return '<span class="cell-data-container" data-program-status="1">' + status + '</span>';
                        }
                    },
                    "targets": 19
                },
                { // email status
                    "render": function (data, type, row) {
                        var emailSentTime = Date.parse(row.email_sent_date) || 0;
                        var currentTime = Date.now();
                        var pendingTime = +new Date(emailSentTime + 2*24*60*60*1000);
                        var wp_set_date = Date.parse(row.workout_set_date) || 0;
                        var disabled = (row.workout_program_id) ? '' : 'disabled';

                        if (row.workout_duration != 0 && wp_set_date != 0) {
                            var wp_end_date = new Date(wp_set_date + row.workout_duration*24*60*60*1000);
                        }

                        if (data == 'send') {
                            return '<span class="cell-data-container"><button data-id="' + row.id + '" class="btn btn-default btn-xs ' + disabled + ' btn-send-email">Send email</button></span>';
                        } else if (data == 'not_sent') {
                            return '<span class="cell-data-container">Not sent email</span>';
                        } else if (data == 'sent') {
                            if (currentTime <= pendingTime) {
                                return '<span class="cell-data-container email-yellow">' + row.email_sent_date + '</span>';
                            } else {
                                var email_color = (row.program_status == 0) ? 'email-green' : '';

                                return '<span class="cell-data-container ' + email_color + '">' + row.email_sent_date + '</span>';
                            }
                        } else if (data == 'wp_change') {
                            return '<span class="cell-data-container">' + row.email_sent_date + '</span>';
                        }
                    },
                    "targets": 21
                },
                {
                    "render": function (data, type, row) {
                        return '<span class="cell-data-container">' + data + '</span>';
                    },
                    "targets": '_all'
                },
                { "visible": false, "targets": [20, 22, 23, 24] }
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
            var tr = $(this).closest("tr");
            var updated_price_container = tr.find(".updated_price_program > .cell-data-container");
            var program_status = tr.find("[data-program-status]").attr("data-program-status");

            $(this).closest(".cell-data-container").removeClass().addClass("cell-data-container wp_" + wp_id);

            if (wp_id == 3) {
                updated_price_container.html('<select class="price-program-list">' +
                        '<option value="regular_cpa">regular_cpa</option>' +
                        '<option value="premium_cpa">premium_cpa</option>' +
                        '<option value="spu">spu</option>' +
                        '</select>');
            } else if (wp_id == 2){
                updated_price_container.attr("data-program-price", "premium_cpa");
                updated_price_container.html("premium_cpa");
            } else if (wp_id == 4) {
                updated_price_container.attr("data-program-price", "rev_share");
                updated_price_container.html("rev_share");
            } else {
                updated_price_container.attr("data-program-price", "regular_cpa");
                updated_price_container.html("regular_cpa");
            }

            var id = $(this).closest('.cell-data-container').attr("data-id");
            if (program_status == 1) {
                tr.find(".program_status > .cell-data-container").html('<button data-id="' + id + '" class="btn btn-default btn-xs btn-set-program">Set program</button>');
                tr.find(".email_status > .cell-data-container").html('<button data-id="' + id + '" class="btn btn-default btn-xs btn-send-email">Send email</button>');
            } else {
                tr.find('.btn-send-email').removeClass('disabled');
                tr.find(".program_status > .cell-data-container").html('<button data-id="' + id + '" class="btn btn-default btn-xs btn-set-program">Set program</button>');
            }
        });

        $("#undesirable_affiliates").on("click", ".btn-set-program", function(){
            var tr = $(this).closest("tr");
            var id = $(this).attr("data-id");
            var wp_id = tr.find(".wp-list").val();
            var program_price = tr.find(".updated_price_program > .cell-data-container").attr("data-program-price");

            tr.attr("data-id", id);

            $("#set-program-confirm").attr("data-id", id);
            $("#set-program-confirm").attr("data-wp-id", wp_id);
            $("#set-program-confirm").attr("data-program-price", program_price);
            $('#set-program-modal').modal('show');

        });

        $("#undesirable_affiliates").on("change",".price-program-list", function(){
            var program_price = $(this).val();
            $(this).closest(".cell-data-container").attr("data-program-price", program_price);
        });

        $("#set-program-confirm").on("click", function(){
            var id = $(this).attr("data-id");
            var wp_id = $(this).attr("data-wp-id");
            var program_price = $(this).attr("data-program-price");
            var tr = $("#undesirable_affiliates").find("tr[data-id=" + id + "]");

            $(".preloader").show();

            $.ajax({
                        method: "GET",
                        url: '{{ url("set-workout-program") }}' + '/' + id + '/' + wp_id + '/' + program_price
                    })
                    .done(function (underisable_affiliate) {
                        table.row(tr).data(underisable_affiliate);
                        $(".preloader").hide();
                        $('#set-program-modal').modal('hide');
                    });
        });

        $("#undesirable_affiliates").on("click", ".btn-send-email", function(){
            if ($(this).hasClass('disabled')) {
                return false;
            }
            var id = $(this).attr("data-id");
            var tr = $(this).closest("tr");

            tr.attr("data-id", id);

            $("#send-email-confirm").attr("data-id", id);

            $('#send-email-modal').modal('show');
        });

        $("#send-email-confirm").on("click", function(){
            var id = $(this).attr("data-id");
            var tr = $(document).find("tr[data-id=" + id + "]");

            $(".preloader").show();

            $.ajax({
                        method: "GET",
                        url: '{{ url("send-email") }}' + '/' + id,
                    })
                    .done(function (underisable_affiliate) {
                        table.row(tr).data(underisable_affiliate);
                        $(".preloader").hide();
                        $('#send-email-modal').modal('hide');
                    });
        });

        $("#undesirable_affiliates").on("click", ".btn-country-info", function(){
            $(".navbar-preloader").show(300);
        });

        function showUndesirableAffiliateHistory(history, tr) {
            var rows_to_show = [
                'aff_price',
                'aff_size',
                'aff_status',
                'reviewed_date',
                'total_cost_126',
                'total_sales_126',
                'gross_margin_126',
                'num_disputes_126',
                'desirability_scores',
                'updated_price_program',
                'workout_set_date',
                'workout_duration',
                'program_status',
            ];
            console.log(history);
            history.forEach(function (e) {
                $.each(e, function( key, value ) {
                    var in_array = $.inArray( key, rows_to_show);

                    if (in_array == -1) {
                        return true;
                    }

                    var td = tr.find('td.' + key);
                    var wp_class = ( key == 'workout_program_id') ? "wp_" + value : ""; // background color for wp
                    if (td.length !== 0) {
                        if (key == 'date_added' || key == 'reviewed_date' || key == 'workout_set_date') {
                            td.append('<span class="history-data">' + formatDate(value) + '</span>');
                        } else {
                            td.append('<span class="history-data ' + wp_class + '">' + value + '</span>');
                        }
                    }
                });
            });

            return true;
        }

        function formatDate(dateTime) {
            if (Date.parse(dateTime)) {
                var date = new Date(dateTime);
                var day = date.getDate();
                var month = date.getMonth() + 1;
                var year = date.getFullYear();

                return year + '-' + month + '-' + day;
            } else {
                return '-';
            }
        }
    });
</script>
@endsection