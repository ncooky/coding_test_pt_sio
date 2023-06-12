<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Post;
use App\Models\PostComments;
use App\Models\PostImages;
use App\Models\PostLikes;
use Carbon\Carbon;

class PostService
{
    public function index()
    {
        $auth = auth()->user()->getAttributes();
        if (!$auth) {
            return array('status' => false, 'message' => 'Unauthorized!');
        }

        $posts = Post::Where('user_id', '=', $auth['uuid'])->get();

        $userPosts = [];
        foreach ($posts as $post) {
            $getPostStruct = array(
                'post_id' => $post->uuid,
                'user_id' => $post->user_id,
                'title' => $post->title,
                'caption' => $post->caption,
                'image' => PostImages::Select('id', 'image')->Where('post_id', '=', $post->uuid)->first()
            );
            array_push($userPosts, $getPostStruct);
        }
        $response = array(
            'status' => true, 'post_data' => $userPosts
        );
        return $response;
    }

    public function show($id)
    {
        $auth = auth()->user()->getAttributes();
        if (!$auth) {
            return array('status' => false, 'message' => 'Unauthorized!');
        }

        $post = Post::Where('user_id', '=', $auth['uuid'])
            ->Where('uuid', '=', $id)
            ->first();

        if (!$post)
            return array(
                'status' => false, 'message' => 'Post Not Found'
            );

        $postLikes = PostLikes::Select('users.username')
            ->leftJoin('users', 'user_id', '=', 'users.uuid')
            ->Where('user_id', '=', $post->user_id)
            ->Where('post_id', '=', $post->uuid)
            ->get();

        $postComments = PostComments::Select(
            'post_comments.user_id',
            'post_comments.comment',
            'post_comments.created_at',
            'users.username'
        )
            ->leftJoin('users', 'user_id', '=', 'users.uuid')
            ->Where('user_id', '=', $post->user_id)
            ->Where('post_id', '=', $post->uuid)
            ->get();

        $getPostStruct = array(
            'post_id' => $post->uuid,
            'user_id' => $post->user_id,
            'title' => $post->title,
            'caption' => $post->caption,
            'likes' =>   $postLikes,
            'comments' => $postComments,
            'images' => PostImages::Select('id', 'image')->Where('post_id', '=', $post->uuid)->get(),
        );


        $response = array(
            'status' => true, 'post_data' => $getPostStruct
        );
        return $response;
    }

    public function store($request)
    {
        $userAttr = auth()->user()->getAttributes();
        if (!$userAttr) {
            return array('status' => false, 'message' => 'Unauthorized');
        }

        DB::beginTransaction();

        try {
            $post = new Post();
            $post->uuid = Str::uuid();
            $post->user_id = $userAttr['uuid'];
            $post->title = $request->title;
            $post->caption = $request->caption;
            $post->save();

            $i = 0;
            $images = [];
            foreach ($request->image as $file) {
                $mimeType = $file->getClientMimeType();
                $path = $file->path();

                $imageName = "data:" . $mimeType . ";base64," . base64_encode(file_get_contents($path));


                $postImages = new PostImages();
                $postImages->post_id = $post->uuid;
                $postImages->image = $imageName;
                $postImages->order = $i;
                $postImages->save();

                array_push($images, $postImages);


                $i++;
            }

            $postContent = array(
                'id' => $post->uuid,
                'title' => $post->title,
                'caption' => $post->caption,
                'images' => $images
            );

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            Log::error("failed to save image");
            $response = array(
                'status' => false, 'message' => $ex->getPrevious()->getMessage()
            );
            return $response;
        }

        $response = array(
            'status' => true, 'data' => $postContent
        );
        return $response;
    }

    public function destroy($id)
    {
        $auth = auth()->user()->getAttributes();
        if (!$auth) {
            return array('status' => false, 'message' => 'Unauthorized!');
        }

        DB::beginTransaction();

        try {
            $postImages = PostImages::Where('post_id', '=', $id);
            if ($postImages->delete()) {
                $post = Post::Where('user_id', '=', $auth['uuid'])
                    ->Where('uuid', '=', $id);
                $post->delete();
            }

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $response = array(
                'status' => false, 'message' => $ex->getMessage()
            );
            return $response;
        }

        $response = array(
            'status' => true, 'message' => 'success deleted'
        );
        return $response;
    }

    public function postLikes($id)
    {
        $auth = auth()->user()->getAttributes();
        if (!$auth) {
            return array('status' => false, 'message' => 'Unauthorized!');
        }

        $exists = PostLikes::Where('post_id', '=', $id)
            ->Where('user_id', '=', $auth['uuid'])->get();

        if ($exists)
            return array('status' => false, 'message' => 'you have like this post before');

        DB::beginTransaction();

        try {
            $postLike = new PostLikes();
            $postLike->user_id = $auth['uuid'];
            $postLike->post_id = $id;
            $postLike->save();

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

    public function destroyLike($id)
    {
        $auth = auth()->user()->getAttributes();
        if (!$auth) {
            return array('status' => false, 'message' => 'Unauthorized!');
        }

        $exists = PostLikes::Where('post_id', '=', $id)
            ->Where('user_id', '=', $auth['uuid'])->get();

        if (!$exists)
            return array('status' => false, 'message' => 'no like post found for this user');

        DB::beginTransaction();

        try {
            $postLikes = PostLikes::Where('post_id', '=', $id)
                ->Where('user_id', '=', $auth['uuid']);

            $postLikes->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $response = array(
                'status' => false, 'message' => $ex->getMessage()
            );
            return $response;
        }

        $response = array(
            'status' => true, 'message' => 'success deleted'
        );
        return $response;
    }

    public function postComment($comment, $id)
    {
        $auth = auth()->user()->getAttributes();
        if (!$auth) {
            return array('status' => false, 'message' => 'Unauthorized!');
        }
        DB::beginTransaction();

        try {
            $postComment = new PostComments();
            $postComment->user_id = $auth['uuid'];
            $postComment->post_id = $id;
            $postComment->comment = $comment;
            $postComment->save();

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
}
