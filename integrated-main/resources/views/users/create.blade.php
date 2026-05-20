@extends('design')
@section('content')
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">New User</h1>
    </div>

</div>
<!-- /.row -->
<div class="row">
    <div class="col-lg-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                Create Single User
            </div>
            <div class="panel-body">
                {{Form::open(['url' => 'users', 'method' => 'post'])}}
                    <div class="form-group">
                        <label>Username</label>
                        <input class="form-control" type="text" name="username">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input class="form-control" type="password" name="password">
                    </div>
                    <div class="form-group">
                        <label>Assign Profile</label>
                        <select class="form-control" name="actual_profile">
                            @for($i=0;$i<count($profiles); $i++)
                                <option value="{{$profiles[$i]['name']}}">{{$profiles[$i]['name']}}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-user-plus"></i> Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
