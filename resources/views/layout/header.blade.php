<header>
    <style>
        .messenger-presence-toggle {
            min-height: 34px !important;
            height: 34px;
            border-radius: 999px !important;
            font-size: 0.78rem;
            font-weight: 800;
            border: 1px solid rgba(0, 73, 30, 0.28) !important;
            box-shadow: 0 8px 18px rgba(0, 73, 30, 0.14);
        }

        .messenger-presence-toggle,
        .messenger-presence-toggle:hover,
        .messenger-presence-toggle:focus {
            color: #00491e !important;
            background: #ffffff !important;
        }

        .messenger-presence-toggle.is-online {
            color: #064d27 !important;
            background: #e6f7ed !important;
            border-color: #198754 !important;
        }

        .messenger-presence-toggle.is-offline {
            color: #343a40 !important;
            background: #f5f6f7 !important;
            border-color: #6c757d !important;
        }

        html.dark-theme .messenger-presence-toggle.is-online {
            color: #d8ffe6 !important;
            background: rgba(25, 135, 84, 0.28) !important;
            border-color: rgba(117, 255, 174, 0.72) !important;
        }

        html.dark-theme .messenger-presence-toggle.is-offline {
            color: #f1f5f9 !important;
            background: rgba(100, 116, 139, 0.32) !important;
            border-color: rgba(203, 213, 225, 0.52) !important;
        }

        .messenger-presence-toggle i,
        .messenger-presence-toggle span {
            color: inherit !important;
        }
    </style>
    <div class="topbar d-flex align-items-center">
        <nav class="navbar navbar-expand gap-3">
            <div class="mobile-toggle-menu"><i class='bx bx-menu'></i>
            </div>

              {{-- <div class="position-relative search-bar d-lg-block d-none" data-bs-toggle="modal" data-bs-target="#SearchModal">
                <input class="form-control px-5" disabled type="search" placeholder="Search">
                <span class="position-absolute top-50 search-show ms-3 translate-middle-y start-0 top-50 fs-5"><i class='bx bx-search'></i></span>
              </div> --}}
              <div class="position-relative search-bar d-lg-block d-none"style="width:100%">
                <span class="position-absolute top-50 search-show ms-3 translate-middle-y start-0 top-50 fs-5">Welcome! {{ Auth::user()->full_name }}</span>
              </div>


              <div class="top-menu">
                <ul class="navbar-nav align-items-center gap-1">
                    <li class="nav-item mobile-search-icon d-flex d-lg-none" data-bs-toggle="modal" data-bs-target="#SearchModal">
                        <a class="nav-link" href="avascript:;"><i class='bx bx-search'></i>
                        </a>
                    </li>
                    <li class="nav-item dark-mode d-none d-sm-flex">
                        <a class="nav-link dark-mode-icon" href="javascript:;"><i class='bx bx-sun'></i>
                        </a>
                    </li>
                    @if(Auth::user()->isAdmin())
                    @php
                        $messengerPresenceVisible = Auth::user()->messenger_presence_visible !== false;
                    @endphp
                    <li class="nav-item d-flex">
                        <button
                            type="button"
                            class="btn btn-sm messenger-presence-toggle {{ $messengerPresenceVisible ? 'is-online' : 'is-offline' }} d-flex align-items-center gap-1 px-2"
                            id="messengerPresenceToggle"
                            data-url="{{ route('messenger.presence.update') }}"
                            data-status="{{ $messengerPresenceVisible ? 'online' : 'offline' }}"
                        >
                            <i class="bx {{ $messengerPresenceVisible ? 'bx-radio-circle-marked' : 'bx-radio-circle' }}" id="messengerPresenceToggleIcon"></i>
                            <span id="messengerPresenceToggleLabel">{{ $messengerPresenceVisible ? 'Online' : 'Offline' }}</span>
                        </button>
                    </li>
                    @endif
                    @php
                        $notifications = getUserNotifications();
                    @endphp
                    <li class="nav-item dropdown dropdown-large">
                        <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="#" data-bs-toggle="dropdown"><span class="alert-count" id="portalNotificationCount">{{ $notifications->where('is_read', 'No')->count() }}</span>
                            <i class='bx bx-bell'></i>
                        </a>

                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:;">
                                <div class="msg-header">
                                    <p class="msg-header-title">Notifications</p>
                                    <p class="msg-header-badge" id="portalNotificationSummary">{{ $notifications->count() }} New</p>
                                </div>
                            </a>
                            <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2">
                                <input class="form-check-input m-0" type="checkbox" id="portalNotificationSelectAll" aria-label="Select all notifications">
                                <label class="form-check-label small flex-grow-1" for="portalNotificationSelectAll">Select all</label>
                                <button type="button" class="btn btn-sm btn-light" id="portalNotificationMarkRead" disabled>Mark as read</button>
                                <button type="button" class="btn btn-sm btn-light" id="portalNotificationMarkUnread" disabled>Mark as unread</button>
                            </div>
                            <div class="header-notifications-list" id="portalNotificationList">
                                @foreach($notifications as $notification)
                                <div class="dropdown-item" data-notification-id="{{ $notification->id }}" data-notification-read="{{ $notification->is_read }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <input class="form-check-input portal-notification-checkbox" type="checkbox" value="{{ $notification->id }}" aria-label="Select notification {{ $notification->id }}">
                                        <a class="d-flex align-items-center flex-grow-1 text-decoration-none text-reset" href="{{ route('reports.details', [$notification->report_id, $notification->id]) }}">
                                            <div class="user-online">
                                                <img src="{{ $notification->fromUser?->profile_photo_url ?? asset('assets/images/avatars/avatar-1.png') }}" class="msg-avatar" alt="user avatar">
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="msg-name">
                                                    {{ $notification->title }}
                                                    <span class="msg-time float-end">{{ $notification->created_at->diffForHumans() }}</span>
                                                </h6>
                                                <p class="msg-info">
                                                    {{ $notification->message }}
                                                    @if($notification->from_user_id)
                                                        by {{ $notification->fromUser->full_name }}
                                                    @endif
                                                    @if($notification->report_id)
                                                        <br>Report ID: {{ $notification->report_id }}
                                                    @endif
                                                </p>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                @endforeach

                            </div>
                            {{-- <a href="javascript:;">
                                <div class="text-center msg-footer">
                                    <button class="btn btn-primary w-100">View All Notifications</button>
                                </div>
                            </a> --}}
                        </div>
                    </li>
                </ul>
            </div>
            <div class="user-box dropdown px-3">
                <a class="d-flex align-items-center nav-link dropdown-toggle gap-3 dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ Auth::user()->profile_photo_url }}" class="user-img" alt="user avatar">
                    <div class="user-info">
                        <p class="user-name mb-0">{{ Auth::user()->full_name }}</p>
                        <p class="designattion mb-0">{{ Auth::user()->department->name }}</p>
                    </div>
                </a>
                <form id="profile-photo-form" action="{{ route('profile.photo.update') }}" method="POST" enctype="multipart/form-data" style="display: none;">
                    @csrf
                    <input type="file" id="profile-photo-input" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,image/*" onchange="if (this.files.length) { document.getElementById('profile-photo-form').submit(); }">
                </form>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item d-flex align-items-center" href="{{ route('profile.show') }}"><i class="bx bx-user fs-5"></i><span>View Profile</span></a>
                    <li><a class="dropdown-item d-flex align-items-center" href="javascript:;" onclick="document.getElementById('profile-photo-input').click(); return false;"><i class="bx bx-image-add fs-5"></i><span>Change Picture</span></a>
                    <li>
                        <div class="dropdown-divider mb-0"></div>
                    </li>
                    <li><a class="dropdown-item d-flex align-items-center" href="javascript:;" onclick="document.getElementById('logout-form').submit();"><i class="bx bx-log-out-circle"></i><span>Logout</span></a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</header>

@if(Auth::user()->isAdmin())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const button = document.getElementById('messengerPresenceToggle');
        const label = document.getElementById('messengerPresenceToggleLabel');
        const icon = document.getElementById('messengerPresenceToggleIcon');

        if (!button || !label || !icon) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        button.addEventListener('click', async function () {
            const currentStatus = button.dataset.status === 'online' ? 'online' : 'offline';
            const nextStatus = currentStatus === 'online' ? 'offline' : 'online';

            button.disabled = true;

            try {
                const response = await fetch(button.dataset.url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ status: nextStatus }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data?.message || 'Unable to update MISsenger status.');
                }

                button.dataset.status = data.status;
                label.textContent = data.status === 'online' ? 'Online' : 'Offline';
                icon.className = data.status === 'online' ? 'bx bx-radio-circle-marked' : 'bx bx-radio-circle';
                button.classList.toggle('is-online', data.status === 'online');
                button.classList.toggle('is-offline', data.status !== 'online');

                if (window.portalMessenger?.refresh) {
                    window.portalMessenger.refresh().catch(function () {});
                }

                if (window.Lobibox) {
                    Lobibox.notify('success', {
                        size: 'mini',
                        rounded: true,
                        sound: false,
                        delay: 2500,
                        position: 'top right',
                        msg: data.message || 'MISsenger status updated.',
                    });
                }
            } catch (error) {
                if (window.Lobibox) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        sound: false,
                        delay: 3500,
                        position: 'top right',
                        msg: error?.message || 'Unable to update MISsenger status.',
                    });
                }
            } finally {
                button.disabled = false;
            }
        });
    });
</script>
@endif
