<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    @php
        $googleEnabled = (bool) config('services.google.enabled', false)
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    @endphp

    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-6 text-rose-600">{{ config('app.name') }}</h1>
        
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('filament.admin.auth.login.post') }}">
            @csrf
            
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-rose-500">
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Senha</label>
                <input type="password" name="password" id="password" required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-rose-500">
            </div>

            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="mr-2">
                    <span class="text-sm text-gray-600">Lembrar-me</span>
                </label>
            </div>

            <button type="submit" 
                class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-rose-500">
                Entrar
            </button>
        </form>

        @if ($googleEnabled)
            <div class="my-4 flex items-center">
                <div class="flex-1 border-t border-gray-200"></div>
                <span class="px-3 text-xs uppercase text-gray-400">ou</span>
                <div class="flex-1 border-t border-gray-200"></div>
            </div>

            <a
                href="{{ route('auth.google.redirect') }}"
                class="w-full inline-flex items-center justify-center gap-2 border border-gray-300 text-gray-700 font-semibold py-2 px-4 rounded hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#EA4335" d="M12 10.2v3.9h5.4c-.2 1.3-1.6 3.9-5.4 3.9-3.2 0-5.8-2.7-5.8-6s2.6-6 5.8-6c1.8 0 3 .8 3.7 1.4l2.5-2.4C16.6 3.4 14.5 2.6 12 2.6 6.8 2.6 2.6 6.8 2.6 12s4.2 9.4 9.4 9.4c5.4 0 9-3.8 9-9.2 0-.6-.1-1.1-.2-1.6H12z"/>
                    <path fill="#34A853" d="M2.6 7.9l3.2 2.4c.8-2.4 3-4.2 6.2-4.2 1.8 0 3 .8 3.7 1.4l2.5-2.4C16.6 3.4 14.5 2.6 12 2.6c-3.7 0-6.9 2.1-8.4 5.3z"/>
                    <path fill="#FBBC05" d="M12 21.4c2.4 0 4.5-.8 6-2.1l-2.8-2.3c-.8.6-1.9 1-3.2 1-3.7 0-5.1-2.6-5.4-3.9l-3.2 2.5c1.5 3.2 4.7 5.4 8.6 5.4z"/>
                    <path fill="#4285F4" d="M21 12.2c0-.6-.1-1.1-.2-1.6H12v3.9h5.4c-.3 1.3-1.1 2.3-2.2 3l2.8 2.3c1.6-1.5 3-3.8 3-7.6z"/>
                </svg>
                Continuar com Google
            </a>
        @endif
    </div>
</body>
</html>
