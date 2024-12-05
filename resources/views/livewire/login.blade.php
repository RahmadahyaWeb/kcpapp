<div>
    <div class="container">
        <div class="d-flex min-vh-100 d-flex justify-content-center align-items-center">
            <div class="card px-sm-6 px-0">
                <div class="card-header text-center">
                    <div class="app-brand justify-content-center">
                        <img src="{{ asset('img/logo-hd.png') }}" alt="logo-kcp" style="width: 200px; height: auto;">
                    </div>
                </div>
                <div class="card-body">
                    <span class="mb-1 fs-2 fw-bold">
                        KCP APP
                    </span>

                    <p class="mb-6 text-muted">Please login to your account and get started!</p>

                    <hr>

                    <form class="mb-6" wire:submit.prevent="login">
                        <div class="mb-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror"
                                id="username" wire:model="username" placeholder="Enter your username" autofocus
                                value="{{ old('username') }}" />

                            @error('username')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="mb-6 form-password-toggle">
                            <label class="form-label" for="password">Password</label>

                            <input type="password" id="password"
                                class="form-control @error('password') is-invalid @enderror" wire:model="password"
                                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                aria-describedby="password" />
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>
                        <div class="mb-6">
                            <button class="btn btn-primary d-grid w-100" type="submit" wire:offline.attr="disabled">
                                <span wire:loading.remove wire:target="login">Login</span>
                                <span wire:loading wire:target="login">Loading...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
