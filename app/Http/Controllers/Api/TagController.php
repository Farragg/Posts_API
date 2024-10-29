<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tags\TagRequest;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tags = Tag::all();

        return response()->json(['data' => $tags], 200);
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
    public function store(TagRequest $request)
    {
        $tag = Tag::create([
            'name' => $request->name,
        ]);

        return response()->json(['message'=> 'Tag created successfully','data' => $tag], 200);

    }

    /**
     * Display the specified resource.
     */
    public function show(Tag $tag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tag $tag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string'
        ]);

        if($validator->fails())
            return response()->json(['data' => null, 'errors' => $validator->errors(), 400]);

        $tag = Tag::find($id);

        if(!$tag) {
            return response()->json(['data' => null, 'message' => 'The tag is Not Found', 404]);
        }

        $tag->update($request->all());
        return response()->json(['message'=> 'Tag updated successfully','data' => $tag], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tag = Tag::find($id);

        if(!$tag)
            return response()->json(['message' => 'The tag is Not Found', 404]);

        $tag->delete();

        return response()->json(['message'=> 'Tag deleted successfully'], 200);
    }
}
