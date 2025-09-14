# Laravel Blog System

A comprehensive blog management system built with Laravel, featuring user authentication, post management, category organization, image uploads, and administrative controls.

## Features

### 📝 Blog Management
- **Post Creation:** Rich text editor with CKEditor integration
- **Category System:** Organize posts by categories with filtering
- **Image Uploads:** Post thumbnail and image management
- **Slug Generation:** SEO-friendly URLs with automatic slug creation
- **Related Posts:** Display related content based on categories
- **String Limiting:** Content truncation for previews and excerpts

### 👥 User Management
- **User Authentication:** Registration, login, and profile management
- **Role-Based Access:** Admin and regular user permissions
- **Author Attribution:** Post ownership and author display
- **User Dashboard:** Personal post management interface

### 🔍 Search & Navigation
- **Advanced Search:** Search through post titles and content
- **Category Filtering:** Browse posts by specific categories
- **Pagination:** Efficient content browsing with page navigation
- **Responsive Design:** Mobile-friendly card layouts

### 🛡️ Security & Validation
- **Form Validation:** Comprehensive input validation with custom error messages
- **CSRF Protection:** Secure form submissions
- **File Upload Security:** Safe image upload handling
- **Authorization:** User-specific edit/delete permissions

### 🎨 Frontend Features
- **Responsive Cards:** Bootstrap-based responsive post layouts
- **Time Display:** Human-readable timestamps (e.g., "2 hours ago")
- **Flash Messages:** Success and error notifications
- **Rich Content Display:** HTML content rendering with CKEditor

## Technology Stack

- **Backend:** Laravel 8+
- **Database:** MySQL/SQLite
- **Frontend:** Bootstrap 5, Blade Templates
- **Text Editor:** CKEditor 4
- **File Storage:** Laravel File System
- **Authentication:** Laravel Sanctum/Breeze
- **Validation:** Laravel Form Requests

## Installation & Setup

### Prerequisites
- PHP 8.0+
- Composer
- Laravel 8+
- MySQL/SQLite database
- Node.js (for frontend assets)

### 1. Project Setup
```bash
# Clone repository
git clone <repository-url>
cd laravel-blog

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate
```

### 2. Database Configuration
```bash
# Configure database in .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blog_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed
```

### 3. Storage Setup
```bash
# Create symbolic link for public storage
php artisan storage:link

# Create required directories
mkdir -p public/Post_Image
chmod 755 public/Post_Image
```

### 4. Start Development Server
```bash
# Start Laravel server
php artisan serve

# Compile assets
npm run dev
```

## Database Schema

### Posts Table Migration
```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('desc'); // Post content
    $table->string('slug')->unique();
    $table->string('imagePath')->nullable();
    $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreignId('category_id')->references('id')->on('categories')->onDelete('cascade');
    $table->timestamps();
});
```

### Categories Table Migration
```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->timestamps();
});
```

### Users Table (Extended)
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('mobile')->nullable();
    $table->string('alter_mobile')->nullable();
    $table->string('user_id')->unique()->nullable();
    $table->enum('role', ['0', '1'])->default('0'); // 0: User, 1: Admin
});
```

## Model Relationships

### Post Model
```php
class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'desc', 'slug', 'imagePath', 'user_id', 'category_id'
    ];

    // Post belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Post belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // String limiting for content preview
    public function limitContent($limit = 150)
    {
        return Str::limit($this->desc, $limit, '.......');
    }
}
```

### User Model
```php
class User extends Authenticatable
{
    // User has many posts
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // Check if user is admin
    public function isAdmin()
    {
        return $this->role === '1';
    }
}
```

### Category Model
```php
class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    // Category has many posts
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
```

## Controller Implementation

### PostController
```php
class PostController extends Controller
{
    // Display all posts with search and filtering
    public function index(Request $request)
    {
        $categories = Category::all();
        
        if ($request->search) {
            $posts = Post::where('title', 'like', '%' . $request->search . '%')
                        ->orWhere('desc', 'like', '%' . $request->search . '%')
                        ->latest()
                        ->paginate(4);
        } elseif ($request->category) {
            $posts = Category::where('name', $request->category)
                           ->firstOrFail()
                           ->posts()
                           ->paginate(4);
        } else {
            $posts = Post::latest()->paginate(4);
        }
        
        return view('frontend.blog', compact('posts', 'categories'));
    }

