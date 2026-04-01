<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Muestra la pantalla de Login
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Procesa el formulario
    public function login(Request $request)
    {
        // Validamos que envíen datos
        $request->validate([
            'login' => 'required|string', // Puede ser email o username
            'password' => 'required|string',
        ]);

        // Magia: Detectamos si el texto introducido es un correo válido
        $fieldType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Intentamos autenticar
        if (Auth::attempt([$fieldType => $request->login, 'password' => $request->password], $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Si entra correctamente, lo mandamos directo a WooCommerce
            return redirect()->intended(route('woocommerce.index'));
        }

        // Si falla, lo regresamos con un error
        return back()->withErrors([
            'login' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('login');
    }

    // Cierra la sesión
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}