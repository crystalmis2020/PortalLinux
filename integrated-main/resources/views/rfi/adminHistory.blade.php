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
        <h4 class="header-title">History</h4>
        <div class="single-table">
            <div class="table-responsive">
                <table class="table text-center">
                    <thead class="text-uppercase bg-primary">
                        <tr class="text-white">
                            <th scope="col">IP Address</th>
                            <th scope="col">Name</th>
                            <th scope="col">Hours</th>
                            <th scope="col">Purpose</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($request as $req)
                    <tr>
                        <td scope="row">{{$req->requestor_ip}}</td>
                        <td>{{$req->requestor_name}}</td>
                        <td>{{$req->hours}}</td>
                        <td>{{$req->requestor_purpose}}</td>
                        <td>{{$req->status}}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="5">{!! $request->links(); !!}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection