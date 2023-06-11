<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use App\Utils\Response;

class PassportAuthController extends Controller
{
    use Response;
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Registration
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|min:4',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $user = $this->userService->register($request);

        switch ($user['status']) {
            case false:
                return $this->responseError($user['message'], 500);
            default:
                return $this->responseData(['token' => $user['token']]);
        }
    }

    /**
     * Login
     */
    public function login(Request $request)
    {

        $user = $this->userService->login($request);

        switch ($user['status']) {
            case false:
                return $this->responseError($user['message'], 500);
            default:
                return $this->responseData(['token' => $user['token']]);
        }
    }
}
