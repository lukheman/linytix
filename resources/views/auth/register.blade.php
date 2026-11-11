<x-layouts.guest title="Register - LinyTix" type="auth">
    <div class="login-container">
        <div class="login-card">
            <!-- Brand Logo -->
            <div class="brand-logo">
                <div class="icon-wrapper">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h1>Create Account</h1>
                <p>Sign up to get started with LinyTix</p>
            </div>

            <!-- Register Form -->
            <form method="POST" action="{{ route('register.store') }}" id="register-form">
                @csrf

                <!-- Name Field -->
                <div class="form-floating position-relative">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="form-control @error('name') is-invalid @enderror" id="name"
                        placeholder="Full Name" autocomplete="name" required autofocus>
                    <label for="name">Full Name</label>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Email Field -->
                <div class="form-floating position-relative">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror" id="email"
                        placeholder="Email Address" autocomplete="email" required>
                    <label for="email">Email Address</label>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="form-floating position-relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password"
                        class="form-control @error('password') is-invalid @enderror" id="password"
                        placeholder="Password" autocomplete="new-password" required>
                    <label for="password">Password</label>
                    <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                        <i class="fas fa-eye" id="toggleIcon1"></i>
                    </button>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Confirm Password Field -->
                <div class="form-floating position-relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password_confirmation" class="form-control"
                        id="password_confirmation" placeholder="Confirm Password"
                        autocomplete="new-password" required>
                    <label for="password_confirmation">Confirm Password</label>
                    <button type="button" class="password-toggle"
                        onclick="togglePassword('password_confirmation', 'toggleIcon2')">
                        <i class="fas fa-eye" id="toggleIcon2"></i>
                    </button>
                </div>

                <!-- Terms and Conditions -->
                <div class="form-check mb-4">
                    <input class="form-check-input @error('agree_terms') is-invalid @enderror" type="checkbox"
                        name="agree_terms" value="1" id="agree_terms" @checked(old('agree_terms')) required>
                    <label class="form-check-label" for="agree_terms">
                        I agree to the <a href="{{ route('info.terms') }}" class="forgot-password">Terms of Service</a> and <a href="{{ route('info.privacy') }}"
                            class="forgot-password">Privacy Policy</a>
                    </label>
                    @error('agree_terms')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Register Button -->
                <button type="submit" class="btn btn-login" id="register-button">
                    <span id="register-text">Create Account <i class="fas fa-arrow-right"></i></span>
                    <span id="register-loading" class="d-none">
                        <i class="fas fa-spinner fa-spin me-2"></i> Creating account...
                    </span>
                </button>
            </form>

            <!-- Divider -->
            <div class="divider">
                <span>or continue with</span>
            </div>

            <!-- Social Login -->
            <div class="social-login">
                <a href="{{ route('auth.google.redirect') }}" class="btn-social google">
                    <i class="fab fa-google"></i>
                    <span>Google</span>
                </a>
                <a href="#" class="btn-social github">
                    <i class="fab fa-github"></i>
                    <span>GitHub</span>
                </a>
            </div>

            <!-- Sign In Link -->
            <div class="signup-link">
                Already have an account? <a href="{{ route('login') }}">Sign In</a>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function togglePassword(inputId, iconId) {
                const input = document.getElementById(inputId);
                const icon = document.getElementById(iconId);

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            }

            document.getElementById('register-form').addEventListener('submit', function () {
                document.getElementById('register-button').disabled = true;
                document.getElementById('register-text').classList.add('d-none');
                document.getElementById('register-loading').classList.remove('d-none');
            });
        </script>
    @endpush
</x-layouts.guest>
