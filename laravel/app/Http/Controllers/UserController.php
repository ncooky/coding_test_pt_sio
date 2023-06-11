<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\Response;
use App\Services\UserService;

class UserController extends Controller
{
    use Response;
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function ping()
    {
        return $this->pingResponse();
    }

    /**
     * Show User Profile
     */
    public function index()
    {
        try {
            $user = $this->userService->index();
        } catch (\Exception $e) {
            return $this->responseError('unauhorized', 401);
        }
        switch ($user['status']) {
            case false:
                return $this->responseError($user['message'], 500);
            default:
                return $this->responseData(['user_data' => $user['user_data']]);
        }
    }

    /**
     * Find User by query
     */
    public function find(Request $request)
    {
        $search = $request->only('q');
        $user =  $this->userService->find($search['q']);
        switch ($user['status']) {
            case false:
                return $this->responseError($user['message'], 500);
            default:
                return $this->responseData(['users_data' => $user['users_data']]);
        }
    }

    /**
     * Update User Profile
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'firstname' => 'required',
            'lastname' => 'required',
            'dob' => 'required',
            'image' => 'required|image|mimes:png,jpg,jpeg|max:2048'
        ]);

        $user = $this->userService->update($request, $id);

        switch ($user['status']) {
            case false:
                return $this->responseError($user['message'], 500);
            default:
                return $this->responseData(['message' => "success updated"]);
        }
    }

    /**
     * Do Following User
     */
    public function follow($id)
    {
        $user = $this->userService->following($id);
        switch ($user['status']) {
            case false:
                return $this->responseError($user['message'], 500);
            default:
                return $this->responseData(['message' => "success updated"]);
        }
    }

    /**
     * Do UnFollowing User
     */
    public function unfollow($id)
    {
        $user = $this->userService->unfollow($id);
        switch ($user['status']) {
            case false:
                return $this->responseError($user['message'], 500);
            default:
                return $this->responseData(['message' => "success unfollow"]);
        }
    }

    /**
     * Get List Following
     */
    public function followinglist()
    {
        return $this->userService->getFollowing();
    }

    /**
     * Get List Follower
     */
    public function followerlist()
    {
        return $this->userService->getFollowers();
    }
}
