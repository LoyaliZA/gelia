<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Seguro | Gelia Hub</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-dark-900 text-dark-text min-h-screen flex items-center justify-center font-sans antialiased">
    <div class="w-full max-w-md p-8 bg-dark-800 border border-dark-700 rounded-2xl shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-600 to-indigo-600"></div>
        
        <div class="text-center mb-8">
            <h1 class="text-3xl font-light tracking-tight text-white mb-2">
                G.E.L.I.A.<span class="font-bold text-aromas-main">HUB</span>
            </h1>
            <p class="text-sm text-dark-muted">Ingresa tus credenciales de acceso</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf
            
            @error('login')
                <div class="p-3 bg-red-500/10 border border-red-500/50 rounded-lg text-red-400 text-sm text-center font-bold">
                    {{ $message }}
                </div>
            @enderror

            <div>
                <label for="login" class="block text-sm font-bold text-gray-300 mb-1">Correo Electrónico o Usuario</label>
                <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus class="w-full bg-dark-900 border border-dark-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500 transition-colors" placeholder="ej. admin_bellaroma o lic@correo.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-gray-300 mb-1">Contraseña</label>
                <input type="password" id="password" name="password" required class="w-full bg-dark-900 border border-dark-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500 transition-colors" placeholder="••••••••">
            </div>

            <button type="submit" class="w-full py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg transition-all transform hover:scale-[1.02]">
                Entrar al Sistema
            </button>
        </form>
    </div>
</body>
</html>