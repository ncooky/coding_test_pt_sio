<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Utils\Response;
use App\Services\PostService;

class PostController extends Controller
{
    use Response;
    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function ping()
    {
        return $this->pingResponse();
    }

    /** 
     * Show User Posts
     */
    public function index()
    {

        $posts = $this->postService->index();
        switch ($posts['status']) {
            case false:
                return $this->responseError($posts['message'], 500);
            default:
                return $this->responseData($posts['post_data']);
        }
    }

    /** 
     * Show User Post by ID
     */
    public function show($id)
    {

        $posts = $this->postService->show($id);
        switch ($posts['status']) {
            case false:
                return $this->responseError($posts['message'], 500);
            default:
                return $this->responseData($posts['post_data']);
        }
    }

    /** 
     * Save new Post
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
            'caption' => 'required',
            'image' => 'required',
            'image.*' => '|image|mimes:png,jpg,jpeg|max:2048'
        ]);

        $post = $this->postService->store($request);
        switch ($post['status']) {
            case false:
                return $this->responseError($post['message'], 500);
            default:
                return $this->responseData($post['data']);
        }
    }

    /**
     * Destroy Post
     */
    public function destroy($id)
    {
        $post = $this->postService->destroy($id);
        switch ($post['status']) {
            case false:
                return $this->responseError($post['message'], 500);
            default:
                return $this->responseData(['message' => "success deleted"]);
        }
    }

    /** 
     * Update Post By ID
     */
    public function update(Request $request, $id)
    {
        $post = auth()->user()->posts()->find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 400);
        }

        $updated = $post->fill($request->all())->save();

        if ($updated)
            return response()->json([
                'success' => true
            ]);
        else
            return response()->json([
                'success' => false,
                'message' => 'Post can not be updated'
            ], 500);
    }

    /**
     * Do Like Post
     */
    public function like($id)
    {
        $post = $this->postService->postLikes($id);
        switch ($post['status']) {
            case false:
                return $this->responseError($post['message'], 500);
            default:
                return $this->responseData(['message' => "success updated"]);
        }
    }

    /**
     * Do Unlike Post
     */
    public function unlike($id)
    {
        $post = $this->postService->destroyLike($id);
        switch ($post['status']) {
            case false:
                return $this->responseError($post['message'], 500);
            default:
                return $this->responseData(['message' => "success dislike post"]);
        }
    }

    /**
     * Do Comment Post
     */
    public function comments(Request $request, $id)
    {
        $data = $request->only('comment');
        $post = $this->postService->postComment($data['comment'], $id);
        switch ($post['status']) {
            case false:
                return $this->responseError($post['message'], 500);
            default:
                return $this->responseData(['message' => "success updated"]);
        }
    }
}
