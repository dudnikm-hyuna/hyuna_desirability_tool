@extends('layouts.app')

@section('content')
    <div class="container undesirable-affiliate-table-container">
        <h2>Users list <a class="btn btn-info btn-back-to-tool" href="{{ URL::previous() }}"> < Back to tool</a></h2>
        <table id="users-list" class="table table-striped table-bordered" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Allowed</th>
            </tr>
            </thead>
        </table>
    </div>

    <!-- Set Admin Modal -->
    <div class="modal fade" id="change-role-modal" tabindex="-1" role="dialog" aria-labelledby="send-email-modal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="send-email-label">Change permissions</h4>
                </div>
                <div class="modal-body">
                    Are you sure you want to change permissions?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
                    <button type="button" class="btn btn-primary" id="change-role-confirm">Yes</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            var table = $('#users-list').DataTable({
                ajax: '{{ url("users-data") }}',
                "bFilter": false,
                "paging": false,
                "columns": [
                    {"data": "name"},
                    {"data": "email"},
                    {"data": "created_at"},
                    {"data": "updated_at"},
                    {"data": "role", "className": "is_manager"},
                ],
                "columnDefs": [
                    {
                        "render": function (data, type, row) {
                            var checked = (data == 'manager') ? 'checked' : '';
                            return  '<input data-id="' + row.id + '" class="allow-checkbox" value="' + data + '" type="checkbox" ' + checked + '>';
                        },
                        "targets": 4
                    },
                ]
            });

            $("#users-list").on("change",".allow-checkbox", function(){
                var is_manager = ($(this).val() == 'manager') ? 0 : 1;
                var tr = $(this).closest("tr");
                var id = $(this).attr("data-id");

                tr.attr("data-id", id);

                $('#change-role-confirm').attr('data-allow', is_manager);
                $('#change-role-confirm').attr('data-id', id);
                $('#change-role-modal').modal('show');
            });

            $("#change-role-confirm").on("click", function(){
                var is_manager = $(this).attr('data-allow');
                var id = $(this).attr("data-id");
                var tr = $("#users-list").find("tr[data-id=" + id + "]");

                $.ajax({
                            method: "GET",
                            url: '{{ url("change-user-role") }}' + '/' + id + '/' + is_manager
                        })
                        .done(function (user) {
                            table.row(tr).data(user);
                            $('#change-role-modal').modal('hide');
                        });
            });
        });


    </script>
@endsection