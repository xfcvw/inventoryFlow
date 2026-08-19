<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        InventoryFlow | Create account
    </title>

    <link
        rel="stylesheet"
        href="{{ asset('assets/app.css') }}"
    >
</head>

<body class="auth-body">

    <!-- SELETOR DE IDIOMA -->
    <div class="language-selector">

        <select id="language-select">

            <option value="en">
                🇺🇸 English
            </option>

            <option value="pt">
                🇧🇷 Português
            </option>

        </select>

    </div>


    <main class="login-page">

        <section class="login-container">


            <!-- ============================= -->
            <!-- LADO ESQUERDO / MARCA -->
            <!-- ============================= -->

            <div class="login-brand">

                <div class="logo">

                    <div class="logo-icon">
                        IF
                    </div>

                    <span>
                        InventoryFlow
                    </span>

                </div>


                <div class="brand-content">

                    <span class="brand-tag">
                        MULTI-TENANT SaaS
                    </span>


                    <h1>

                        @if ($invitation)

                            Join {{ $invitation->workspace->name }}

                        @else

                            Create your workspace.
                            Run your inventory.

                        @endif

                    </h1>


                    <p>

                        @if ($invitation)

                            You were invited as
                            {{ $invitation->role }}.

                        @else

                            Products, orders, customers
                            and stock are isolated
                            by workspace.

                        @endif

                    </p>

                </div>


                <div class="brand-footer">

                    <span>
                        Laravel + PostgreSQL
                    </span>

                    <span>
                        Complete SaaS MVP
                    </span>

                </div>

            </div>


            <!-- ============================= -->
            <!-- FORMULÁRIO -->
            <!-- ============================= -->

            <div class="login-form-area">

                <div class="form-container">


                    <div class="form-header">

                        <span class="welcome-text">
                            START FREE
                        </span>


                        <h2>
                            Create your account
                        </h2>


                        <p>

                            @if ($invitation)

                                Use the invited
                                email address.

                            @else

                                The first account
                                becomes workspace owner.

                            @endif

                        </p>

                    </div>


                    <form
                        method="POST"
                        action="{{ route('register.store') }}"
                    >

                        @csrf


                        <!-- TOKEN DE CONVITE -->

                        @if ($invitation)

                            <input
                                type="hidden"
                                name="invite_token"
                                value="{{ $invitation->token }}"
                            >

                        @endif


                        <!-- NOME -->

                        <div class="input-group">

                            <label for="name">
                                Name
                            </label>

                            <input
                                id="name"
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                autocomplete="name"
                            >

                        </div>


                        <!-- WORKSPACE -->

                        @unless ($invitation)

                            <div class="input-group">

                                <label for="workspace_name">
                                    Workspace name
                                </label>

                                <input
                                    id="workspace_name"
                                    type="text"
                                    name="workspace_name"
                                    value="{{ old('workspace_name') }}"
                                    required
                                >

                            </div>

                        @endunless


                        <!-- EMAIL -->

                        <div class="input-group">

                            <label for="email">
                                Email
                            </label>

                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old(
                                    'email',
                                    $invitation?->email
                                ) }}"

                                @if ($invitation)
                                    readonly
                                @endif

                                required
                                autocomplete="email"
                            >

                        </div>


                        <!-- SENHA -->

                        <div class="input-group">

                            <label for="password">
                                Password
                            </label>

                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="new-password"
                            >

                        </div>


                        <!-- CONFIRMAR SENHA -->

                        <div class="input-group">

                            <label for="password_confirmation">
                                Confirm password
                            </label>

                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                            >

                        </div>


                        <!-- ERROS -->

                        @if ($errors->any())

                            <div class="server-error">

                                {{ $errors->first() }}

                            </div>

                        @endif


                        <!-- BOTÃO -->

                        <button
                            type="submit"
                            class="login-button"
                        >

                            @if ($invitation)

                                Create account & join

                            @else

                                Create workspace

                            @endif

                        </button>

                    </form>


                    <p class="auth-switch">

                        Already have an account?

                        <a href="{{ route('login') }}">
                            Sign in
                        </a>

                    </p>

                </div>

            </div>

        </section>

    </main>


    <script
        src="{{ asset('assets/i18n.js') }}"
    ></script>

</body>

</html>