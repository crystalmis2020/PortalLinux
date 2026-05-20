@extends('rfi.designAdmin')
@section('content')
    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">Administrative Panel</h4>
                </div>
            </div>
            <div class="col-sm-6 clearfix">
                <div class="user-profile pull-right">
                    <img class="avatar user-thumb" src="{{asset('images/rfi/author/avatar.png')}}" alt="avatar">
                    <h4 class="user-name dropdown-toggle" data-toggle="dropdown">{{Request::ip()}} <i class="fa fa-angle-down"></i></h4>
                    
                </div>
            </div>
        </div>
    </div>
    @include('messages')
    <div class="card-body">
        <h4 class="header-title">Accesses</h4>
        <div class="single-table">
            <div class="table-responsive">
                <table class="table text-center">
                    <thead class="text-uppercase bg-primary">
                        <tr class="text-white">
                            <th scope="col">Username</th>
                            <th scope="col">Hours</th>
                            <th scope="col">Requested By</th>
                            <th scope="col">Approve by</th>
                            <th scope="col">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($accesses as $access)
                    <tr>
                        <td scope="row">{{$access->username}}</td>
                        <td>{{$access->hours}}</td>
                        <td>{{$access->requests->requestor_name}}</td>
                        <td>{{$access->approve_by}}</td>
                        <td>{{$access->created_at->format('M d, Y')}}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <!--<td colspan="5"></td> -->
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection