<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Masuk — {{ config('app.name') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#07070a;--bg2:#0e0e11;--bg3:#16161a;--bg4:#1e1e23;
  --bd:rgba(255,255,255,.055);--bd2:rgba(255,255,255,.09);--bd3:rgba(255,255,255,.15);
  --tx:#eeeae3;--tx2:#938f88;--tx3:#4e4c49;
  --go:#c9a44e;--go2:#e4bf6a;--go3:#f2d080;
  --gd:rgba(201,164,78,.10);--gd2:rgba(201,164,78,.18);--gd3:rgba(201,164,78,.30);
  --gn:#3ecf8e;--rd:#f87171;
  --fn:'DM Sans',sans-serif;--mo:'DM Mono',monospace;
}
html,body{height:100%;overflow:hidden}
body{font-family:var(--fn);background:var(--bg);color:var(--tx);-webkit-font-smoothing:antialiased;display:flex;align-items:center;justify-content:center}

/* BACKGROUND GRID */
body::before{
  content:'';position:fixed;inset:0;
  background-image:linear-gradient(rgba(201,164,78,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(201,164,78,.03) 1px,transparent 1px);
  background-size:48px 48px;pointer-events:none;z-index:0
}

/* GLOW */
.glow{position:fixed;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(201,164,78,.07) 0%,transparent 70%);top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;z-index:0}

/* CARD */
.card{position:relative;z-index:1;width:100%;max-width:400px;padding:0 16px}

/* LOGO */
.logo-wrap{text-align:center;margin-bottom:32px}
.logo-box{width:52px;height:52px;background:var(--go);border-radius:14px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;box-shadow:0 0 32px rgba(201,164,78,.25)}
.logo-box svg{width:28px;height:28px;color:#07070a}
.logo-name{font-size:22px;font-weight:600;letter-spacing:-.5px;color:var(--tx)}
.logo-sub{font-size:12px;color:var(--tx3);letter-spacing:.8px;text-transform:uppercase;margin-top:3px}

/* FORM CARD */
.form-card{background:var(--bg2);border:1px solid var(--bd2);border-radius:16px;padding:28px;box-shadow:0 24px 64px rgba(0,0,0,.6)}

/* ERROR */
.error-box{background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.2);border-radius:10px;padding:11px 14px;margin-bottom:20px;display:flex;align-items:flex-start;gap:9px}
.error-box svg{width:14px;height:14px;color:var(--rd);flex-shrink:0;margin-top:1px}
.error-box p{font-size:12.5px;color:var(--rd);line-height:1.5}

/* FORM GROUPS */
.fg{margin-bottom:18px}
.fg:last-of-type{margin-bottom:0}
.fl{font-size:11px;color:var(--tx3);text-transform:uppercase;letter-spacing:.7px;font-weight:500;display:block;margin-bottom:7px}

/* INPUT WRAPPER */
.inp-wrap{position:relative}
.inp-ico{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--tx3);pointer-events:none}
.inp-ico svg{width:15px;height:15px}
.fi2{width:100%;background:var(--bg3);border:1px solid var(--bd2);color:var(--tx);border-radius:10px;padding:11px 13px 11px 40px;font-family:var(--fn);font-size:13.5px;outline:none;transition:border .15s,box-shadow .15s}
.fi2:focus{border-color:var(--go);box-shadow:0 0 0 3px var(--gd)}
.fi2::placeholder{color:var(--tx3)}
.fi2-pass{padding-right:42px}

/* PASSWORD TOGGLE */
.pass-toggle{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--tx3);cursor:pointer;padding:3px;transition:color .14s;display:flex;align-items:center;justify-content:center}
.pass-toggle:hover{color:var(--tx2)}
.pass-toggle svg{width:15px;height:15px}

/* REMEMBER */
.remember-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;margin-top:4px}
.check-label{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--tx2)}
.check-box{width:16px;height:16px;border:1px solid var(--bd2);border-radius:4px;background:var(--bg3);cursor:pointer;appearance:none;transition:all .14s;flex-shrink:0;position:relative}
.check-box:checked{background:var(--go);border-color:var(--go)}
.check-box:checked::after{content:'';position:absolute;left:4px;top:1px;width:5px;height:9px;border:2px solid #07070a;border-top:none;border-left:none;transform:rotate(40deg)}
.forgot-link{font-size:12.5px;color:var(--tx3);text-decoration:none;transition:color .14s}
.forgot-link:hover{color:var(--go)}

/* SUBMIT */
.submit-btn{width:100%;padding:13px;background:var(--go);color:#07070a;border:none;border-radius:10px;font-family:var(--fn);font-size:14px;font-weight:600;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:9px;letter-spacing:.1px}
.submit-btn:hover{background:var(--go2);box-shadow:0 4px 20px rgba(201,164,78,.3)}
.submit-btn:active{transform:scale(.98)}
.submit-btn:disabled{background:var(--bg4);color:var(--tx3);cursor:not-allowed;box-shadow:none;transform:none}
.submit-btn svg{width:16px;height:16px}

/* DIVIDER */
.divider{display:flex;align-items:center;gap:12px;margin:20px 0}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--bd)}
.divider span{font-size:11px;color:var(--tx3);white-space:nowrap}

