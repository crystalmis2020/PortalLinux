<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">

        <title>Mikrotik Administration</title>

        <!-- Bootstrap Core CSS -->
        <link rel="stylesheet" href="{{asset('css/bootstrap.min.css')}}">

        <!-- MetisMenu CSS -->
        <link rel="stylesheet" href="{{asset('css/metisMenu.min.css')}}">

        @if(Request::route()->getName() == ('usersView' || 'profilesView' || 'limitationsView'))
        <link href="{{asset('css/dataTables/dataTables.bootstrap.css')}}" rel="stylesheet">
        <!-- DataTables Responsive CSS -->
        <link href="../css/dataTables/dataTables.responsive.css" rel="stylesheet">
        @endif


        <!-- Timeline CSS -->
        <link rel="stylesheet" href="{{asset('css/timeline.css')}}">

        <!-- Morris Charts CSS -->
        <link rel="stylesheet" href="{{asset('css/morris.css')}}">

        <!-- Custom CSS -->
        <link rel="stylesheet" href="{{asset('css/startmin.css')}}">

        <!-- Custom Fonts -->
        <link href="{{asset('css/font-awesome.min.css')}}" rel="stylesheet" type="text/css">

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>

        <div id="wrapper">

            <!-- Navigation -->
            <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
                <div class="navbar-header">
                    <a class="navbar-brand" href="index.html">Startmin</a>
                </div>

                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <ul class="nav navbar-nav navbar-left navbar-top-links">
                    <li><a href="#"><i class="fa fa-home fa-fw"></i> Website</a></li>
                </ul>

                

                <div class="navbar-default sidebar" role="navigation">
                    <div class="sidebar-nav navbar-collapse">
                        <ul class="nav" id="side-menu">
                            <li class="sidebar-search">
                                <div class="input-group custom-search-form">
                                    <input type="text" class="form-control" placeholder="Search...">
                                    <span class="input-group-btn">
                                        <button class="btn btn-primary" type="button">
                                            <i class="fa fa-search"></i>
                                        </button>
                                </span>
                                </div>
                                <!-- /input-group -->
                            </li>
                            <li>
                                <a href="{{route('dashboard')}}" class="active"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
                            </li>
                            <li>
                                <a href="#"><i class="fa fa-bar-chart-o fa-fw"></i> Users<span class="fa arrow"></span></a>
                                <ul class="nav nav-second-level">
                                    <li>
                                        <a href="{{ route('usersView') }}">View</a>
                                    </li>
                                    <li>
                                        <a href="{{route('userCreate')}}">Add</a>
                                    </li>
                                </ul>
                                <!-- /.nav-second-level -->
                            </li>
                            <li>
                                <a href="#"><i class="fa fa-sitemap fa-fw"></i> Profile<span class="fa arrow"></span></a>
                                <ul class="nav nav-second-level">
                                    <li>
                                        <a href="{{route('profilesView')}}">View</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('profileCreate') }}">Add</a>
                                    </li>
                                    <li>
                                        <a href="#">Limitations<span class="fa arrow"></span></a>
                                        <ul class="nav nav-third-level">
                                            <li>
                                                <a href="{{ route('limitationsView') }}">View</a>
                                            </li>
                                            <li>
                                                <a href="{{ route('limitationCreate') }}">Add</a>
                                            </li>
                                        </ul>
                                        <!-- /.nav-third-level -->
                                    </li>
                                </ul>
                                <!-- /.nav-second-level -->
                            </li>
                            <li>
                                <a href="#"><i class="fa fa-bar-chart-o fa-fw"></i> PPPoE<span class="fa arrow"></span></a>
                                <ul class="nav nav-second-level">
                                    <li>
                                        <a href="flot.html">View</a>
                                    </li>
                                    <li>
                                        <a href="morris.html">Add</a>
                                    </li>
                                </ul>
                                <!-- /.nav-second-level -->
                            </li>
                            <li>
                                <a href="tables.html"><i class="fa fa-table fa-fw"></i>Use session</a>
                            </li>
                            <li>
                                <a href="tables.html"><i class="fa fa-table fa-fw"></i> Log</a>
                            </li>
                            <li>
                                <a href="tables.html"><i class="fa fa-table fa-fw"></i> Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div id="page-wrapper">
                @include('messages')
                @yield('content')
            </div>
            <!-- /#page-wrapper -->

        </div>
        <!-- /#wrapper -->

        <!-- jQuery -->
        <script src="{{asset('js/jquery.min.js')}}"></script>

        <!-- Bootstrap Core JavaScript -->
        <script src="{{asset('js/bootstrap.min.js')}}"></script>

        <!-- Metis Menu Plugin JavaScript -->
        <script src="{{asset('js/metisMenu.min.js')}}"></script>

        @if(Request::route()->getName() == ('usersView' || 'profilesView' || 'limitationsView'))
            <!-- DataTables JavaScript -->
        <script src="{{asset('js/dataTables/jquery.dataTables.min.js')}}"></script>
        <script src="{{asset('js/dataTables/dataTables.bootstrap.min.js')}}"></script>
        <script>
            $(document).ready(function() {
                $('#dataTables-example').DataTable({
                        responsive: true
                });
            });
        </script>
        @endif

        @if(Request::route()->getName() == 'dashboard')
            <!-- Morris Charts JavaScript -->
        <script src="{{asset('js/raphael.min.js')}}"></script>
        <script src="{{asset('js/morris.min.js')}}"></script>
        <script src="{{asset('js/morris-data.js')}}"></script>
        @endif

        <!-- Custom Theme JavaScript -->
        <script src="{{asset('js/startmin.js')}}"></script>

    </body>
</html>
