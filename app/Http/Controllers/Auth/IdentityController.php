<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class IdentityController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('identity')
            ->scopes(['email'])
            ->redirectUrl(route('identity.callback'))
            ->redirect();
    }

    public function callback()
    {
        Log::info('Identity callback');

        $user = Socialite::driver('identity')
            ->scopes(['email'])
            ->redirectUrl(route('identity.callback'))
            ->user();

        $allowed_groups = array_map(fn($group) => trim($group), explode(',', config('services.identity.allowed_groups')));

        \Log::info("OK", [
            'user' => $user,
            'groups' => $user->groups,
            'allowed_groups' => $allowed_groups,
        ]);

        if (!array_reduce($user->groups, fn ($carry, $group) => $carry || in_array($group, $allowed_groups, true), false)) {
            return "Unauthorized";
        }

        $dbUser = User::where('email', $user->email)->first();

        if ($dbUser === null && config('services.identity.create_users')) {
            $dbUser = DB::transaction(function () use ($user) {
                return User::create([
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'password' => str()->random(32),
                ]);
            });
        } else if ($dbUser === null) {
            return redirect('/admin');
        }

        Auth::login($dbUser);

        return redirect()->intended('/admin');
    }
}
