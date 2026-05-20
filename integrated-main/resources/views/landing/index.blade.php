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
    <div class="page-container sbar_collapsed">
        <!-- main content area start -->
        <div class="main-content">
            <br /><br /><br />
            <div class="page-title-area">
                <div class="row align-items-center">
                    <div class="col-sm-12 clearfix">
                        <div class="user-profile">
                            <h4 class="user-name">Welcome to CSCI Integrated System</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 col-md-6 mt-5">
                    <div class="card card-bordered">
                        <img class="card-img-top img-fluid" src="{{asset('images/landing/rfi.jpg')}}" alt="image">
                        <div class="card-body">
                            <h5 class="title">Request for Internet Access</h5>
                            <p class="card-text">
                            </p>
                            <a href="{{route('requestDash')}}" class="btn btn-primary">Fill up Request form</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mt-5" style="display:none;">
                    <div class="card card-bordered">
                        <img class="card-img-top img-fluid" src="{{asset('images/landing/procurement.png')}}" alt="image">
                        <div class="card-body">
                            <h5 class="title">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Harum, dicta.</h5>
                            <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Mollitia adipisci quidem, quam nam reiciendis facere blanditiis atque neque architecto omnis magni totam, voluptate maiores, iusto molestias incidunt unde nesciunt cum.
                            </p>
                            <a href="#" class="btn btn-primary">Go More....</a>
                        </div>
                    </div>
                </div>
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
    