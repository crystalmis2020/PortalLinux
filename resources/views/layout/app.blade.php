<!doctype html>
<html lang="en">

<head>
	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
	<!--favicon-->
	<link rel="icon" href="{{ asset('assets/images/favicon-32x32.png') }}" type="image/png"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function () {
            const storageKey = 'support-portal-theme';
            const allowedThemes = ['light-theme', 'dark-theme', 'semi-dark', 'minimal-theme'];
            const themeManager = {
                getStoredTheme() {
                    const savedTheme = window.localStorage.getItem(storageKey);
                    return allowedThemes.includes(savedTheme) ? savedTheme : 'light-theme';
                },
                applyThemeClass(target, themeName) {
                    if (!target) {
                        return;
                    }

                    target.classList.remove(...allowedThemes);
                    target.classList.add(themeName);
                },
                applyTheme(themeName, persist = true) {
                    const nextTheme = allowedThemes.includes(themeName) ? themeName : 'light-theme';

                    this.applyThemeClass(document.documentElement, nextTheme);
                    this.applyThemeClass(document.body, nextTheme);

                    if (persist) {
                        window.localStorage.setItem(storageKey, nextTheme);
                    }

                    return nextTheme;
                },
            };

            window.supportPortalTheme = themeManager;
            themeManager.applyTheme(themeManager.getStoredTheme(), false);

            document.addEventListener('DOMContentLoaded', function () {
                themeManager.applyTheme(themeManager.getStoredTheme(), false);
            });
        })();
    </script>
	<!--plugins-->
	<link href="{{ asset('assets/plugins/vectormap/jquery-jvectormap-2.0.2.css') }}" rel="stylesheet"/>
	<link href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
	<link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
	<link href="{{ asset('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet"/>
	<!-- loader-->
	<link href="{{ asset('assets/css/pace.min.css') }}" rel="stylesheet"/>
	<script src="{{ asset('assets/js/pace.min.js') }}"></script>
	<!-- Bootstrap CSS -->
	<link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
	<link href="{{ asset('assets/css/bootstrap-extended.css') }}" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
	<link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
	<!-- Theme Style CSS -->
	<link rel="stylesheet" href="{{ asset('assets/css/dark-theme.css') }}"/>
	<link rel="stylesheet" href="{{ asset('assets/css/semi-dark.css') }}"/>
	<link rel="stylesheet" href="{{ asset('assets/css/header-colors.css') }}"/>
	<link rel="stylesheet" href="{{ asset('assets/css/theme-csci.css') }}"/>
	<link rel="stylesheet" href="{{ asset('assets/css/dashboard-ui.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/plugins/notifications/css/lobibox.min.css')}}" />
    <script>
        window.portalRealtimeConfig = {
            appName: @json(config('app.name')),
            userId: @json(auth()->user()?->getKey()),
            dashboardUrl: @json(route('dashboard.index')),
            portalBasePath: @json(request()->getBaseUrl()),
            reverbKey: @json(config('broadcasting.connections.reverb.key')),
            reverbHost: @json(request()->getHost()),
            reverbPort: @json(request()->isSecure() ? 443 : (int) env('REVERB_PORT', 8080)),
            reverbScheme: @json(request()->isSecure() ? 'https' : env('REVERB_SCHEME', 'http')),
            broadcastAuthEndpoint: @json(url('/broadcasting/auth')),
        };
    </script>
    @vite('resources/js/app.js')
	<title>Support Portal - CSCI</title>
    <style>
        html.dark-theme {
            color-scheme: dark;
        }
    </style>
    @yield('css-custom')
</head>

