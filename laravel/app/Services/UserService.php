<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\UserFollow;
use Carbon\Carbon;

class UserService
{

    public function register($request)
    {
        DB::beginTransaction();

        try {
            $store = [
                'uuid' => Str::uuid(),
                'username' => $request->username,
                'firstname' => $request->firstname ?? "",
                'lastname' => $request->lastname ?? "",
                'dob' => $request->dob ?? null,
                'phone_number' => $request->phone_number ?? null,
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ];
            $user = User::create($store);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $response = array(
                'status' => false, 'message' => $ex->getPrevious()->getMessage()
            );
            return $response;
        }

        $token = $user->createToken('LaravelAuthApp')->accessToken;
        $response = array(
            'status' => true, 'token' => $token
        );
        return $response;
    }

    public function login($request)
    {
        $data = [
            'email' => $request->email,
            'password' => $request->password
        ];

        if (auth()->attempt($data)) {
            $token = auth()->user()->createToken('LaravelAuthApp')->accessToken;
            $response = array(
                'status' => true, 'token' => $token
            );
            return $response;
        } else {
            return array('status' => false, 'message' => 'Unauthorised');
        }
    }

    public function index()
    {
        $userAttr = auth()->user()->getAttributes();
        $dob = new Carbon($userAttr['dob']);
        $userData = array(
            'uuid' => $userAttr['uuid'],
            'username' => $userAttr['username'],
            'firstname' => $userAttr['firstname'],
            'lastname' => $userAttr['lastname'],
            'email' => $userAttr['email'],
            'phone_number' => $userAttr['phone_number'],
            'dob' =>   $dob->toDateString(),
            'following' => UserFollow::Where('user_id', '=',  $userAttr['uuid'])->count(),
            'followers' => UserFollow::Where('following', '=',  $userAttr['uuid'])->count(),
            'image' => $userAttr['image']
        );

        $response = array(
            'status' => true, 'user_data' => $userData
        );
        return $response;
    }

    public function update($request, $id)
    {
        $user = User::Where('uuid', '=', $id)->first();
        if (!$user) {
            return array('status' => false, 'message' => 'User not found');
        }

        $mimeType = $request->image->getClientMimeType();
        $path = $request->image->path();

        $image = "data:" . $mimeType . ";base64," . base64_encode(file_get_contents($path));

        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->dob = $request->dob;
        $user->image = $image;
        $user->phone_number = $request->phone_number;

        $updated = $user->save();

        if ($updated)
            return array('status' => true);
        else
            return array('status' => false, 'message' => 'User can not be updated');
    }

    public function find($query)
    {
        $auth = auth()->user()->getAttributes();
        if (!$auth) {
            return array('status' => false, 'message' => 'Unauthorized!');
        }
        $allUsers = [];
        $userAttr = User::Where('username', 'like', '%' . $query . '%')
            ->orWhere('firstname', 'like', '%' . $query . '%')
            ->orWhere('lastname', 'like', '%' . $query . '%')
            ->orWhere('email', 'like', '%' . $query . '%')->get();

        if (!$userAttr) {
            return array('status' => false, 'message' => 'User not found');
        }

        foreach ($userAttr as $key => $value) {
            $dob = new Carbon($value->dob);
            $userData = array(
                'uuid' => $value->uuid,
                'username' => $value->username,
                'firstname' => $value->firstname,
                'lastname' => $value->lastname,
                'email' => $value->email,
                'phone_number' => $value->phone_number,
                'dob' =>   $dob->toDateString(),
                'following' => UserFollow::Where('user_id', '=', $value->uuid)->count(),
                'followers' => UserFollow::Where('following', '=', $value->uuid)->count(),
                'image' => $value->image
            );
            array_push($allUsers, $userData);
        }



        return array('status' => true, 'users_data' => $allUsers);
    }

    public function following($id)
    {
        $auth = auth()->user()->getAttributes();
        if (!$auth) {
            return array('status' => false, 'message' => 'Unauthorized!');
        }
        DB::beginTransaction();

        try {
            $userFollow = new UserFollow();
            $userFollow->user_id = $auth['uuid'];
            $userFollow->following = $id;
            $userFollow->save();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $response = array(
                'status' => false, 'message' => $ex->getPrevious()->getMessage()
            );
            return $response;
        }

        $response = array(
            'status' => true, 'message' => 'success'
        );
        return $response;
    }

    public function unfollow($id)
    {
        $auth = auth()->user()->getAttributes();
        if (!$auth) {
            return array('status' => false, 'message' => 'Unauthorized!');
        }

        DB::beginTransaction();

        try {
            $userFollow = UserFollow::Where('user_id', '=', $auth['uuid'])
                ->Where('following', '=', $id);

            $userFollow->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $response = array(
                'status' => false, 'message' => $ex->getMessage()
            );
            return $response;
        }

        $response = array(
            'status' => true, 'message' => 'success'
        );
        return $response;
    }

    public function getFollowing()
    {
        $auth = auth()->user()->getAttributes();
        if (!$auth) {
            return array('status' => false, 'message' => 'Unauthorized!');
        }

        $following = UserFollow::select('user_follows.*', 'users.username')
            ->leftJoin('users', 'following', '=', 'users.uuid')
            ->Where('user_id', '=', $auth['uuid'])->paginate(15);
        return $following;
    }

    public function getFollowers()
    {
        $auth = auth()->user()->getAttributes();
        if (!$auth) {
            return array('status' => false, 'message' => 'Unauthorized!');
        }

        $following = UserFollow::select('user_follows.*', 'users.username')
            ->leftJoin('users', 'user_id', '=', 'users.uuid')
            ->Where('following', '=', $auth['uuid'])->paginate(15);
        return $following;
    }
}
