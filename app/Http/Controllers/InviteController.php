<?php

namespace App\Http\Controllers;

use App\Contracts\PartnerInviteServiceInterface;
use App\Models\PartnerInvite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/**
 * Controller for handling partner invite acceptance and rejection.
 */
class InviteController extends Controller
{
    public function __construct(
        private PartnerInviteServiceInterface $inviteService
    ) {}

    /**
     * Show the invite page based on token.
     */
    public function show(string $token)
    {
        $invite = PartnerInvite::where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$invite) {
            return Inertia::render('Invite/Invalid', [
                'message' => 'Este convite não é válido ou já foi utilizado.',
            ]);
        }

        if ($invite->isExpired()) {
            return Inertia::render('Invite/Expired', [
                'inviterName' => $invite->inviter->name,
            ]);
        }

        // Check if this is for an existing user
        $existingUser = User::where('email', $invite->email)->first();

        if ($existingUser) {
            return Inertia::render('Invite/ExistingUser', [
                'token' => $token,
                'inviterName' => $invite->inviter->name,
                'inviteeName' => $invite->name,
                'email' => $invite->email,
                'hasPreviousWedding' => $existingUser->current_wedding_id !== null,
            ]);
        }

        return Inertia::render('Invite/NewUser', [
            'token' => $token,
            'inviterName' => $invite->inviter->name,
            'inviteeName' => $invite->name,
            'email' => $invite->email,
        ]);
    }

    /**
     * Accept invite for new user (create account and accept).
     */
    public function acceptNewUser(Request $request, string $token)
    {
        $invite = $this->getValidInvite($token);

        if (!$invite) {
            return back()->withErrors(['token' => 'Convite inválido ou expirado.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Create the new user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $invite->email,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'onboarding_completed' => true, // Skip onboarding for invited users
        ]);

        // Accept the invite
        $this->inviteService->acceptInvite($invite, $user);

        // Log the user in
        Auth::login($user);

        // Set the wedding context in session
        session(['filament_wedding_id' => $invite->wedding_id]);

        return redirect()->route('filament.admin.pages.dashboard')
            ->with('success', 'Conta criada com sucesso! Bem-vindo(a) ao planejamento do casamento.');
    }

    /**
     * Accept invite for existing user.
     */
    public function acceptExistingUser(Request $request, string $token)
    {
        $invite = $this->getValidInvite($token);

        if (!$invite) {
            return back()->withErrors(['token' => 'Convite inválido ou expirado.']);
        }

        $user = Auth::user();

        if (!$user) {
            // User needs to login first
            return redirect()->route('filament.admin.auth.login')
                ->with('redirect_after_login', route('convite.show', $token));
        }

        // Verify the logged-in user matches the invite email
        if ($user->email !== $invite->email) {
            return back()->withErrors([
                'email' => 'Você precisa estar logado com o e-mail ' . $invite->email . ' para aceitar este convite.',
            ]);
        }

        // Accept the invite
        $this->inviteService->acceptInvite($invite, $user);

        // Set the wedding context in session
        session(['filament_wedding_id' => $invite->wedding_id]);

        return redirect()->route('filament.admin.pages.dashboard')
            ->with('success', 'Convite aceito! Você agora faz parte do planejamento do casamento.');
    }

    /**
     * Decline invite.
     */
    public function decline(Request $request, string $token)
    {
        $invite = $this->getValidInvite($token);

        if (!$invite) {
            return back()->withErrors(['token' => 'Convite inválido ou expirado.']);
        }

        $this->inviteService->declineInvite($invite);

        return Inertia::render('Invite/Declined', [
            'inviterName' => $invite->inviter->name,
        ]);
    }

    /**
     * Get a valid (pending, not expired) invite by token.
     */
    private function getValidInvite(string $token): ?PartnerInvite
    {
        $invite = PartnerInvite::where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$invite || $invite->isExpired()) {
            return null;
        }

        return $invite;
    }
}
