@extends('design')
@section('content')
<div class="row">
<div class="col-lg-12">
    <h1 class="page-header">Profiles</h1>
</div>
<!-- /.col-lg-12 -->
</div>
<!-- /.row -->
<div class="row">
<div class="col-lg-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            List of Profiles
        </div>
        <!-- /.panel-heading -->
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover" id="dataTables-example">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Validity</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @for($i=0;$i<count($profiles); $i++)
                        <tr>
                            <td>{{$profiles[$i]['.id']}}</td>
                            <td>{{$profiles[$i]['name']}}</td>
                            <td>{{$profiles[$i]['validity']}}</td>
                            <td>{{$profiles[$i]['owner']}}</td>
                            <td><a href="#" class="btn btn-warning" onclick="return confirm('Want to delete  {{$profiles[$i]['name']}}  Click OK to confirm ?');">Delete</a></td>
                        </tr>
                    @endfor
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
@endsection
