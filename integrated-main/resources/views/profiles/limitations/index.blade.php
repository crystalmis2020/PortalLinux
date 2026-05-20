@extends('design')
@section('content')
<div class="row">
<div class="col-lg-12">
    <h1 class="page-header">Limitations</h1>
</div>
<!-- /.col-lg-12 -->
</div>
<!-- /.row -->
<div class="row">
<div class="col-lg-12">
    <div class="panel panel-default">
        <div class="panel-heading">
            List of Limitations
        </div>
        <!-- /.panel-heading -->
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover" id="dataTables-example">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Uptime Limit</th>
                            <th>Created By</th>
                            <th>Ip Pool</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @for($i=0;$i<count($limitations); $i++)
                            <tr>
                                <td>{{$limitations[$i]['.id']}}</td>
                                <td>{{$limitations[$i]['name']}}</td>
                                <td>{{@$limitations[$i]['uptime-limit']}}</td>
                                <td>{{$limitations[$i]['owner']}}</td>
                                <td>{{@$limitations[$i]['ip-pool']}}</td>
                                <td>
                                    <a href="{{ route('limitationEdit', ['id' => $limitations[$i]['.id'] ]) }}" class="btn btn-success">Edit</a>
                                    <a href="{{route('limitationDestroy', ['id'=>$limitations[$i]['.id'], 'name'=> $limitations[$i]['name']])}}" class="btn btn-warning" onclick="return confirm('Want to delete  {{$limitations[$i]['name']}}  Click OK to confirm ?');">Delete</a>
                                </td>
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