/* FOOTER */
.form-footer{text-align:center;margin-top:20px}
.form-footer p{font-size:12px;color:var(--tx3)}

/* FEATURE BADGES */
.badges{display:flex;justify-content:center;gap:8px;margin-top:24px;flex-wrap:wrap}
.badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--tx3);background:var(--bg2);border:1px solid var(--bd);border-radius:20px;padding:4px 10px}
.badge svg{width:11px;height:11px}

/* SPINNER */
.spin{width:16px;height:16px;border:2.5px solid rgba(7,7,10,.25);border-top-color:#07070a;border-radius:50%;animation:sp .6s linear infinite}
@keyframes sp{to{transform:rotate(360deg)}}

/* FOCUS RING */
input:focus-visible{outline:none}
</style>
</head>
<body>

<div class="glow"></div>

<div class="card">

  <!-- LOGO -->
  <div class="logo-wrap">
    <div class="logo-box">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h7v7H3V3zm0 11h7v7H3v-7zm11-11h7v7h-7V3zm4 11h-1v3h-3v1h3v3h1v-3h3v-1h-3v-3z"/></svg>
    </div>
    <div class="logo-name">{{ config('app.name') }}</div>
    <div class="logo-sub">Toko Plastik & Bahan Kue · POS</div>
  </div>

  <!-- FORM CARD -->
  <div class="form-card">

    @if ($errors->any())
    <div class="error-box">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <p>{{ $errors->first() }}</p>
    </div>
    @endif

    @if (session('status'))
    <div style="background:rgba(62,207,142,.08);border:1px solid rgba(62,207,142,.2);border-radius:10px;padding:11px 14px;margin-bottom:20px;font-size:12.5px;color:var(--gn)">
      {{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}" id="login-form">
      @csrf

      <!-- EMAIL -->
      <div class="fg">
        <label class="fl" for="email">Email</label>
        <div class="inp-wrap">
          <div class="inp-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <input id="email" class="fi2" type="email" name="email"
            value="{{ old('email') }}" required autofocus autocomplete="email"
            placeholder="nama@email.com">
        </div>
      </div>

      <!-- PASSWORD -->
      <div class="fg">
        <label class="fl" for="password">Password</label>
        <div class="inp-wrap">
          <div class="inp-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          </div>
          <input id="password" class="fi2 fi2-pass" type="password" name="password"
            required autocomplete="current-password" placeholder="Password">
          <button type="button" class="pass-toggle" onclick="togglePass()" id="pass-toggle-btn" title="Tampilkan password">
            <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <!-- REMEMBER + FORGOT -->
      <div class="remember-row">
        <label class="check-label">
          <input type="checkbox" class="check-box" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
          Ingat saya
        </label>
        @if (Route::has('password.request'))
        <a class="forgot-link" href="{{ route('password.request') }}">Lupa password?</a>
        @endif
      </div>

      <!-- SUBMIT -->
      <button type="submit" class="submit-btn" id="submit-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Masuk ke Dashboard
      </button>

    </form>

    <div class="divider"><span>Demo: owner@toko.com / password</span></div>

    <div class="form-footer">
      <p>Hubungi administrator jika tidak bisa masuk</p>
    </div>

  </div>

  <!-- FEATURE BADGES -->
  <div class="badges">
    <span class="badge">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
      Aman & Terenkripsi
    </span>
    <span class="badge">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
      Real-time
    </span>
    <span class="badge">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
      Multi-fitur
    </span>
  </div>

</div>

<script>
function togglePass() {
  const inp = document.getElementById('password');
  const ico = document.getElementById('eye-icon');
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  ico.innerHTML = show
    ? '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>'
    : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
}

document.getElementById('login-form').addEventListener('submit', function() {
  const btn = document.getElementById('submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<div class="spin"></div> Memverifikasi...';
});
</script>
</body>
</html>
