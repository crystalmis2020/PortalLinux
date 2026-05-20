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
                            <th scope="col">Assigned to</th>
                            <!--<th scope="col">IP Address</th>
                            <th scope="col">Uptime Used</th>
                            <th scope="col">Last Seen</th>
                            <th scope="col">DL/UL Used</th> -->
                            <th scope="col"></th> 
                        </tr>
                    </thead>
                    <tbody>
                    @for($i=0; $i<count($accesses);$i++)
                        @if(@$accesses[$i]['profile'] != 'Support')
                            <tr>
                                <td scope="row">{{$accesses[$i]['name']}}</td>
                                <td>{{@$accesses[$i]['comment']}}</td>
                                <!--<td>{{@$accesses[$i]['name']}}</td>
                                <td>{{@$accesses[$i]['profile']}}</td>
                                <td>{{@$accesses[$i]['profile']}}</td>
                                <td>{{@$accesses[$i]['profile']}} / {{@$accesses[$i]['profile']}}</td> -->
                                <td><a href="{{route('requestAdminAccessDestroy', $accesses[$i]['name'])}}" class="btn btn-primary btn-flat btn-lg mt-3 ">Marck as Used</a></td>
                            </tr>
                        @endif
                    @endfor
                    <tr>
                        <!--<td colspan="5"></td> -->
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection