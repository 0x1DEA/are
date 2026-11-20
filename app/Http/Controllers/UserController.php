<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{
    /**
     * The guard implementation.
     */
    protected StatefulGuard $guard;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(StatefulGuard $guard)
    {
        $this->guard = $guard;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        dd($request->all());
    }

    public function storeFacebook(Request $request)
    {
        $data = Socialite::driver('facebook')->user();

        $try = User::query()->where('users.facebook_id', $data->getId())->first();

        if ($try) {
            $this->guard->login($try);

            return redirect()->intended();
        }

        try {
            Validator::make([
                'name' => $data->getName(),
                'email' => $data->getEmail(),
            ], [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
            ])->validate();
        } catch (ValidationException $e) {
            return 'email exists';
        }

        $user = new User;
        $user->name = $data->getName();
        $user->email = $data->getEmail();
        $user->password = 'facebook';
        // facebook emails verified if included
        $user->email_verified_at = now();

        $user->facebook_id = $data->getId();
        $user->facebook_avatar = $data->getAvatar();
        $user->facebook_token = $data->token;
        $user->facebook_token_expires_at = now()->addSeconds($data->expiresIn);

        $user->save();

        $this->guard->login($user);

        return redirect()->intended();
    }

    /**
     * Display the specified resource.
     */
    public function show(Agent $agent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Agent $agent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Agent $agent)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agent $agent)
    {
        //
    }
}
