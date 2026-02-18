<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FilamentLoginController extends Controller
{
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (($user instanceof FilamentUser) && (! $user->canAccessPanel(Filament::getPanel('admin')))) {
                Auth::logout();
                $this->throwFailedValidation();
            }

            $request->session()->regenerate();

            return redirect()->intended('/admin');
        }

        $this->throwFailedValidation();
    }

    protected function throwFailedValidation(): never
    {
        throw ValidationException::withMessages([
            'email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
