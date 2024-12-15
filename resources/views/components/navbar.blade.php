@php
    $onlineUsers = \App\Models\User::where('id', '!=', Auth::id())
        ->orderBy('last_seen', 'desc')
        ->get();
@endphp

<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="bx bx-menu bx-md"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <ul class="navbar-nav flex-row align-items-center ms-auto">
            {{-- USER ONLINE --}}
            <li class="nav-item navbar-dropdown dropdown-user dropdown me-5">
                <a class="nav-link dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                    <div class="avatar position-relative">
                        <i class='bx bx-signal-5' style='font-size: 40px;'></i>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" style="max-height: 300px; overflow-y: auto;">
                    <li>
                        <h6 class="dropdown-header">Users Online</h6>
                    </li>
                    @foreach ($onlineUsers as $user)
                        @if ($user->isOnline != null && $user->last_seen != null)
                            <li>
                                <a class="dropdown-item" href="#">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">{{ $user->username }}</h6>
                                            <small class="text-muted">
                                                @if ($user->userOnline())
                                                    Online
                                                @else
                                                    {{ \Carbon\Carbon::parse($user->last_seen)->diffForHumans() }}
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>
            {{-- USER ONLINE --}}

            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <div>
                            <i class='bx bxs-user-circle' style='font-size: 40px;'></i>
                        </div>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <i class='bx bxs-user-circle' style='font-size: 40px;'></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                                    <small class="text-muted">{{ Auth::user()->role }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('logout') }}">
                            <i class="bx bx-power-off bx-md me-3"></i><span>Log Out</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>