    // Store new post
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'desc' => 'required',
            'category_id' => 'required|exists:categories,id',
            'imagePath' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $post = new Post();
        $post->title = $request->title;
        $post->desc = $request->desc;
        $post->category_id = $request->category_id;
        $post->user_id = auth()->id();
        
        // Generate unique slug
        $post_id = Post::latest()->take(1)->first()->id ?? 0;
        $post->slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $request->title))) . '-' . ($post_id + 1);
        
        // Handle image upload
        if ($request->hasFile('imagePath')) {
            $image = $request->file('imagePath');
            $folder = 'Post_Image/';
            $imageName = time() . $image->getClientOriginalName();
            $image->move(public_path($folder), $imageName);
            $post->imagePath = $imageName;
        }
        
        $post->save();
        
        return redirect()->back()->with('msg', 'Post created successfully!');
    }

    // Show single post with related posts
    public function show($slug)
    {
        $post = Post::where('slug', $slug)->firstOrFail();
        $category = $post->category;
        $relatedPosts = $category->posts()
                              ->where('id', '!=', $post->id)
                              ->latest()
                              ->take(3)
                              ->get();
        
        return view('frontend.single-blog-post', compact('post', 'relatedPosts'));
    }

    // Edit post (with authorization)
    public function edit(Post $post)
    {
        if (auth()->user()->id !== $post->user->id) {
            abort(403);
        }
        
        $categories = Category::all();
        return view('backend.post.edit', compact('post', 'categories'));
    }

    // Delete post with image cleanup
    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        
        // Delete associated image
        $deleteOldImg = 'Post_Image/' . $post->imagePath;
        if (File::exists(public_path($deleteOldImg))) {
            File::delete(public_path($deleteOldImg));
        }
        
        $post->delete();
        
        return redirect()->back()->with('success', 'Post deleted successfully!');
    }
}
```

## Frontend Implementation

### Blog Post Cards
```blade
<!-- Responsive Blog Cards -->
<section>
    <div class="container-fluid">
        <div class="row">
            @forelse ($posts as $post)
            <div class="col-md-3 border-end p-0 text-center">
                <div class="card-body text-center">
                    @auth
                        @if (auth()->user()->id === $post->user->id)
                            <div class="post-buttons">
                                <a href="{{ route('editPost', $post) }}" class="btn btn-sm btn-primary">Edit</a>
                                <form action="{{ route('destroyPost', $post) }}" method="post" class="d-inline">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        @endif
                    @endauth
                    
                    <a href="{{ route('AllPost', $post->slug) }}">
                        <img width="250px" height="200px" 
                             src="{{ asset('Post_Image/' . $post->imagePath) }}" 
                             alt="Post image">
                        <p>
                            {{ $post->created_at->diffForHumans() }}
                            <span>Written By {{ $post->user->name }}</span>
                        </p>
                        <h4>{{ $post->title }}</h4>
                        <p>{{ Str::limit($post->desc, 150, '.......') }}</p>
                    </a>
                </div>
            </div>
            @empty
                <p class="text-center">No posts found!</p>
            @endforelse
        </div>
    </div>
</section>

<!-- Pagination -->
<div class="d-flex justify-content-center">
    {{ $posts->links() }}
</div>
```

### Search and Category Filter
```blade
<!-- Search Form -->
<form action="{{ route('blog') }}" method="get" class="mb-4">
    <div class="input-group">
        <input class="form-control" name="search" type="search" 
               value="{{ request('search') }}" placeholder="Search posts...">
        <button class="btn btn-primary" type="submit">
            <i class="fas fa-search"></i> Search
        </button>
        <a href="{{ route('blog') }}" class="btn btn-success ms-2">Reset</a>
    </div>
</form>