<body>
	<!--wrapper-->
	<div class="wrapper">
		<!--sidebar wrapper -->
		@include('layout.sidebar')
		<!--end sidebar wrapper -->
		<!--start header -->
		@include('layout.header')
		<!--end header -->
		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
                @yield('content')
			</div>
		</div>
		<!--end page wrapper -->

		<!--Start Back To Top Button-->
		  <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
		<!--End Back To Top Button-->
		<footer class="page-footer">
			<div class="footer-shell">
				<div class="footer-left">
					<p class="footer-title mb-0">CSCI MIS</p>
					<p class="footer-sub mb-0">Built with precision. Empowering smarter digital solutions.</p>
				</div>
				<div class="footer-right">Support Portal | 2025</div>
			</div>
		</footer>
	</div>
	<!--end wrapper-->

    @include('layout.portal-messenger')

    @if(session('show_profile_photo_modal'))
    <div class="modal fade" id="profilePhotoReminderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Your Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">You have not uploaded a profile picture yet.</p>
                    <p class="mb-2"><strong>How to update:</strong></p>
                    <ol class="mb-0 ps-3">
                        <li>Click your avatar at the top-right corner.</li>
                        <li>Select <strong>Change Picture</strong>.</li>
                        <li>Choose a clear photo and upload.</li>
                    </ol>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('profile.show') }}" class="btn btn-primary">Open Profile</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Later</button>
                </div>
            </div>
        </div>
    </div>
    @endif


	<!-- search modal -->
    <div class="modal" id="SearchModal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
		  <div class="modal-content">
			<div class="modal-header gap-2">
			  <div class="position-relative popup-search w-100">
				<input class="form-control form-control-lg ps-5 border border-3 border-primary" type="search" placeholder="Search">
				<span class="position-absolute top-50 search-show ms-3 translate-middle-y start-0 top-50 fs-4"><i class='bx bx-search'></i></span>
			  </div>
			  <button type="button" class="btn-close d-md-none" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="search-list">
				   <p class="mb-1">Html Templates</p>
				   <div class="list-group">
					  <a href="javascript:;" class="list-group-item list-group-item-action active align-items-center d-flex gap-2 py-1"><i class='bx bxl-angular fs-4'></i>Best Html Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-vuejs fs-4'></i>Html5 Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-magento fs-4'></i>Responsive Html5 Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-shopify fs-4'></i>eCommerce Html Templates</a>
				   </div>
				   <p class="mb-1 mt-3">Web Designe Company</p>
				   <div class="list-group">
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-windows fs-4'></i>Best Html Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-dropbox fs-4' ></i>Html5 Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-opera fs-4'></i>Responsive Html5 Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-wordpress fs-4'></i>eCommerce Html Templates</a>
				   </div>
				   <p class="mb-1 mt-3">Software Development</p>
				   <div class="list-group">
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-mailchimp fs-4'></i>Best Html Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-zoom fs-4'></i>Html5 Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-sass fs-4'></i>Responsive Html5 Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-vk fs-4'></i>eCommerce Html Templates</a>
				   </div>
				   <p class="mb-1 mt-3">Online Shoping Portals</p>
				   <div class="list-group">
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-slack fs-4'></i>Best Html Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-skype fs-4'></i>Html5 Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-twitter fs-4'></i>Responsive Html5 Templates</a>
					  <a href="javascript:;" class="list-group-item list-group-item-action align-items-center d-flex gap-2 py-1"><i class='bx bxl-vimeo fs-4'></i>eCommerce Html Templates</a>
				   </div>
				</div>
			</div>
		  </div>
		</div>
	  </div>
    <!-- end search modal -->




	<!--start switcher-->
	<div class="switcher-wrapper">
		<div class="switcher-btn"> <i class='bx bx-cog bx-spin'></i>
		</div>
		<div class="switcher-body">
			<div class="d-flex align-items-center">
				<h5 class="mb-0 text-uppercase">Theme Customizer</h5>
				<button type="button" class="btn-close ms-auto close-switcher" aria-label="Close"></button>
			</div>
			<hr/>
			<h6 class="mb-0">Theme Styles</h6>
			<hr/>
			<div class="d-flex align-items-center justify-content-between">
				<div class="form-check">
					<input class="form-check-input" type="radio" name="flexRadioDefault" id="lightmode" checked>
					<label class="form-check-label" for="lightmode">Light</label>
				</div>
				<div class="form-check">
					<input class="form-check-input" type="radio" name="flexRadioDefault" id="darkmode">
					<label class="form-check-label" for="darkmode">Dark</label>
				</div>
				<div class="form-check">
					<input class="form-check-input" type="radio" name="flexRadioDefault" id="semidark">
					<label class="form-check-label" for="semidark">Semi Dark</label>
				</div>
			</div>
			<hr/>
			<div class="form-check">
				<input class="form-check-input" type="radio" id="minimaltheme" name="flexRadioDefault">
				<label class="form-check-label" for="minimaltheme">Minimal Theme</label>
			</div>
			<hr/>
			<h6 class="mb-0">Header Colors</h6>
			<hr/>
			<div class="header-colors-indigators">
				<div class="row row-cols-auto g-3">
					<div class="col">
						<div class="indigator headercolor1" id="headercolor1"></div>
					</div>
					<div class="col">
						<div class="indigator headercolor2" id="headercolor2"></div>
					</div>
					<div class="col">
						<div class="indigator headercolor3" id="headercolor3"></div>
					</div>
					<div class="col">
						<div class="indigator headercolor4" id="headercolor4"></div>
					</div>
					<div class="col">
						<div class="indigator headercolor5" id="headercolor5"></div>
					</div>
					<div class="col">
						<div class="indigator headercolor6" id="headercolor6"></div>
					</div>
					<div class="col">
						<div class="indigator headercolor7" id="headercolor7"></div>
					</div>
					<div class="col">
						<div class="indigator headercolor8" id="headercolor8"></div>
					</div>
				</div>
			</div>
			<hr/>
			<h6 class="mb-0">Sidebar Colors</h6>
			<hr/>
			<div class="header-colors-indigators">
				<div class="row row-cols-auto g-3">
					<div class="col">
						<div class="indigator sidebarcolor1" id="sidebarcolor1"></div>
					</div>
					<div class="col">
						<div class="indigator sidebarcolor2" id="sidebarcolor2"></div>
					</div>
					<div class="col">
						<div class="indigator sidebarcolor3" id="sidebarcolor3"></div>
					</div>
					<div class="col">
						<div class="indigator sidebarcolor4" id="sidebarcolor4"></div>
					</div>
					<div class="col">
						<div class="indigator sidebarcolor5" id="sidebarcolor5"></div>
					</div>
					<div class="col">
						<div class="indigator sidebarcolor6" id="sidebarcolor6"></div>
					</div>
					<div class="col">
						<div class="indigator sidebarcolor7" id="sidebarcolor7"></div>
					</div>
					<div class="col">
						<div class="indigator sidebarcolor8" id="sidebarcolor8"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!--end switcher-->
	<!-- Bootstrap JS -->
	<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
	<!--plugins-->
	<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
	<script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
	<script src="{{ asset('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
	<script src="{{ asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
	<!--app JS-->
	<script src="{{ asset('assets/js/app.js') }}"></script>
	    <script src="{{ asset('assets/plugins/notifications/js/lobibox.min.js') }}"></script>
		<script src="{{ asset('assets/plugins/notifications/js/notifications.min.js') }}"></script>
        <script>
            window.portalToastsDisabled = true;

            if (window.Lobibox) {
                window.Lobibox.notify = function () {};
            }
        </script>

	    <script>
	        $(document).ready(function () {
            @if(session('success'))
                Lobibox.notify('success', {
                    size: 'mini',
                    rounded: true,
                    delayIndicator: true,
                    sound: false,
                    position: 'top right',
                    icon: 'bx bx-check-circle',
                    msg: "{{ session('success') }}"
                });
            @endif

            @if(session('error'))
                Lobibox.notify('error', {
                    size: 'mini',
                    rounded: true,
                    delayIndicator: true,
                    sound: false,
                    position: 'top right',
                    icon: 'bx bx-x-circle',
                    msg: "{{ session('error') }}"
                });

            @endif

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: "{{ $error }}"
                    });
                @endforeach
            @endif

            @if(session('show_profile_photo_modal'))
                const profilePhotoReminderModalEl = document.getElementById('profilePhotoReminderModal');
                if (profilePhotoReminderModalEl) {
                    const profilePhotoReminderModal = new bootstrap.Modal(profilePhotoReminderModalEl);
                    profilePhotoReminderModal.show();
                }
            @endif

        });
    </script>
    @include('layout.realtime-notifications')
	@yield('js-custom')
</body>

</html>
