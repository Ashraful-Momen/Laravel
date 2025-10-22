*********if user loggedin then set the token in header : 

#thunder client : 
-----------------
url => domain_name/api/route_name

header=> 
        Content-type : application/json // for upload image , Content-Type:  multipart/form-data //no need content type for uploading image . method must be : post method . 
        Authorization: Bearer Past_the_token_id

body => for the post method send data in json formate. 



# app > http > middleware > kernal.php . 
---------------------------------------------
>>> uncomment the api =[
scantum::class, 
]

--------------------------------------------
api> route: 
-------------
Route::get('/post',[CrudController::class,'show']);
Route::post('/post_store',[CrudController::class,'store']);


#controller : 
--------------------
<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Models\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CrudController extends Controller
{
    //

    public function show(){
        $post = Post::all();
        $response = [
            'post'=> $post,
            'msg' => 'Fetcing post successfully'
        ];
        return response()->json($response,200);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'title' => 'required',
            'user_id' => 'required',
        ]);

        if($validator->fails()) {

            return response()->json([

                'success' => false,

                'message' => $validator->errors()->first()

            ]);

        }

        $post = Post::create([

            'title'     => $request->title,
            'user_id'    => $request->user_id,

        ]);



        return response()->json([

            'success' => true,

            'post' => $post,

            'message' => 'Post register successfully.'
        ],201);
    }
}



-----------------------------------------------------------
#for uploading image: 
-----------------------------------------------------------
 public function upload(Request $request)
    {
        // Validate the request
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle the uploaded file
        if ($request->file('image')) {
            $file = $request->file('image');
            $path = $file->store('images', 'public');

            // Return a response
            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully!',
                'path' => $path
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Image upload failed!'
        ], 500);
    }

------------------------------------------------------ Image save and existing image delete ------------------------------------------

  
     // 🧾 Store new post with image
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'user_id' => 'required|integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $data = $request->only(['title', 'user_id']);

        // save image with Storage::
        if ($request->hasFile('image')) {
            // store in storage/app/public/posts
            $file = $request->file('image');
            $path = $file->store('posts', 'public');
            $data['image'] = $path; // save only path, not URL
        }

        $post = Post::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully!',
            'post' => $post
        ], 201);
    }

    // 🗑️ Delete post + image
    public function destroy($id)
    {
        $post = Post::find($id);

        if (! $post) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        // delete image using Storage::
        if ($post->image && Storage::disk('public')->exists($post->image)) {
            Storage::disk('public')->delete($post->image);
        }

        $post->delete();

        return response()->json(['success' => true, 'message' => 'Deleted successfully!']);
    }

------------------------------------------------------ Search , Filter and Pagination ------------------------------------------------


public function index(Request $request)
{
    $query = Post::query();

    // 🔍 Search by title
    if ($request->has('q')) {
        $query->where('title', 'like', '%' . $request->q . '%');
    }

    // 🎯 Filter by user_id
    if ($request->has('user_id')) {
        $query->where('user_id', $request->user_id);
    }

    // 📄 Pagination (default 10 per page)
    $perPage = $request->get('per_page', 10);
    $posts = $query->orderBy('id', 'desc')->paginate($perPage);

    // 🧾 Custom pagination response
    return response()->json([
        'success' => true,
        'current_page' => $posts->currentPage(),
        'first_page' => 1,
        'last_page' => $posts->lastPage(),
        'per_page' => $posts->perPage(),
        'total' => $posts->total(),
        'data' => $posts->items(), // only post data
    ]);
}


----------------------------------------------------------Custom validation error----------------------------------------------------------------------------------
  public function store(Request $request)
{
    // 🧾 Custom error messages
    $messages = [
        'title.required' => 'Please enter a title for your post.',
        'title.string'   => 'Title must be a valid string.',
        'title.max'      => 'Title should not be longer than 255 characters.',

        'user_id.required' => 'User ID is required.',
        'user_id.integer'  => 'User ID must be a valid number.',

        'image.image' => 'The uploaded file must be an image.',
        'image.mimes' => 'Image must be in JPG, JPEG, or PNG format.',
        'image.max'   => 'Image size should not exceed 2MB.'
    ];

    // ✅ Validate input with custom messages
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'user_id' => 'required|integer',
        'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
    ], $messages);

    $data = $request->only(['title', 'user_id']);

    // 💾 Save image using Storage::
    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $path = $file->store('posts', 'public'); // saves to storage/app/public/posts
        $data['image'] = $path;
    }

    $post = Post::create($data);

    return response()->json([
        'success' => true,
        'message' => 'Post created successfully!',
        'post' => $post
    ], 201);
}


  
