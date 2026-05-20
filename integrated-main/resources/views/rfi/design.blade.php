<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    @if(Request::route()->getName() == 'requestForApproval')
    <meta http-equiv="refresh" content="40">
    @endif
    <title>Request for Internet | CSCI Integrated System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="{{asset('images/rfi/icon/favicon.ico')}}">
    <link rel="stylesheet" href="{{asset('css/rfi/bootstrap.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/rfi/font-awesome.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/rfi/themify-icons.css')}}">
    <link rel="stylesheet" href="{{asset('css/rfi/metisMenu.css')}}">
    <link rel="stylesheet" href="{{asset('css/rfi/owl.carousel.min.css')}}">
    <link rel="stylesheet" href="{{asset('css/rfi/slicknav.min.css')}}">
    <!-- amchart css -->
    <link rel="stylesheet" href="{{asset('css/rfi/export.css')}}" type="text/css" media="all" />
    <!-- others css -->
    <link rel="stylesheet" href="{{asset('css/rfi/typography.css')}}">
    <link rel="stylesheet" href="{{asset('css/rfi/default-css.css')}}">
    <link rel="stylesheet" href="{{asset('css/rfi/styles.css')}}">
    <link rel="stylesheet" href="{{asset('css/rfi/responsive.css')}}">
    <!-- modernizr css -->
    <script src="{{asset('js/rfi/vendor/modernizr-2.8.3.min.js')}}"></script>
</head>

<body>
    <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
    <!-- preloader area start -->
    <div id="preloader">
        <div class="loader"></div>
    </div>
    <!-- preloader area end -->
    <!-- page container area start -->
    <div class="page-container">
        <!-- sidebar menu area start -->
        <div class="sidebar-menu">
            <div class="sidebar-header">
                <div class="logo">
                    <a href="{{route('requestDash')}}"><img src="{{asset('images/rfi/icon/logo.png')}}" alt="logo"></a>
                </div>
            </div>
            <div class="main-menu">
                <div class="menu-inner">
                    <nav>
                        <ul class="metismenu" id="menu">
                            <li><a href="{{route('requestDash')}}"><i class="ti-map-alt"></i> <span>Internet</span></a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <!-- sidebar menu area end -->
        <!-- main content area start -->
        <div class="main-content">
            <div class="header-area">
                <div class="row align-items-center">
                    <!-- nav and search button -->
                    <div class="col-md-6 col-sm-8 clearfix">
                        <div class="nav-btn pull-left">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
            @yield('content')
        </div>
         <!-- main content area end -->
         <!-- footer area start-->
        <footer>
                <div class="footer-area">
                    <p>Powered by MIS</p>
                </div>
            </footer>
            <!-- footer area end-->
        </div>
        <!-- page container area end -->
        <!-- jquery latest version -->
        <script src="{{asset('js/rfi/vendor/jquery-2.2.4.min.js')}}"></script>
        <!-- bootstrap 4 js -->
        <script src="{{asset('js/rfi/popper.min.js')}}"></script>
        <script src="{{asset('js/rfi/bootstrap.min.js')}}"></script>
        <script src="{{asset('js/rfi/owl.carousel.min.js')}}"></script>
        <script src="{{asset('js/rfi/metisMenu.min.js')}}"></script>
        <script src="{{asset('js/rfi/jquery.slimscroll.min.js')}}"></script>
        <script src="{{asset('js/rfi/jquery.slicknav.min.js')}}"></script>
    
        <!-- start chart js -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.min.js"></script>
        <!-- start highcharts js -->
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <!-- all line chart activation -->
        
        <script src="{{asset('js/rfi/plugins.js')}}"></script>
        <script src="{{asset('js/rfi/scripts.js')}}"></script>
    </body>
    
    </html>
    