<!-- Category Filter -->
<div class="categories">
    <ul class="list-unstyled">
        @foreach ($categories as $category)
            <li>
                <a href="{{ route('blog', ['category' => $category->name]) }}">
                    {{ $category->name }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
```

## CKEditor Integration

### Blade Template
```blade
<!-- Include CKEditor CDN -->
<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>

<!-- Textarea for content -->
<textarea id="editor" name="desc" class="form-control">{{ old('desc', $post->desc ?? '') }}</textarea>

<!-- Initialize CKEditor -->
<script>
    CKEDITOR.replace('editor', {
        height: 300,
        filebrowserUploadUrl: "{{ route('ckeditor.upload', ['_token' => csrf_token()]) }}",
        filebrowserUploadMethod: 'form'
    });
</script>
```

## Form Validation

### Custom Validation Messages
```php
// In resources/lang/en/validation.php
'custom' => [
    'imagePath' => [
        'image' => 'The file must be an image',
        'required' => 'Please select an image for your post'
    ],
    'desc' => [
        'required' => 'The description field is required',
    ],
    'title' => [
        'required' => 'Post title is required',
        'max' => 'Title cannot exceed 255 characters'
    ]
],
```

### Blade Error Display
```blade
<input type="text" class="form-control" name="title" value="{{ old('title') }}">
@error('title')
    <div class="text-danger">{{ $message }}</div>
@enderror

<textarea id="editor" name="desc">{{ old('desc') }}</textarea>
@error('desc')
    <div class="text-danger">{{ $message }}</div>
@enderror
```

## Authentication & Authorization

### Route Protection
```php
// Protect routes with authentication
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PostController::class, 'dashboard'])->name('dashboard');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
});

// Admin-only routes
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
});
```

### Blade Authorization
```blade
<!-- Show content only to authenticated users -->
@auth
    <a href="{{ route('posts.create') }}" class="btn btn-primary">Create Post</a>
@endauth

<!-- Show content only to guests -->
@guest
    <a href="{{ route('login') }}">Login</a>
    <a href="{{ route('register') }}">Register</a>
@endguest

<!-- Admin-only content -->
@auth
    @if(Auth::user()->role === '1')
        <a href="{{ route('admin.dashboard') }}">Admin Panel</a>
    @endif
@endauth
```

## File Upload Handling

### Image Upload Method
```php
private function handleImageUpload($request, $post = null)
{
    if ($request->hasFile('imagePath')) {
        // Delete old image if updating
        if ($post && $post->imagePath) {
            $oldImagePath = public_path('Post_Image/' . $post->imagePath);
            if (File::exists($oldImagePath)) {
                File::delete($oldImagePath);
            }
        }
        
        $image = $request->file('imagePath');
        $folder = 'Post_Image/';
        $imageName = time() . '_' . $image->getClientOriginalName();
        $image->move(public_path($folder), $imageName);
        
        return $imageName;
    }
    
    return $post ? $post->imagePath : null;
}
```

## Admin Panel Features

### User Management
```php
class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search ?? "";
        
        $users = User::query();
        
        if ($request->search) {
            $users->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('mobile', 'like', '%' . $request->search . '%');
        }
        
        $userData = $users->latest()->get();
        
        return view('backend.user.manage', compact('userData', 'search'));
    }
}
```

### Admin User Management Blade
```blade
<form action="{{ route('manageUser') }}" method="get">
    <div class="input-group">
        <input class="form-control" name="search" type="search" 
               value="{{ $search }}" placeholder="Name, Phone or Email">
        <button class="btn btn-primary" type="submit">
            <i class="fas fa-search"></i>
        </button>
        <a href="{{ route('manageUser') }}" class="btn btn-success ms-2">Reset</a>
    </div>
</form>

<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Mobile</th>
            @auth
                @if(Auth::user()->role === '1')
                    <th>Actions</th>
                @endif
            @endauth
        </tr>
    </thead>
    <tbody>
        @foreach($userData as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->mobile }}</td>
            @auth
                @if(Auth::user()->role === '1')
                <td>
                    <a href="{{ route('editUser', $user->id) }}" class="btn btn-sm btn-primary">Edit</a>
                    <a href="{{ route('deleteUser', $user->id) }}" class="btn btn-sm btn-danger">Delete</a>
                </td>
                @endif
            @endauth
        </tr>
        @endforeach
    </tbody>
</table>
```

## Deployment

### Production Configuration
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### Environment Variables
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database configuration
DB_CONNECTION=mysql
DB_HOST=your-host
DB_PORT=3306
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Implement new blog features or improvements
4. Add appropriate tests
5. Submit a pull request

## License

This Laravel blog system is open source and available under the [MIT License](LICENSE).

## Support

For support and questions:
- Check Laravel documentation for framework-specific issues
- Review CKEditor documentation for text editor integration
- Test file upload functionality with various image formats
- Verify database relationships and migrations

---

**Note:** This blog system includes comprehensive features for content management, user authentication, and administrative controls. Ensure proper security measures are implemented for production deployment.
