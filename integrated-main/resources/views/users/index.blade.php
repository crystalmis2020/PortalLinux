@extends('design')
@section('content')
<div class="row">
<div class="col-lg-12">
    <h1 class="page-header">Users</h1>
</div>
<!-- /.col-lg-12 -->
</div>
<!-- /.row -->
<div class="row">
<div class="col-lg-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            List of Users
        </div>
        <!-- /.panel-heading -->
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover" id="dataTables-example">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Uptime Used</th>
                            <th>Actual Profile</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @for($i=0;$i<count($users); $i++)
                    
                        @if(@$users[$i]['actual-profile'] != 'admin')
                            <tr>
                                <td>{{$users[$i]['.id']}}</td>
                                <td>{{$users[$i]['username']}}</td>
                                <td>{{@$users[$i]['uptime-used']}}</td>
                                <td>{{@$users[$i]['actual-profile']}}</td>
                                <td><button type="button" onclick="return onClickDelete('{{$users[$i]['username']}}', '{{route('userDestroy', ['id' => $users[$i]['.id'], 'username' => $users[$i]['username']] )}}')" class="btn btn-warning" data-toggle="modal" data-target="#confirm-delete">Delete</button></td>
                            </tr>
                        @endif
                    @endfor
                    
                    <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                        <h4 class="modal-title" id="myModalLabel">Confirm Delete</h4>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to delete <span id="username-to-delete" class="text-danger"></span> ?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                        <a href="" class="btn btn-primary" id="href-username-to-delete">Yes</a>
                                    </div>
                                </div>
                                <!-- /.modal-content -->
                            </div>
                            <!-- /.modal-dialog -->
                        </div>

                    </tbody>
                </table>
            </div>
            <!-- /.table-responsive -->
        </div>
        <!-- /.panel-body -->
    </div>
    <!-- /.panel -->
</div>
<!-- /.col-lg-12 -->
</div>

<script>
    function onClickDelete(username, url){
        document.getElementById('username-to-delete').innerHTML  = username;
        document.getElementById('href-username-to-delete').href = url;
    }
</script>
@endsection
