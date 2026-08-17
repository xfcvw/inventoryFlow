<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InventoryFlow | Login</title>
    <link rel="stylesheet" href="{{ asset('assets/app.css') }}">
</head>
<body class="auth-body">
    <div class="language-selector">
        <select id="language-select" aria-label="Language">
            <option value="en">🇺🇸 English</option>
            <option value="pt">🇧🇷 Português</option>
        </select>
    </div>
    <main class="login-page">
        <section class="login-container">
            <div class="login-brand">
                <div class="logo"><div class="logo-icon">IF</div><span>InventoryFlow</span></div>
                <div class="brand-content">
                    <span class="brand-tag" data-i18n="brandTag">Inventory Management</span>
                    <h1 data-i18n="brandTitle">Smart inventory. Better decisions.</h1>
                    <p data-i18n="brandDescription">Manage your products, inventory and orders through a simple and efficient platform.</p>
                </div>
                <div class="brand-footer"><span>Laravel 13 + PostgreSQL</span><span>Portfolio v2.0</span></div>
            </div>
            <div class="login-form-area">
                <div class="form-container">
                    <div class="form-header">
                        <span class="welcome-text" data-i18n="welcome">WELCOME BACK</span>
                        <h2 data-i18n="loginTitle">Sign in to your account</h2>
                        <p data-i18n="loginDescription">Use the seeded demo account to access the system.</p>
                    </div>
                    <form method="POST" action="{{ route('login.store') }}">
                        @csrf
                        <div class="input-group"><label for="email" data-i18n="email">Email</label><input type="email" id="email" name="email" value="{{ old('email', 'demo@inventoryflow.com') }}" required></div>
                        <div class="input-group"><label for="password" data-i18n="password">Password</label><input type="password" id="password" name="password" value="inventory123" required></div>
                        <label class="remember login-remember"><input type="checkbox" name="remember" value="1"><span data-i18n="remember">Remember me</span></label>
                        @if ($errors->any())<div class="server-error">{{ $errors->first() }}</div>@endif
                        <button type="submit" class="login-button" data-i18n="signIn">Sign in</button>
                    </form>
                    <div class="credentials-card"><strong data-i18n="demoCredentials">Demo credentials</strong><code>demo@inventoryflow.com</code><code>inventory123</code></div>
                    <p class="demo-text"><span data-i18n="portfolio">Portfolio project developed by</span><strong> Vinicius</strong></p>
                </div>
            </div>
        </section>
    </main>
    <script src="{{ asset('assets/i18n.js') }}"></script>
</body>
</html>
