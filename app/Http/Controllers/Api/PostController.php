<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\PostRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
//        $posts = $user->posts()->with('tags')->get();
        $posts = $user->posts()->with('tags')->orderBy('pinned', 'desc')->get();

        return response()->json(['posts'=> $posts], 200);
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
    public function store(PostRequest $request)
    {
        $user = $request->user();

        if ($request->hasFile('cover_image')){

            $file = $request->file('cover_image');

            $imageName = time() .'_'. $file->getClientOriginalName();
            $imgPath = $file->storeAs('images/coverImages', $imageName, 'images');

            $post = $user->posts()->create([
                'title' => $request->title,
                'body' => $request->body,
                'cover_image' => $imageName,
                'pinned' => $request->pinned,
                'user_id' => $user->id,
            ]);

            if ($request->has('tag_ids')){
                // Attach tags
                $post->tags()->attach($request->tag_ids);
            }

            return response()->json(['message'=> 'Post created successfully','data' => $post], 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $post = Post::find($id);

        if(!$post) {
            return response()->json(['data' => null, 'message' => 'The Post is Not Found', 404]);
        }

        if ($post->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $post], 200);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'body' => 'required|string',
            'cover_image' => 'image|mimes:jpeg,jpg,png',
            'pinned' => 'required|boolean',
            'tag_id' => 'nullable|exists:tags,id'
        ]);

        if($validator->fails())
            return response()->json(['data' => null, 'errors' => $validator->errors(), 400]);

        $post = Post::find($id);

        if(!$post) {
            return response()->json(['data' => null, 'message' => 'The Post is Not Found', 404]);
        }

        if ($post->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($request->hasFile('cover_image')){

            // Delete old photo
            if ($post->cover_image) {
                $old_img = $post->cover_image;
                $old_img_path = public_path().$old_img;
                unlink($old_img_path);
            }

            $file = $request->file('cover_image');

            $imageName =time() .'_'.$file->getClientOriginalName();
            $imagePath = public_path('images/coverImages/');

            $file->move($imagePath, $imageName);

            $post->cover_image = $imageName;
        }

        $post->title = $request->title;
        $post->body = $request->body;
        $post->pinned = $request->pinned;

        // replace existing tags with new ones
        if ($request->filled('tag_ids')) {
            $post->tags()->sync($request->tag_ids);
        }

        $post->save();

        return response()->json(['message'=> 'Post updated successfully', 'data' => $post], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        $post = Post::find($id);

        if(!$post) {
            return response()->json(['data' => null, 'message' => 'The Post is Not Found', 404]);
        }

        if ($post->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete old photo
        if ($post->cover_image) {
                $old_img = $post->cover_image;
                $old_img_path = public_path('images/coverImages/').$old_img;
                unlink($old_img_path);
            }

        $post->delete();

        return response()->json(['message' => 'Post Deleted Successfully'], 200);
    }


    //Get Trashed Posts Function
    public function trashed (Request $request)
    {
        $user = $request->user();

        $trashed_posts = $user->posts()->onlyTrashed()->get();

        if ($trashed_posts->isEmpty()) {
            return response()->json(['message' => 'Post is not deleted or already restored'], 400);
        }

        return response()->json(['data'=> $trashed_posts], 200);
    }

    //Restore Trashed Post Function
    public function restore (Request $request, string $id)
    {
        $user = $request->user();

        $post = Post::withTrashed()->find($id);

        if(!$post) {
            return response()->json(['data' => null, 'message' => 'The Post is Not Found', 404]);
        }

        if ($post->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($post->trashed()){
            $restored_post = $user->posts()->restore();

            return response()->json(['message' => 'Post restored successfully', 'data'=> $restored_post], 200);
        }

        return response()->json(['message' => 'Post is not deleted or already restored'], 400);

    }

    public function stats ()
    {
        $cacheKey = 'stats';

        // Check if stats are cached
        $stats = Cache::remember($cacheKey, 3600, function () {
            return $this->getStats();
        });

        return response()->json($stats);
    }

    public function getStats()
    {
        $numberOfUsers = User::count();
        $numberOfPosts = Post::count();
        $usersWithZeroPosts = User::doesntHave('posts')->count();

        return [
            'number_of_users' => $numberOfUsers,
            'number_of_posts' => $numberOfPosts,
            'users_with_zero_posts' => $usersWithZeroPosts,
        ];
    }
}
