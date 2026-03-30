<x-guest-layout>
    <div class="text-center mb-4">
        <h1 class="h4 mb-2">Two-Step Verification</h1>
        <p class="text-muted mb-0">
            @if ($otpSent)
                We sent a 6-digit code to {{ $maskedEmail }}
            @else
                Click below to send your verification code.
            @endif
        </p>
    </div>

    <form method="POST" action="{{ route('two-factor.verify') }}" class="row g-3">
        @csrf
        <div class="col-12">
            <label for="code" class="form-label">Verification code</label>
            <input
                id="code"
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                name="code"
                class="form-control form-control-lg text-center tracking-otp @error('code') is-invalid @enderror"
                placeholder="000000"
                autocomplete="one-time-code"
                aria-label="6 digit verification code"
                required
                autofocus
            >
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12 d-grid">
            <button type="submit" class="btn btn-primary btn-lg">Verify and continue</button>
        </div>
    </form>

    <form method="POST" action="{{ route('two-factor.resend') }}" class="mt-3 d-grid" id="resendForm">
        @csrf
        <button type="submit" id="resendButton" class="btn btn-outline-secondary" @disabled($otpSent && $remainingCooldown > 0)>
            {{ $otpSent ? 'Resend code' : 'Send code' }}
        </button>
        <small class="text-muted text-center mt-2" id="resendHint">
            @if ($otpSent && $remainingCooldown > 0)
                Resend available in <span id="countdown">{{ (int) ceil($remainingCooldown) }}</span>s
            @elseif ($otpSent)
                You can request a new code every 60 seconds (max 5 per hour)
            @else
                We will send your 6-digit code to {{ $maskedEmail }}.
            @endif
        </small>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const resendButton = document.getElementById('resendButton');
            const countdown = document.getElementById('countdown');
            const resendHint = document.getElementById('resendHint');
            const initialSeconds = Number.parseFloat(countdown?.textContent || '0');
            let seconds = Number.isFinite(initialSeconds) ? Math.max(0, Math.ceil(initialSeconds)) : 0;

            if (!countdown || !resendButton || seconds <= 0) {
                return;
            }

            countdown.textContent = String(seconds);

            const timer = setInterval(() => {
                seconds = Math.max(0, seconds - 1);
                countdown.textContent = String(seconds);

                if (seconds <= 0) {
                    clearInterval(timer);
                    resendButton.disabled = false;
                    resendHint.textContent = 'You can now resend a new code.';
                }
            }, 1000);
        });
    </script>
</x-guest-layout>
