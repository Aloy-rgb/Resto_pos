<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Resto POS') }} | Restaurant operations</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --forest: #17352b; --cream: #f5f0e7; --coral: #e66d4e; --sage: #b8c9a8; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--cream); color: var(--forest); font-family: 'Manrope', sans-serif; }
        .restaurant-home { min-height: 100vh; overflow: hidden; }
        .restaurant-nav { display: flex; justify-content: space-between; align-items: center; max-width: 1240px; margin: auto; padding: 28px 28px 0; }
        .brand { display: flex; align-items: center; gap: 11px; color: var(--forest); font-weight: 800; letter-spacing: -.04em; text-decoration: none; }
        .brand-mark { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 50%; background: var(--coral); color: var(--cream); font-size: 20px; }
        .nav-actions { display: flex; align-items: center; gap: 18px; font-size: 13px; font-weight: 700; }
        .nav-actions a { color: var(--forest); text-decoration: none; }
        .nav-actions .nav-button { padding: 11px 17px; background: var(--forest); color: var(--cream); border-radius: 3px; }
        .hero { display: grid; grid-template-columns: 1.05fr .95fr; gap: 60px; align-items: center; max-width: 1240px; min-height: 680px; margin: auto; padding: 76px 28px 100px; }
        .kicker { color: var(--coral); font: 12px 'DM Mono', monospace; letter-spacing: .16em; text-transform: uppercase; }
        h1 { max-width: 680px; margin: 17px 0 24px; font-family: Georgia, serif; font-size: clamp(3.4rem, 7vw, 7.2rem); font-weight: 400; line-height: .88; letter-spacing: -.07em; }
        h1 em { color: var(--coral); font-style: italic; }
        .hero-copy { max-width: 470px; color: #557066; font-size: 17px; line-height: 1.65; }
        .hero-actions { display: flex; align-items: center; gap: 22px; margin-top: 32px; }
        .primary-action { display: inline-flex; gap: 16px; align-items: center; padding: 15px 19px; background: var(--coral); color: white; text-decoration: none; font-size: 13px; font-weight: 800; border-radius: 3px; }
        .text-action { color: var(--forest); font-size: 13px; font-weight: 800; text-decoration: underline; text-underline-offset: 5px; }
        .hero-art { position: relative; min-height: 480px; }
        .plate { position: absolute; inset: 25px 18px 15px 50px; display: grid; place-items: center; border-radius: 48% 48% 45% 45%; background: #dbe4d1; transform: rotate(4deg); }
        .plate::before { content: ''; position: absolute; inset: 45px; border: 1px solid rgba(23,53,43,.2); border-radius: 50%; }
        .dish { position: relative; z-index: 1; width: 235px; height: 235px; border-radius: 50%; background: #f8f3e8; box-shadow: 0 18px 35px rgba(23,53,43,.17); }
        .dish::before { content: ''; position: absolute; width: 132px; height: 94px; top: 68px; left: 51px; border-radius: 55% 45% 48% 52%; background: #d76d45; transform: rotate(-15deg); box-shadow: 27px -10px 0 #e7a34d, -15px 18px 0 #6b8c57, 30px 26px 0 #9a4d38; }
        .dish::after { content: '✦'; position: absolute; top: 56px; left: 91px; color: #f5d68b; font-size: 25px; }
        .floating-label { position: absolute; z-index: 2; padding: 12px 15px; background: var(--forest); color: var(--cream); font-size: 11px; line-height: 1.35; box-shadow: 0 12px 25px rgba(23,53,43,.16); }
        .floating-label strong, .floating-label small { display: block; } .floating-label strong { font-size: 14px; } .floating-label small { color: var(--sage); margin-top: 4px; }
        .label-one { top: 40px; right: 4px; transform: rotate(5deg); } .label-two { bottom: 55px; left: 0; transform: rotate(-5deg); }
        .feature-strip { background: var(--forest); color: var(--cream); }
        .features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; max-width: 1240px; margin: auto; padding: 0 28px; background: rgba(245,240,231,.16); }
        .feature { min-height: 150px; padding: 30px 25px 28px 0; background: var(--forest); } .feature + .feature { padding-left: 25px; }
        .feature-number { color: var(--coral); font: 12px 'DM Mono', monospace; } .feature h2 { margin: 13px 0 8px; font: 22px Georgia, serif; } .feature p { max-width: 280px; margin: 0; color: #b3c8b9; font-size: 12px; line-height: 1.6; }
        @media (max-width: 760px) { .restaurant-nav { padding: 20px 18px 0; } .hero { grid-template-columns: 1fr; gap: 15px; min-height: auto; padding: 75px 18px 70px; } h1 { font-size: clamp(3.6rem, 17vw, 6rem); } .hero-copy { font-size: 15px; } .hero-art { min-height: 360px; } .plate { inset: 10px 15px 10px 25px; } .features { grid-template-columns: 1fr; padding: 0 18px; } .feature, .feature + .feature { min-height: auto; padding: 25px 0; } }
    </style>
</head>
<body>
<div class="restaurant-home">
    <nav class="restaurant-nav">
        <a class="brand" href="{{ url('/') }}"><span class="brand-mark">✦</span><span>{{ config('app.name', 'Resto POS') }}</span></a>
        <div class="nav-actions">
            @auth
                <a href="{{ route('pos.index') }}">Open POS</a>
                <a class="nav-button" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Sign out</a>
                <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display:none">@csrf</form>
            @else
                @if (Route::has('login')) <a href="{{ route('login') }}">Sign in</a> @endif
                @if (Route::has('register')) <a class="nav-button" href="{{ route('register') }}">Create account</a> @endif
            @endauth
        </div>
    </nav>

    <main class="hero">
        <section>
            <div class="kicker">Front of house / back of house</div>
            <h1>Good food.<br><em>In rhythm.</em></h1>
            <p class="hero-copy">A calm command center for busy tables, thoughtful service, and every order that deserves to arrive just right.</p>
            <div class="hero-actions">
                <a class="primary-action" href="{{ auth()->check() ? route('pos.index') : route('login') }}">{{ auth()->check() ? "Open today's service" : 'Sign in to service' }} <span>↗</span></a>
                <a class="text-action" href="#how-it-works">See how it works</a>
            </div>
        </section>
        <section class="hero-art" aria-label="A plated restaurant dish">
            <div class="plate"><div class="dish"></div></div>
            <div class="floating-label label-one"><strong>Service is live</strong><small>Orders move together</small></div>
            <div class="floating-label label-two"><strong>Table 08 · Ready</strong><small>Roasted tomato pasta</small></div>
        </section>
    </main>

    <section class="feature-strip" id="how-it-works">
        <div class="features">
            <article class="feature"><span class="feature-number">01</span><h2>Take the table</h2><p>Capture the guest, table, and order in one clean ticket.</p></article>
            <article class="feature"><span class="feature-number">02</span><h2>Keep the rhythm</h2><p>Kitchen, manager, and service teams always see the next move.</p></article>
            <article class="feature"><span class="feature-number">03</span><h2>Close with care</h2><p>Take payment, create the invoice, and keep the record ready.</p></article>
        </div>
    </section>
</div>
</body>
</html>
