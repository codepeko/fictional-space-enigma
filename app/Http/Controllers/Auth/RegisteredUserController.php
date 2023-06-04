<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

use Laravel\Socialite\Facades\Socialite;
use App\Models\SocialProfile;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        if(config('settings.registration_open') != 1) return redirect('/')->withErrors(__('Registration is closed'));        
        return view('auth.register')->with('page_title',__('Sign Up'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if(config('settings.registration_open') != 1) return redirect('/')->withErrors(__('Registration is closed'));

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'g-recaptcha-response' => (config('settings.captcha')) ? 'required|captcha' : 'nullable'
        ]);

        $user_data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ];

        if(config('settings.auto_approve_user') == 1){
            $user_data['email_verified_at'] = date('Y-m-d H:i:s');
            $user_data['status'] = 1;
        }

        $user = User::create($user_data);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }

 /**
     * Redirect the user to the GitHub authentication page.
     *
     * @param string $provider
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToSocialProvider($provider)
    {
        if(config('settings.registration_open') != 1) return redirect()->back()->withErrors(__('Registration is closed'));

        try {
            if (config('settings.social_login_facebook') == 1) {
                if ($provider == 'facebook') {
                    return Socialite::driver($provider)
                        ->setScopes(['email', 'public_profile'])
                        ->redirect();
                }
            }

            if (config('settings.social_login_twitter') == 1) {
                if ($provider == 'twitter') {
                    return Socialite::driver($provider)->redirect();
                }
            }

            if (config('settings.social_login_google') == 1) {
                if ($provider == 'google') {
                    return Socialite::driver($provider)
                        ->setScopes(['email', 'profile'])
                        ->redirect();
                }
            }

            return Socialite::driver($provider)->redirect();
        } catch (\Exception $exception) {
            return redirect('login')->withErrors($exception->getMessage());
        }
    }  

    /**
     * Obtain the user information from GitHub.
     *
     * @param string $provider
     *
     * @see https://laravel.com/docs/5.7/socialite#retrieving-user-details
     *
     * @return \Illuminate\Http\Response
     */
    public function handleSocialProviderCallback($provider)
    {
        try {
            $social_user = Socialite::driver($provider)->user();
        } catch (\Exception $exception) {
            return redirect('login')->withErrors($exception->getMessage());
        }

        if (!$social_user) {
            return redirect('login')->withErrors(__('Invalid login Try again'));
        }

        if (!$social_user->getEmail()) {
            return redirect('login')->withErrors(__("You must have an email on your social profile"));
        }

        $social_profile = SocialProfile::query()
            ->where([
                ['provider', $provider],
                ['provider_id', $social_user->getId()],
            ])
            ->first();

        if ($social_profile) {
            if (Auth::loginUsingId($social_profile->user_id)) {

                if (Auth::user()->role == 1) {
                    return redirect('admin/dashboard');
                }

                return redirect('/');
            }
        }

        $user = User::query()
            ->whereEmail($social_user->getEmail())
            ->first();

        if ($user) {
            $social_profile = new SocialProfile();
            $social_profile->user_id = $user->id;
            $social_profile->provider = $provider;
            $social_profile->provider_id = $social_user->getId();
            $social_profile->nickname = $social_user->getNickname();
            $social_profile->name = $social_user->getName();
            $social_profile->email = $social_user->getEmail();
            $social_profile->avatar = $social_user->getAvatar();
            $social_profile->save();

            if ($social_profile) {
                if (Auth::loginUsingId($social_profile->user_id)) {

                    if (Auth::user()->role == 1) {
                        return redirect('admin/dashboard');
                    }

                    return redirect('/');
                }
            }
        }

        if(config('settings.registration_open') != 1) return redirect()->back()->withErrors(__('Registration is closed'));

        $user = new User();
        $social_user_email = explode('@', $social_user->getEmail());
        $username = str_limit($social_user_email[0],20);      
        $username = preg_replace("/[^A-Za-z0-9 ]/", '', $username);  
        $user->name = $username;
        $user->email = $social_user->getEmail();
        $user->password = Hash::make(str_random(32));
        $user->role = 2;
        $user->status = 1;
        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->avatar = $social_user->getAvatar();

        if ($user->save()) {
            $social_profile = new SocialProfile();
            $social_profile->user_id = $user->id;
            $social_profile->provider = $provider;
            $social_profile->provider_id = $social_user->getId();
            $social_profile->nickname = $social_user->getNickname();
            $social_profile->name = $social_user->getName();
            $social_profile->email = $social_user->getEmail();
            $social_profile->avatar = $social_user->getAvatar();
            $social_profile->save();

            if ($social_profile) {

                if (Auth::loginUsingId($social_profile->user_id)) {
                    if (Auth::user()->role == 1) {
                        return redirect('admin/dashboard')->withSuccess(__('Your account successfully created'));
                    }

                    return redirect('/')->withSuccess(__('Your account successfully created'));
                }
            }
        }
    }

}
