@extends('rfi.design')
@section('content')

    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">Dashboard</h4>
                    <ul class="breadcrumbs pull-left">
                    <li><a href="{{route('requestDash')}}">Home</a></li>
                        <li><span>Request for Internet</span></li>
                    </ul>
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

    <div class="main-content-inner">
        @include('messages')
        <div class="row">
            <div class="col-lg-6 mt-5">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title">Status: {{$requests->status}}</h4>
                        @if($requests->status == 'pending')
                        <p class="text-info">Your Request has been sent. <br />Please dont close this page until your request is approved.</p>
                        @endif
                        @if($requests->status == 'approve')
                        <p class="text-success">Your request has been approved. To start browsing, follow the instruction below.</p>
                        <br />
                        <ol>
                            <li>
                                On your Desktop, double click the "Internet Access icon". In case there is no response after double clicking, try to restart your PC <br />
                                <code style="font-size:25pt;"><img src="{{asset('images/rfi/approve/access.png')}}"></code>
                            </li>
                            <li>
                                Enter <span style="">{{@$requests->accesses->username}}</span> as your User Name and Password then click connect<br />
                                <code style="font-size:25pt;"><img src="{{asset('images/rfi/approve/login.png')}}"></code>                                
                            </li>
                        </ol>
                        @endif
                        @if($requests->status == 'decline')
                        <p class="text-danger">Your request has been decline.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection