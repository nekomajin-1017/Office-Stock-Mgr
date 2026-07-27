<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
  public function boot(): void
  {
    Fortify::createUsersUsing(CreateNewUser::class);

    Fortify::loginView(fn () => view('auth.login'));
    Fortify::registerView(fn () => view('auth.register'));

    Fortify::authenticateUsing(function (Request $request): ?User {
      $user = User::where('email', (string) $request->string('email'))->first();

      if (! $user || ! $user->is_active || ! Hash::check((string) $request->string('password'), $user->password)) {
        return null;
      }

      return $user;
    });

    RateLimiter::for('login', function (Request $request): Limit {
      $throttleKey = Str::transliterate(
        Str::lower($request->string(Fortify::username())).'|'.$request->ip(),
      );

      return Limit::perMinute(5)->by($throttleKey);
    });
  }
}
