return strip_tags($content, $allowedTags);
    }
}

// app/Services/PostImageService.php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class PostImageService
{
    private $disk;
    private $basePath;
    
    public function __construct()
    {
        $this->disk = Storage::disk('public');
        $this->basePath = 'posts/images';
    }
    
    public function uploadFeaturedImage($image, $postId)
    {
        $filename = $this->generateFilename($image, $postId);
        $path = "{$this->basePath}/{$filename}";
        
        // Resize and optimize image
        $optimizedImage = $this->optimizeImage($image);
        
        // Store the image
        $this->disk->put($path, $optimizedImage);
        
        // Generate thumbnail
        $thumbnailPath = $this->generateThumbnail($image, $postId);
        
        return [
            'original' => $path,
            'thumbnail' => $thumbnailPath,
            'url' => $this->disk->url($path),
            'thumbnail_url' => $this->disk->url($thumbnailPath)
        ];
    }
    
    public function deleteFeaturedImage($imagePath)
    {
        if ($this->disk->exists($imagePath)) {
            $this->disk->delete($imagePath);
            
            // Also delete thumbnail
            $thumbnailPath = str_replace('/images/', '/thumbnails/', $imagePath);
            if ($this->disk->exists($thumbnailPath)) {
                $this->disk->delete($thumbnailPath);
            }
            
            return true;
        }
        
        return false;
    }
    
    private function generateFilename($image, $postId)
    {
        $extension = $image->getClientOriginalExtension();
        return "post_{$postId}_" . uniqid() . ".{$extension}";
    }
    
    private function optimizeImage($image)
    {
        $img = Image::make($image);
        
        // Resize if too large
        if ($img->width() > 1200) {
            $img->resize(1200, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }
        
        // Optimize quality
        return $img->encode('jpg', 85)->stream();
    }
    
    private function generateThumbnail($image, $postId)
    {
        $filename = $this->generateFilename($image, $postId);
        $thumbnailPath = "posts/thumbnails/{$filename}";
        
        $thumbnail = Image::make($image)
            ->resize(300, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('jpg', 80);
        
        $this->disk->put($thumbnailPath, $thumbnail);
        
        return $thumbnailPath;
    }
}

// app/Services/PostSlugService.php
namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Post;

class PostSlugService
{
    public function generateSlug($title, $postId = null)
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->slugExists($slug, $postId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    public function updateSlug($postId, $newTitle)
    {
        $newSlug = $this->generateSlug($newTitle, $postId);
        
        Post::where('id', $postId)->update(['slug' => $newSlug]);
        
        return $newSlug;
    }
    
    private function slugExists($slug, $excludeId = null)
    {
        $query = Post::where('slug', $slug);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}

// app/Services/PostSEOService.php
namespace App\Services;

use Illuminate\Support\Str;

class PostSEOService
{
    public function generateMetaDescription($content, $customDescription = null)
    {
        if ($customDescription) {
            return $this->truncateDescription($customDescription);
        }
        
        // Extract first paragraph and clean it
        $firstParagraph = $this->extractFirstParagraph($content);
        $cleanText = strip_tags($firstParagraph);
        
        return $this->truncateDescription($cleanText);
    }
    
    public function generateKeywords($title, $content, $tags = [])
    {
        $keywords = [];
        
        // Extract keywords from title
        $titleWords = $this->extractKeywords($title);
        $keywords = array_merge($keywords, $titleWords);
        
        // Extract keywords from content
        $contentWords = $this->extractKeywords($content, 10);
        $keywords = array_merge($keywords, $contentWords);
        
        // Add tags
        $keywords = array_merge($keywords, $tags);
        
        // Remove duplicates and return
        return array_unique(array_filter($keywords));
    }
    
    public function generateOpenGraphData($post)
    {
        return [
            'og:title' => $post->title,
            'og:description' => $post->meta_description,
            'og:image' => $post->featured_image_url,
            'og:url' => route('posts.show', $post->slug),
            'og:type' => 'article',
            'article:author' => $post->author->name,
            'article:published_time' => $post->published_at->toISOString(),
            'article:section' => $post->category->name
        ];
    }
    
    private function extractFirstParagraph($content)
    {
        // Extract content between first <p> tags
        preg_match('/<p[^>]*>(.*?)<\/p>/s', $content, $matches);
        
        return $matches[1] ?? Str::limit(strip_tags($content), 200);
    }
    
    private function truncateDescription($text, $maxLength = 160)
    {
        return Str::limit($text, $maxLength);
    }
    
    private function extractKeywords($text, $limit = 5)
    {
        $words = str_word_count(strtolower(strip_tags($text)), 1);
        
        // Filter out common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = array_diff($words, $stopWords);
        
        // Filter words that are at least 3 characters
        $words = array_filter($words, function($word) {
            return strlen($word) >= 3;
        });
        
        // Count word frequency
        $wordCounts = array_count_values($words);
        arsort($wordCounts);
        
        return array_slice(array_keys($wordCounts), 0, $limit);
    }
}

// app/Services/PostCacheService.php
namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PostCacheService
{
    private $cachePrefix = 'post_';
    private $defaultTtl = 3600; // 1 hour
    
    public function cachePost($post)
    {
        $cacheKey = $this->getCacheKey('single', $post->id);
        Cache::put($cacheKey, $post, $this->defaultTtl);
        
        // Also cache by slug
        $slugCacheKey = $this->getCacheKey('slug', $post->slug);
        Cache::put($slugCacheKey, $post, $this->defaultTtl);
    }
    
    public function getCachedPost($identifier, $type = 'id')
    {
        $cacheKey = $this->getCacheKey($type === 'slug' ? 'slug' : 'single', $identifier);
        return Cache::get($cacheKey);
    }
    
    public function cachePostList($key, $posts, $ttl = null)
    {
        $cacheKey = $this->getCacheKey('list', $key);
        Cache::put($cacheKey, $posts, $ttl ?: $this->defaultTtl);
    }
    
    public function getCachedPostList($key)
    {
        $cacheKey = $this->getCacheKey('list', $key);
        return Cache::get($cacheKey);
    }
    
    public function invalidatePost($postId, $slug = null)
    {
        // Clear single post cache
        Cache::forget($this->getCacheKey('single', $postId));
        
        if ($slug) {
            Cache::forget($this->getCacheKey('slug', $slug));
        }
        
        // Clear related list caches
        $this->invalidateListCaches();
    }
    
    public function invalidateListCaches()
    {
        $listKeys = [
            'recent_posts',
            'popular_posts',
            'featured_posts',
            'category_posts_*',
            'tag_posts_*',
            'author_posts_*'
        ];
        
        foreach ($listKeys as $pattern) {
            if (str_contains($pattern, '*')) {
                // For wildcard patterns, we'd need to track keys or use tags
                Cache::tags(['post_lists'])->flush();
                break;
            } else {
                Cache::forget($this->getCacheKey('list', $pattern));
            }
        }
    }
    
    private function getCacheKey($type, $identifier)
    {
        return $this->cachePrefix . $type . '_' . $identifier;
    }
}

// app/Services/PostNotificationService.php
namespace App\Services;

use App\Models\User;
use App\Notifications\NewPostPublished;
use App\Notifications\PostUpdated;
use Illuminate\Support\Facades\Notification;

class PostNotificationService
{
    public function notifySubscribersOfNewPost($post)
    {
        $subscribers = User::where('subscribed_to_posts', true)->get();
        
        Notification::send($subscribers, new NewPostPublished($post));
        
        return $subscribers->count();
    }
    
    public function notifyAuthorOfPublish($post)
    {
        $post->author->notify(new PostUpdated($post, 'published'));
    }
    
    public function notifyFollowersOfNewPost($post)
    {
        $followers = $post->author->followers;
        
        if ($followers->count() > 0) {
            Notification::send($followers, new NewPostPublished($post));
        }
        
        return $followers->count();
    }
    
    public function notifyModeratorsOfDraft($post)
    {
        $moderators = User::where('role', 'moderator')->get();
        
        Notification::send($moderators, new PostUpdated($post, 'submitted_for_review'));
        
        return $moderators->count();
    }
}

// app/Services/PostSearchIndexingService.php
namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\DB;

class PostSearchIndexingService
{
    public function indexPost($post)
    {
        $searchableContent = $this->prepareSearchableContent($post);
        
        // Update search index table
        DB::table('post_search_index')->updateOrInsert(
            ['post_id' => $post->id],
            [
                'title' => $post->title,
                'content' => $searchableContent,
                'tags' => implode(' ', $post->tags->pluck('name')->toArray()),
                'category' => $post->category->name,
                'author' => $post->author->name,
                'updated_at' => now()
            ]
        );
    }
    
    public function removeFromIndex($postId)
    {
        DB::table('post_search_index')->where('post_id', $postId)->delete();
    }
    
    public function reindexAll()
    {
        $posts = Post::with(['category', 'author', 'tags'])->chunk(100, function ($posts) {
            foreach ($posts as $post) {
                $this->indexPost($post);
            }
        });
    }
    
    private function prepareSearchableContent($post)
    {
        // Strip HTML and extract searchable text
        $content = strip_tags($post->content);
        
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Limit content length for indexing
        return substr($content, 0, 2000);
    }
}

// app/Repositories/PostRepository.php
namespace App\Repositories;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

class PostRepository
{
    public function create($data)
    {
        return Post::create($data);
    }
    
    public function findById($id)
    {
        return Post::with(['category', 'author', 'tags'])->find($id);
    }
    
    public function findBySlug($slug)
    {
        return Post::with(['category', 'author', 'tags'])
                   ->where('slug', $slug)
                   ->first();
    }
    
    public function update($id, $data)
    {
        $post = Post::findOrFail($id);
        $post->update($data);
        return $post->fresh();
    }
    
    public function delete($id)
    {
        return Post::findOrFail($id)->delete();
    }
    
    public function getPublishedPosts($perPage = 15)
    {
        return Post::with(['category', 'author', 'tags'])
                   ->where('status', 'published')
                   ->where('published_at', '<=', now())
                   ->orderBy('published_at', 'desc')
                   ->paginate($perPage);
    }
    
    public function getPostsByCategory($categoryId, $perPage = 15)
    {
        return Post::with(['category', 'author', 'tags'])
                   ->where('category_id', $categoryId)
                   ->where('status', 'published')
                   ->where('published_at', '<=', now())
                   ->orderBy('published_at', 'desc')
                   ->paginate($perPage);
    }
    
    public function getPostsByTag($tagId, $perPage = 15)
    {
        return Post::with(['category', 'author', 'tags'])
                   ->whereHas('tags', function ($query) use ($tagId) {
                       $query->where('tag_id', $tagId);
                   })
                   ->where('status', 'published')
                   ->where('published_at', '<=', now())
                   ->orderBy('published_at', 'desc')
                   ->paginate($perPage);
    }
    
    public function searchPosts($query, $perPage = 15)
    {
        return Post::with(['category', 'author', 'tags'])
                   ->where('status', 'published')
                   ->where(function ($q) use ($query) {
                       $q->where('title', 'LIKE', "%{$query}%")
                         ->orWhere('content', 'LIKE', "%{$query}%");
                   })
                   ->orderBy('published_at', 'desc')
                   ->paginate($perPage);
    }
}

// app/Services/PostService.php - ORCHESTRATOR CLASS
namespace App\Services;

use App\Repositories\PostRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostService
{
    private $postRepository;
    private $validationService;
    private $imageService;
    private $slugService;
    private $seoService;
    private $cacheService;
    private $notificationService;
    private $searchIndexingService;
    
    public function __construct(
        PostRepository $postRepository,
        PostValidationService $validationService,
        PostImageService $imageService,
        PostSlugService $slugService,
        PostSEOService $seoService,
        PostCacheService $cacheService,
        PostNotificationService $notificationService,
        PostSearchIndexingService $searchIndexingService
    ) {
        $this->postRepository = $postRepository;
        $this->validationService = $validationService;
        $this->imageService = $imageService;
        $this->slugService = $slugService;
        $this->seoService = $seoService;
        $this->cacheService = $cacheService;
        $this->notificationService = $notificationService;
        $this->searchIndexingService = $searchIndexingService;
    }
    
    public function createPost($data, $authorId)
    {
        DB::beginTransaction();
        
        try {
            // Each responsibility delegated to specialized service
            $validatedData = $this->validationService->validatePostData($data);
            
            // Generate slug
            $slug = $this->slugService->generateSlug($validatedData['title']);
            
            // Handle featured image upload
            $imageData = null;
            if (isset($validatedData['featured_image'])) {
                $imageData = $this->imageService->uploadFeaturedImage(
                    $validatedData['featured_image'], 
                    'temp_' . uniqid()
                );
            }
            
            // Generate SEO data
            $metaDescription = $this->seoService->generateMetaDescription(
                $validatedData['content'],
                $validatedData['meta_description']
            );
            
            // Create post
            $postData = array_merge($validatedData, [
                'author_id' => $authorId,
                'slug' => $slug,
                'meta_description' => $metaDescription,
                'featured_image' => $imageData['original'] ?? null,
                'featured_image_thumbnail' => $imageData['thumbnail'] ?? null,
                'status' => 'draft'
            ]);
            
            $post = $this->postRepository->create($postData);
            
            // Update image paths with actual post ID
            if ($imageData) {
                $updatedImageData = $this->imageService->uploadFeaturedImage(
                    $validatedData['featured_image'],
                    $post->id
                );
                
                $post->update([
                    'featured_image' => $updatedImageData['original'],
                    'featured_image_thumbnail' => $updatedImageData['thumbnail']
                ]);
            }
            
            // Cache the post
            $this->cacheService->cachePost($post);
            
            // Index for search
            $this->searchIndexingService->indexPost($post);
            
            DB::commit();
            
            Log::info('Post created successfully', [
                'post_id' => $post->id,
                'author_id' => $authorId,
                'title' => $post->title
            ]);
            
            return $post;
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Post creation failed', [
                'error' => $e->getMessage(),
                'author_id' => $authorId,
                'data' => $data
            ]);
            
            throw $e;
        }
    }
    
    public function publishPost($postId)
    {
        $post = $this->postRepository->findById($postId);
        
        if (!$post) {
            throw new \Exception('Post not found');
        }
        
        if ($post->status === 'published') {
            throw new \Exception('Post is already published');
        }
        
        DB::beginTransaction();
        
        try {
            // Update post status
            $updatedPost = $this->postRepository->update($postId, [
                'status' => 'published',
                'published_at' => now()
            ]);
            
            // Update cache
            $this->cacheService->cachePost($updatedPost);
            $this->cacheService->invalidateListCaches();
            
            // Update search index
            $this->searchIndexingService->indexPost($updatedPost);
            
            // Send notifications
            $this->notificationService->notifyAuthorOfPublish($updatedPost);
            $this->notificationService->notifySubscribersOfNewPost($updatedPost);
            $this->notificationService->notifyFollowersOfNewPost($updatedPost);
            
            DB::commit();
            
            Log::info('Post published successfully', [
                'post_id' => $postId,
                'title' => $updatedPost->title
            ]);
            
            return $updatedPost;
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Post publishing failed', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    public function updatePost($postId, $data)
    {
        $post = $this->postRepository->findById($postId);
        
        if (!$post) {
            throw new \Exception('Post not found');
        }
        
        DB::beginTransaction();
        
        try {
            $validatedData = $this->validationService->validateUpdateData($data, $postId);
            
            // Update slug if title changed
            if ($validatedData['title'] !== $post->title) {
                $validatedData['slug'] = $this->slugService->generateSlug(
                    $validatedData['title'], 
                    $postId
                );
            }
            
            // Handle new featured image
            if (isset($validatedData['featured_image'])) {
                // Delete old image
                if ($post->featured_image) {
                    $this->imageService->deleteFeaturedImage($post->featured_image);
                }
                
                // Upload new image
                $imageData = $this->imageService->uploadFeaturedImage(
                    $validatedData['featured_image'],
                    $postId
                );
                
                $validatedData['featured_image'] = $imageData['original'];
                $validatedData['featured_image_thumbnail'] = $imageData['thumbnail'];
            }
            
            // Update SEO data
            $validatedData['meta_description'] = $this->seoService->generateMetaDescription(
                $validatedData['content'],
                $validatedData['meta_description']
            );
            
            $updatedPost = $this->postRepository->update($postId, $validatedData);
            
            // Update cache
            $this->cacheService->invalidatePost($postId, $post->slug);
            $this->cacheService->cachePost($updatedPost);
            
            // Update search index
            $this->searchIndexingService->indexPost($updatedPost);
            
            DB::commit();
            
            Log::info('Post updated successfully', [
                'post_id' => $postId,
                'title' => $updatedPost->title
            ]);
            
            return $updatedPost;
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Post update failed', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    public function deletePost($postId)
    {
        $post = $this->postRepository->findById($postId);
        
        if (!$post) {
            throw new \Exception('Post not found');
        }
        
        DB::beginTransaction();
        
        try {
            // Delete featured image
            if ($post->featured_image) {
                $this->imageService->deleteFeaturedImage($post->featured_image);
            }
            
            // Remove from search index
            $this->searchIndexingService->removeFromIndex($postId);
            
            // Clear cache
            $this->cacheService->invalidatePost($postId, $post->slug);
            
            // Delete post
            $this->postRepository->delete($postId);
            
            DB::commit();
            
            Log::info('Post deleted successfully', [
                'post_id' => $postId,
                'title' => $post->title
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Post deletion failed', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    public function getPost($identifier, $type = 'id')
    {
        // Try cache first
        $cachedPost = $this->cacheService->getCachedPost($identifier, $type);
        
        if ($cachedPost) {
            return $cachedPost;
        }
        
        // Get from database
        $post = $type === 'slug' 
            ? $this->postRepository->findBySlug($identifier)
            : $this->postRepository->findById($identifier);
        
        if ($post) {
            $this->cacheService->cachePost($post);
        }
        
        return $post;
    }
}

// app/Http/Controllers/PostController.php
namespace App\Http\Controllers;

use App\Services\PostService;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Http\Request;

class PostController extends Controller
{
    private $postService;
    
    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }
    
    public function store(CreatePostRequest $request)
    {
        try {
            $post = $this->postService->createPost(
                $request->validated(),
                auth()->id()
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'post' => $post
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    public function show($slug)
    {
        try {
            $post = $this->postService->getPost($slug, 'slug');
            
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'post' => $post
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function update(UpdatePostRequest $request, $id)
    {
        try {
            $post = $this->postService->updatePost($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully',
                'post' => $post
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    public function destroy($id)
    {
        try {
            $this->postService->deletePost($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    public function publish($id)
    {
        try {
            $post = $this->postService->publishPost($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Post published successfully',
                'post' => $post
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish post',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

## 5. Real-Life Laravel Code - Service Provider Setup

### Dependency Injection Configuration

```php
<?php
// app/Providers/PostServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PostService;
use App\Services\PostValidationService;
use App\Services\PostImageService;
use App\Services\PostSlugService;
use App\Services\PostSEOService;
use App\Services\PostCacheService;
use App\Services\PostNotificationService;
use App\Services\PostSearchIndexingService;
use App\Repositories\PostRepository;

class PostServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register all individual services - each with single responsibility
        $this->app->singleton(PostValidationService::class);
        $this->app->singleton(PostImageService::class);
        $this->app->singleton(PostSlugService::class);
        $this->app->singleton(PostSEOService::class);
        $this->app->singleton(PostCacheService::class);
        $this->app->singleton(PostNotificationService::class);
        $this->app->singleton(PostSearchIndexingService::class);
        $this->app->singleton(PostRepository::class);
        
        // Register the main orchestrator service
        $this->app->singleton(PostService::class, function ($app) {
            return new PostService(
                $app->make(PostRepository::class),
                $app->make(PostValidationService::class),
                $app->make(PostImageService::class),
                $app->make(PostSlugService::class),
                $app->make(PostSEOService::class),
                $app->make(PostCacheService::class),
                $app->make(PostNotificationService::class),
                $app->make(PostSearchIndexingService::class)
            );
        });
    }
}

// config/app.php - Register the service provider
'providers' => [
    // ... other providers
    App\Providers\PostServiceProvider::class,
],
```

### Testing Individual Responsibilities

```php
<?php
// tests/Unit/PostValidationServiceTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PostValidationService;
use App\Exceptions\ValidationException;

class PostValidationServiceTest extends TestCase
{
    private $validationService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new PostValidationService();
    }
    
    public function test_validates_valid_post_data()
    {
        $validData = [
            'title' => 'Test Post Title',
            'content' => str_repeat('This is test content. ', 20), // > 100 chars
            'category_id' => 1,
            'tags' => ['laravel', 'php'],
            'meta_description' => 'Test meta description'
        ];
        
        $result = $this->validationService->validatePostData($validData);
        
        $this->assertIsArray($result);
        $this->assertEquals('Test Post Title', $result['title']);
        $this->assertArrayHasKey('content', $result);
    }
    
    public function test_throws_exception_for_invalid_data()
    {
        $invalidData = [
            'title' => '', // Required field empty
            'content' => 'Short', // Too short
            'category_id' => 999 // Non-existent category
        ];
        
        $this->expectException(ValidationException::class);
        
        $this->validationService->validatePostData($invalidData);
    }
}

// tests/Unit/PostSlugServiceTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PostSlugService;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostSlugServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private $slugService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->slugService = new PostSlugService();
    }
    
    public function test_generates_slug_from_title()
    {
        $title = 'This is a Test Post Title';
        $slug = $this->slugService->generateSlug($title);
        
        $this->assertEquals('this-is-a-test-post-title', $slug);
    }
    
    public function test_generates_unique_slug_when_duplicate_exists()
    {
        // Create existing post with slug
        Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Test content',
            'author_id' => 1,
            'category_id' => 1
        ]);
        
        $slug = $this->slugService->generateSlug('Test Post');
        
        $this->assertEquals('test-post-1', $slug);
    }
}

// tests/Unit/PostSEOServiceTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PostSEOService;

class PostSEOServiceTest extends TestCase
{
    private $seoService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->seoService = new PostSEOService();
    }
    
    public function test_generates_meta_description_from_content()
    {
        $content = '<p>This is the first paragraph of the post content. It should be used for meta description.</p><p>This is the second paragraph.</p>';
        
        $metaDescription = $this->seoService->generateMetaDescription($content);
        
        $this->assertStringContainsString('This is the first paragraph', $metaDescription);
        $this->assertLessThanOrEqual(160, strlen($metaDescription));
    }
    
    public function test_uses_custom_description_when_provided()
    {
        $content = '<p>Some content here</p>';
        $customDescription = 'Custom meta description for this post';
        
        $metaDescription = $this->seoService->generateMetaDescription($content, $customDescription);
        
        $this->assertEquals($customDescription, $metaDescription);
    }
    
    public function test_generates_keywords_from_title_and_content()
    {
        $title = 'Laravel PHP Framework Tutorial';
        $content = 'This is a comprehensive guide about Laravel framework and PHP development';
        $tags = ['laravel', 'tutorial'];
        
        $keywords = $this->seoService->generateKeywords($title, $content, $tags);
        
        $this->assertContains('laravel', $keywords);
        $this->assertContains('php', $keywords);
        $this->assertContains('tutorial', $keywords);
    }
}

// tests/Integration/PostServiceTest.php
namespace Tests\Integration;

use Tests\TestCase;
use App\Services\PostService;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private $postService;
    private $user;
    private $category;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->postService = app(PostService::class);
        
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
        
        Storage::fake('public');
    }
    
    public function test_creates_post_with_all_services_working_together()
    {
        $postData = [
            'title' => 'Integration Test Post',
            'content' => str_repeat('This is test content for integration testing. ', 10),
            'category_id' => $this->category->id,
            'tags' => ['integration', 'testing'],
            'featured_image' => UploadedFile::fake()->image('test.jpg', 800, 600),
            'meta_description' => 'Test meta description'
        ];
        
        $post = $this->postService->createPost($postData, $this->user->id);
        
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Integration Test Post',
            'author_id' => $this->user->id,
            'category_id' => $this->category->id,
            'status' => 'draft'
        ]);
        
        // Check that slug was generated
        $this->assertEquals('integration-test-post', $post->slug);
        
        // Check that image was uploaded
        $this->assertNotNull($post->featured_image);
        Storage::disk('public')->assertExists($post->featured_image);
        
        // Check that meta description was set
        $this->assertNotNull($post->meta_description);
    }
    
    public function test_publishes_post_with_notifications()
    {
        $post = $this->postService->createPost([
            'title' => 'Test Publish Post',
            'content' => str_repeat('Content for publishing test. ', 10),
            'category_id' => $this->category->id
        ], $this->user->id);
        
        $publishedPost = $this->postService->publishPost($post->id);
        
        $this->assertEquals('published', $publishedPost->status);
        $this->assertNotNull($publishedPost->published_at);
    }
}
```

### Frontend Integration

```php
<?php
// routes/web.php
use App\Http\Controllers\PostController;

Route::prefix('api/posts')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [PostController::class, 'store']);
    Route::get('/{slug}', [PostController::class, 'show']);
    Route::put('/{id}', [PostController::class, 'update']);
    Route::delete('/{id}', [PostController::class, 'destroy']);
    Route::post('/{id}/publish', [PostController::class, 'publish']);
});

// Frontend JavaScript - Vue.js Component
// resources/js/components/PostEditor.vue
<template>
    <div class="post-editor">
        <form @submit.prevent="savePost">
            <div class="form-group">
                <label>Title</label>
                <input 
                    v-model="post.title" 
                    type="text" 
                    class="form-control"
                    required
                />
            </div>
            
            <div class="form-group">
                <label>Content</label>
                <textarea 
                    v-model="post.content" 
                    class="form-control"
                    rows="10"
                    required
                ></textarea>
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <select v-model="post.category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <option 
                        v-for="category in categories" 
                        :key="category.id" 
                        :value="category.id"
                    >
                        {{ category.name }}
                    </option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tags</label>
                <input 
                    v-model="tagsInput" 
                    type="text" 
                    class="form-control"
                    placeholder="Enter tags separated by commas"
                />
            </div>
            
            <div class="form-group">
                <label>Featured Image</label>
                <input 
                    @change="handleImageUpload" 
                    type="file" 
                    class="form-control"
                    accept="image/*"
                />
            </div>
            
            <div class="form-group">
                <label>Meta Description</label>
                <textarea 
                    v-model="post.meta_description" 
                    class="form-control"
                    rows="3"
                    maxlength="160"
                ></textarea>
                <small class="text-muted">{{ 160 - (post.meta_description?.length || 0) }} characters remaining</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    {{ editMode ? 'Update' : 'Create' }} Post
                </button>
                
                <button 
                    v-if="post.id && post.status === 'draft'" 
                    @click="publishPost" 
                    type="button" 
                    class="btn btn-success"
                >
                    Publish Post
                </button>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    name: 'PostEditor',
    props: {
        postId: {
            type: Number,
            default: null
        }
    },
    data() {
        return {
            post: {
                title: '',
                content: '',
                category_id: '',
                meta_description: '',
                featured_image: null
            },
            categories: [],
            tagsInput: '',
            editMode: false,
            loading: false
        }
    },
    async mounted() {
        await this.loadCategories();
        
        if (this.postId) {
            this.editMode = true;
            await this.loadPost();
        }
    },
    methods: {
        async loadCategories() {
            try {
                const response = await fetch('/api/categories');
                this.categories = await response.json();
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },
        
        async loadPost() {
            try {
                const response = await fetch(`/api/posts/${this.postId}`);
                const data = await response.json();
                
                if (data.success) {
                    this.post = data.post;
                    this.tagsInput = this.post.tags?.map(tag => tag.name).join(', ') || '';
                }
            } catch (error) {
                console.error('Failed to load post:', error);
            }
        },
        
        handleImageUpload(event) {
            this.post.featured_image = event.target.files[0];
        },
        
        async savePost() {
            this.loading = true;
            
            try {
                const formData = new FormData();
                
                // Append all post data
                Object.keys(this.post).forEach(key => {
                    if (this.post[key] !== null) {
                        formData.append(key, this.post[key]);
                    }
                });
                
                // Process tags
                if (this.tagsInput.trim()) {
                    const tags = this.tagsInput.split(',').map(tag => tag.trim());
                    tags.forEach(tag => formData.append('tags[]', tag));
                }
                
                const url = this.editMode ? `/api/posts/${this.post.id}` : '/api/posts';
                const method = this.editMode ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Authorization': `Bearer ${this.getAuthToken()}`
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.$emit('post-saved', data.post);
                    this.showSuccessMessage(data.message);
                    
                    if (!this.editMode) {
                        // Redirect to edit mode for new posts
                        this.$router.push(`/posts/${data.post.id}/edit`);
                    }
                } else {
                    this.showErrorMessage(data.message);
                }
                
            } catch (error) {
                console.error('Failed to save post:', error);
                this.showErrorMessage('Failed to save post. Please try again.');
            } finally {
                this.loading = false;
            }
        },
        
        async publishPost() {
            if (!confirm('Are you sure you want to publish this post?')) {
                return;
            }
            
            this.loading = true;
            
            try {
                const response = await fetch(`/api/posts/${this.post.id}/publish`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Authorization': `Bearer ${this.getAuthToken()}`
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.post = data.post;
                    this.$emit('post-published', data.post);
                    this.showSuccessMessage('Post published successfully!');
                } else {
                    this.showErrorMessage(data.message);
                }
                
            } catch (error) {
                console.error('Failed to publish post:', error);
                this.showErrorMessage('Failed to publish post. Please try again.');
            } finally {
                this.loading = false;
            }
        },
        
        getAuthToken() {
            return localStorage.getItem('auth_token');
        },
        
        showSuccessMessage(message) {
            // Implementation depends on your notification system
            console.log('Success:', message);
        },
        
        showErrorMessage(message) {
            // Implementation depends on your notification system
            console.error('Error:', message);
        }
    }
}
</script>
```

### Configuration and Database

```php
<?php
// database/migrations/create_posts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->text('meta_description')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('featured_image_thumbnail')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('category_id')->constrained();
            $table->timestamps();
            
            $table->index(['status', 'published_at']);
            $table->index(['author_id', 'status']);
            $table->index(['category_id', 'status']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}

// database/migrations/create_post_search_index_table.php
class CreatePostSearchIndexTable extends Migration
{
    public function up()
    {
        Schema::create('post_search_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->text('tags')->nullable();
            $table->string('category');
            $table->string('author');
            $table->timestamps();
            
            $table->fullText(['title', 'content', 'tags']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('post_search_index');
    }
}

// app/Models/Post.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_description',
        'featured_image',
        'featured_image_thumbnail',
        'status',
        'published_at',
        'author_id',
        'category_id'
    ];
    
    protected $casts = [
        'published_at' => 'datetime'
    ];
    
    protected $appends = [
        'featured_image_url',
        'featured_image_thumbnail_url'
    ];
    
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
    
    public function getFeaturedImageUrlAttribute()
    {
        return $this->featured_image 
            ? Storage::disk('public')->url($this->featured_image)
            : null;
    }
    
    public function getFeaturedImageThumbnailUrlAttribute()
    {
        return $this->featured_image_thumbnail 
            ? Storage::disk('public')->url($this->featured_image_thumbnail)
            : null;
    }
}
```

## Summary: SRP Benefits in Laravel

### ✅ Perfect Separation Achieved:

1. **Single Responsibilities**: Each service class handles exactly one concern
2. **Easy Testing**: Each responsibility can be unit tested independently
3. **Team Development**: Different developers can work on different services
4. **Maintenance**: Changes to one responsibility don't affect others
5. **Reusability**: Services can be reused across different parts of application

### 🎯 Key SRP Patterns:

1. **Validation Service**: Only handles data validation
2. **Image Service**: Only handles file operations
3. **SEO Service**: Only handles SEO-related tasks
4. **Cache Service**: Only handles caching operations
5. **Notification Service**: Only handles notifications
6. **Repository**: Only handles data persistence
7. **Orchestrator Service**: Coordinates all responsibilities

### 🚀 Real-world Benefits:

- **Maintainability**: Easy to locate and fix issues
- **Testability**: Each service can be tested in isolation
- **Scalability**: Easy to optimize individual services
- **Team Productivity**: Multiple developers can work simultaneously
- **Code Reuse**: Services can be used in different contexts
- **Debugging**: Issues are isolated to specific responsibilities

### 📊 Comparison Results:

**Before SRP (Single Class):**
- 1 class with 8+ responsibilities
- Hard to test individual features
- Changes affect entire system
- Difficult team collaboration

**After SRP (8 Specialized Classes):**
- 8 classes, each with 1 responsibility
- Easy to test each feature
- Changes isolated to specific services
- Perfect team collaboration

SRP transforms your Laravel applications from monolithic classes into modular, maintainable systems where each class has a clear, single purpose!# Single Responsibility Principle (SRP) - Complete Guide with Laravel Examples

## Definition

> **"A class should have only one reason to change."**
> 
> **"A class should have only one responsibility."**

**In Simple Terms**: Each class should do ONE thing and do it well. If you need to change a class for multiple different reasons, it's probably doing too much.

## 1. Algorithm with ASCII Visualization

### Core SRP Algorithm:
```
1. Identify what the class is responsible for
2. Count the reasons why this class might need to change
3. If more than ONE reason exists, split the class
4. Create separate classes for each responsibility
5. Make classes collaborate through composition
```

### ASCII Visualization - SRP Violation (BAD):

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    SINGLE RESPONSIBILITY VIOLATION                      │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                    UserManager Class                            │   │
│  │                                                                 │   │
│  │  RESPONSIBILITY 1: User Validation                             │   │
│  │  ┌─────────────────────────────────────────────────────────┐   │   │
│  │  │ + validateEmail($email)                                 │   │   │
│  │  │ + validatePassword($password)                           │   │   │
│  │  │ + validateAge($age)                                     │   │   │
│  │  └─────────────────────────────────────────────────────────┘   │   │
│  │                                                                 │   │
│  │  RESPONSIBILITY 2: Database Operations                         │   │
│  │  ┌─────────────────────────────────────────────────────────┐   │   │
│  │  │ + saveUser($userData)                                   │   │   │
│  │  │ + findUser($id)                                         │   │   │
│  │  │ + updateUser($id, $data)                                │   │   │
│  │  │ + deleteUser($id)                                       │   │   │
│  │  └─────────────────────────────────────────────────────────┘   │   │
│  │                                                                 │   │
│  │  RESPONSIBILITY 3: Email Operations                            │   │
│  │  ┌─────────────────────────────────────────────────────────┐   │   │
│  │  │ + sendWelcomeEmail($user)                               │   │   │
│  │  │ + sendPasswordResetEmail($user)                         │   │   │
│  │  │ + sendNotificationEmail($user, $message)                │   │   │
│  │  └─────────────────────────────────────────────────────────┘   │   │
│  │                                                                 │   │
│  │  RESPONSIBILITY 4: File Operations                             │   │
│  │  ┌─────────────────────────────────────────────────────────┐   │   │
│  │  │ + uploadAvatar($userId, $file)                          │   │   │
│  │  │ + deleteAvatar($userId)                                 │   │   │
│  │  │ + generateReport($userId)                               │   │   │
│  │  └─────────────────────────────────────────────────────────┘   │   │
│  │                                                                 │   │
│  │  RESPONSIBILITY 5: Logging                                     │   │
│  │  ┌─────────────────────────────────────────────────────────┐   │   │
│  │  │ + logUserAction($userId, $action)                       │   │   │
│  │  │ + logError($error)                                      │   │   │
│  │  └─────────────────────────────────────────────────────────┘   │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  PROBLEMS WITH THIS APPROACH:                                           │
│  ❌ MANY REASONS TO CHANGE:                                            │
│    • Validation rules change → modify UserManager                     │
│    • Database schema changes → modify UserManager                     │
│    • Email templates change → modify UserManager                      │
│    • File storage changes → modify UserManager                        │
│    • Logging format changes → modify UserManager                      │
│                                                                         │
│  ❌ HARD TO:                                                           │
│    • Test individual parts                                             │
│    • Reuse validation in other classes                                 │
│    • Change email provider without affecting database code             │
│    • Work in teams (everyone touches same class)                       │
│    • Understand what the class actually does                           │
└─────────────────────────────────────────────────────────────────────────┘
```

### ASCII Visualization - SRP Compliant (GOOD):

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    SINGLE RESPONSIBILITY COMPLIANT                      │
│                                                                         │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐         │
│  │ UserValidator   │  │ UserRepository  │  │ EmailService    │         │
│  │                 │  │                 │  │                 │         │
│  │ Responsibility: │  │ Responsibility: │  │ Responsibility: │         │
│  │ VALIDATION      │  │ DATA STORAGE    │  │ EMAIL SENDING   │         │
│  │                 │  │                 │  │                 │         │
│  │ + validateEmail │  │ + save()        │  │ + sendWelcome() │         │
│  │ + validatePass  │  │ + find()        │  │ + sendReset()   │         │
│  │ + validateAge   │  │ + update()      │  │ + sendNotify()  │         │
│  │                 │  │ + delete()      │  │                 │         │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘         │
│                                                                         │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐         │
│  │ FileManager     │  │ Logger          │  │ UserService     │         │
│  │                 │  │                 │  │                 │         │
│  │ Responsibility: │  │ Responsibility: │  │ Responsibility: │         │
│  │ FILE OPERATIONS │  │ LOGGING         │  │ ORCHESTRATION   │         │
│  │                 │  │                 │  │                 │         │
│  │ + uploadAvatar  │  │ + logAction()   │  │ + createUser()  │         │
│  │ + deleteAvatar  │  │ + logError()    │  │ + updateUser()  │         │
│  │ + generateReport│  │ + logInfo()     │  │ + deleteUser()  │         │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘         │
│           ▲                     ▲                     │                 │
│           │                     │                     │                 │
│           └─────────────────────┼─────────────────────┘                 │
│                                 │      uses all other classes           │
│                                 ▼                                       │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │                   COMPOSITION PATTERN                          │   │
│  │                                                                 │   │
│  │  class UserService {                                            │   │
│  │    private $validator, $repository, $emailService,             │   │
│  │            $fileManager, $logger;                              │   │
│  │                                                                 │   │
│  │    public function createUser($userData) {                     │   │
│  │      $this->validator->validate($userData);                    │   │
│  │      $user = $this->repository->save($userData);               │   │
│  │      $this->emailService->sendWelcome($user);                  │   │
│  │      $this->logger->logAction($user->id, 'created');           │   │
│  │    }                                                            │   │
│  │  }                                                              │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  BENEFITS OF THIS APPROACH:                                             │
│  ✅ SINGLE REASON TO CHANGE:                                           │
│    • Validation rules change → only modify UserValidator              │
│    • Database changes → only modify UserRepository                    │
│    • Email changes → only modify EmailService                         │
│    • File operations change → only modify FileManager                 │
│    • Logging changes → only modify Logger                             │
│                                                                         │
│  ✅ EASY TO:                                                           │
│    • Test each class independently                                     │
│    • Reuse validation in other parts of application                    │
│    • Change email provider without touching other code                 │
│    • Work in teams (each team owns different classes)                  │
│    • Understand what each class does                                   │
└─────────────────────────────────────────────────────────────────────────┘
```

### SRP Decision Flow Diagram:

```
SINGLE RESPONSIBILITY ANALYSIS WORKFLOW:

┌─────────────────┐
│   Look at a     │
│     Class       │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│   List all      │
│  Responsibilities │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐    NO     ┌─────────────────┐
│  More than 1    │ ────────▶ │  Class follows  │
│ Responsibility? │           │      SRP ✓      │
└─────────┬───────┘           └─────────────────┘
          │ YES
          ▼
┌─────────────────┐
│  List reasons   │
│ class might     │
│    change       │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐    NO     ┌─────────────────┐
│  More than 1    │ ────────▶ │  Class follows  │
│  reason to      │           │      SRP ✓      │
│   change?       │           └─────────────────┘
└─────────┬───────┘
          │ YES
          ▼
┌─────────────────┐
│  Split class    │
│  into separate  │
│  classes, each  │
│  with single    │
│ responsibility  │
└─────────┬───────┘
          │
          ▼
┌─────────────────┐
│  Use composition│
│  to combine     │
│  functionality  │
│  when needed    │
└─────────────────┘

EXAMPLE ANALYSIS:

UserManager class responsibilities:
1. Validate user data
2. Save to database  
3. Send emails
4. Handle file uploads
5. Log actions

Reasons to change:
1. Validation rules change
2. Database schema changes
3. Email templates change
4. File storage changes
5. Logging format changes

Result: 5 responsibilities = VIOLATES SRP
Solution: Split into 5 separate classes
```

## 2. Easy PHP Code Examples

### Simple Example - User Registration

```php
<?php
// ❌ BAD: SRP Violation - One class doing everything
class BadUserRegistration {
    
    public function registerUser($userData) {
        // Responsibility 1: Validation
        if (empty($userData['email'])) {
            throw new Exception('Email required');
        }
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email');
        }
        if (strlen($userData['password']) < 8) {
            throw new Exception('Password too short');
        }
        
        // Responsibility 2: Database operations
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (email, password, name) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userData['email'], $hashedPassword, $userData['name']]);
        $userId = $this->pdo->lastInsertId();
        
        // Responsibility 3: Email sending
        $subject = 'Welcome to our platform!';
        $message = "Hello {$userData['name']}, welcome to our platform!";
        mail($userData['email'], $subject, $message);
        
        // Responsibility 4: Logging
        $logEntry = date('Y-m-d H:i:s') . " - User {$userId} registered\n";
        file_put_contents('user_log.txt', $logEntry, FILE_APPEND);
        
        // Responsibility 5: File operations
        if (isset($userData['avatar'])) {
            $avatarPath = "avatars/{$userId}.jpg";
            move_uploaded_file($userData['avatar']['tmp_name'], $avatarPath);
        }
        
        return $userId;
    }
}

// Problems:
// - Hard to test individual parts
// - If email system changes, must modify this class
// - If database schema changes, must modify this class
// - If validation rules change, must modify this class
// - If logging format changes, must modify this class

// ✅ GOOD: SRP Compliant - Each class has one responsibility
class UserValidator {
    public function validate($userData) {
        $errors = [];
        
        if (empty($userData['email'])) {
            $errors[] = 'Email required';
        } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email';
        }
        
        if (empty($userData['password'])) {
            $errors[] = 'Password required';
        } elseif (strlen($userData['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if (empty($userData['name'])) {
            $errors[] = 'Name required';
        }
        
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
        
        return true;
    }
}

class UserRepository {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($userData) {
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (email, password, name, created_at) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $userData['email'],
            $hashedPassword,
            $userData['name'],
            date('Y-m-d H:i:s')
        ]);
        
        return [
            'id' => $this->pdo->lastInsertId(),
            'email' => $userData['email'],
            'name' => $userData['name']
        ];
    }
    
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}

class EmailService {
    public function sendWelcomeEmail($user) {
        $subject = 'Welcome to our platform!';
        $message = "Hello {$user['name']}, welcome to our platform!";
        
        // Could easily switch to different email provider
        return mail($user['email'], $subject, $message);
    }
    
    public function sendPasswordResetEmail($user, $resetToken) {
        $subject = 'Password Reset';
        $message = "Click this link to reset your password: /reset?token={$resetToken}";
        
        return mail($user['email'], $subject, $message);
    }
}

class FileUploadService {
    private $uploadPath;
    
    public function __construct($uploadPath = 'uploads/') {
        $this->uploadPath = $uploadPath;
    }
    
    public function uploadAvatar($userId, $file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid file upload');
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "avatar_{$userId}.{$extension}";
        $destination = $this->uploadPath . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $destination;
        }
        
        throw new Exception('File upload failed');
    }
}

class ActivityLogger {
    private $logFile;
    
    public function __construct($logFile = 'activity.log') {
        $this->logFile = $logFile;
    }
    
    public function logUserRegistration($userId, $email) {
        $this->log("User {$userId} ({$email}) registered");
    }
    
    public function logUserLogin($userId) {
        $this->log("User {$userId} logged in");
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}

// Orchestrator class that uses all the above classes
class GoodUserRegistration {
    private $validator;
    private $repository;
    private $emailService;
    private $fileService;
    private $logger;
    
    public function __construct(
        UserValidator $validator,
        UserRepository $repository,
        EmailService $emailService,
        FileUploadService $fileService,
        ActivityLogger $logger
    ) {
        $this->validator = $validator;
        $this->repository = $repository;
        $this->emailService = $emailService;
        $this->fileService = $fileService;
        $this->logger = $logger;
    }
    
    public function registerUser($userData, $avatarFile = null) {
        // Each responsibility is handled by a dedicated class
        $this->validator->validate($userData);
        
        $user = $this->repository->create($userData);
        
        $this->emailService->sendWelcomeEmail($user);
        
        if ($avatarFile) {
            $avatarPath = $this->fileService->uploadAvatar($user['id'], $avatarFile);
        }
        
        $this->logger->logUserRegistration($user['id'], $user['email']);
        
        return $user;
    }
}

// Usage
$pdo = new PDO('sqlite:users.db');
$registration = new GoodUserRegistration(
    new UserValidator(),
    new UserRepository($pdo),
    new EmailService(),
    new FileUploadService('uploads/avatars/'),
    new ActivityLogger('user_activity.log')
);

$userData = [
    'email' => 'john@example.com',
    'password' => 'securepassword123',
    'name' => 'John Doe'
];

$user = $registration->registerUser($userData, $_FILES['avatar'] ?? null);
?>
```

### Simple Example - Order Processing

```php
<?php
// ❌ BAD: One class handling everything
class BadOrderProcessor {
    public function processOrder($orderData) {
        // Validation responsibility
        if (empty($orderData['items'])) {
            throw new Exception('No items in order');
        }
        
        // Inventory responsibility
        foreach ($orderData['items'] as $item) {
            $stock = $this->getStock($item['product_id']);
            if ($stock < $item['quantity']) {
                throw new Exception('Insufficient stock');
            }
        }
        
        // Pricing responsibility
        $total = 0;
        foreach ($orderData['items'] as $item) {
            $price = $this->getProductPrice($item['product_id']);
            $total += $price * $item['quantity'];
        }
        
        // Tax calculation responsibility
        $taxRate = $this->getTaxRate($orderData['customer']['location']);
        $tax = $total * $taxRate;
        $finalTotal = $total + $tax;
        
        // Payment responsibility
        $paymentResult = $this->processPayment($finalTotal, $orderData['payment']);
        if (!$paymentResult['success']) {
            throw new Exception('Payment failed');
        }
        
        // Database responsibility
        $orderId = $this->saveOrder($orderData, $finalTotal);
        
        // Email responsibility
        $this->sendOrderConfirmation($orderData['customer']['email'], $orderId);
        
        // Inventory update responsibility
        foreach ($orderData['items'] as $item) {
            $this->updateStock($item['product_id'], -$item['quantity']);
        }
        
        return $orderId;
    }
}

// ✅ GOOD: Each responsibility in its own class
class OrderValidator {
    public function validate($orderData) {
        if (empty($orderData['items'])) {
            throw new Exception('Order must contain at least one item');
        }
        
        if (empty($orderData['customer']['email'])) {
            throw new Exception('Customer email required');
        }
        
        if (empty($orderData['payment'])) {
            throw new Exception('Payment information required');
        }
        
        return true;
    }
}

class InventoryChecker {
    private $productRepository;
    
    public function __construct($productRepository) {
        $this->productRepository = $productRepository;
    }
    
    public function checkAvailability($items) {
        $unavailableItems = [];
        
        foreach ($items as $item) {
            $stock = $this->productRepository->getStock($item['product_id']);
            if ($stock < $item['quantity']) {
                $unavailableItems[] = [
                    'product_id' => $item['product_id'],
                    'requested' => $item['quantity'],
                    'available' => $stock
                ];
            }
        }
        
        if (!empty($unavailableItems)) {
            throw new InsufficientStockException($unavailableItems);
        }
        
        return true;
    }
}

class PriceCalculator {
    private $productRepository;
    
    public function __construct($productRepository) {
        $this->productRepository = $productRepository;
    }
    
    public function calculateSubtotal($items) {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $price = $this->productRepository->getPrice($item['product_id']);
            $subtotal += $price * $item['quantity'];
        }
        
        return $subtotal;
    }
}

class TaxCalculator {
    public function calculateTax($amount, $customerLocation) {
        $taxRates = [
            'CA' => 0.0875,  // California
            'NY' => 0.08,    // New York
            'TX' => 0.0625,  // Texas
            'FL' => 0.06     // Florida
        ];
        
        $rate = $taxRates[$customerLocation] ?? 0;
        return $amount * $rate;
    }
}

class PaymentProcessor {
    public function processPayment($amount, $paymentData) {
        // Simulate payment processing
        if ($paymentData['card_number'] === '4000000000000002') {
            return ['success' => false, 'error' => 'Card declined'];
        }
        
        return [
            'success' => true,
            'transaction_id' => 'txn_' . uniqid(),
            'amount' => $amount
        ];
    }
}

class OrderRepository {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function save($orderData, $total, $tax) {
        $sql = "INSERT INTO orders (customer_email, total_amount, tax_amount, status, created_at) 
                VALUES (?, ?, ?, 'confirmed', ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $orderData['customer']['email'],
            $total,
            $tax,
            date('Y-m-d H:i:s')
        ]);
        
        return $this->pdo->lastInsertId();
    }
}

class OrderNotificationService {
    public function sendOrderConfirmation($email, $orderId, $total) {
        $subject = "Order Confirmation - Order #{$orderId}";
        $message = "Thank you for your order! Order #{$orderId} for \${$total} has been confirmed.";
        
        return mail($email, $subject, $message);
    }
}

// Orchestrator that coordinates all responsibilities
class GoodOrderProcessor {
    private $validator;
    private $inventoryChecker;
    private $priceCalculator;
    private $taxCalculator;
    private $paymentProcessor;
    private $orderRepository;
    private $notificationService;
    
    public function __construct(
        OrderValidator $validator,
        InventoryChecker $inventoryChecker,
        PriceCalculator $priceCalculator,
        TaxCalculator $taxCalculator,
        PaymentProcessor $paymentProcessor,
        OrderRepository $orderRepository,
        OrderNotificationService $notificationService
    ) {
        $this->validator = $validator;
        $this->inventoryChecker = $inventoryChecker;
        $this->priceCalculator = $priceCalculator;
        $this->taxCalculator = $taxCalculator;
        $this->paymentProcessor = $paymentProcessor;
        $this->orderRepository = $orderRepository;
        $this->notificationService = $notificationService;
    }
    
    public function processOrder($orderData) {
        // Each step delegates to a specialized class
        $this->validator->validate($orderData);
        
        $this->inventoryChecker->checkAvailability($orderData['items']);
        
        $subtotal = $this->priceCalculator->calculateSubtotal($orderData['items']);
        
        $tax = $this->taxCalculator->calculateTax($subtotal, $orderData['customer']['location']);
        
        $total = $subtotal + $tax;
        
        $paymentResult = $this->paymentProcessor->processPayment($total, $orderData['payment']);
        if (!$paymentResult['success']) {
            throw new PaymentException($paymentResult['error']);
        }
        
        $orderId = $this->orderRepository->save($orderData, $total, $tax);
        
        $this->notificationService->sendOrderConfirmation(
            $orderData['customer']['email'],
            $orderId,
            $total
        );
        
        return [
            'order_id' => $orderId,
            'total' => $total,
            'payment_result' => $paymentResult
        ];
    }
}
?>
```

## 3. Use Cases

### Common SRP Scenarios:

1. **User Management** - Validation, repository, email, file uploads, logging
2. **Order Processing** - Validation, inventory, pricing, payment, notifications
3. **Content Management** - Validation, storage, search indexing, caching
4. **Authentication** - Validation, token generation, session management, logging
5. **File Processing** - Upload, validation, conversion, storage, metadata extraction
6. **Reporting** - Data collection, calculation, formatting, delivery
7. **API Responses** - Validation, business logic, formatting, caching
8. **Data Import/Export** - Reading, validation, transformation, writing

### When SRP is Critical:

✅ **Testing** - Each responsibility can be tested independently
✅ **Team Development** - Different developers work on different responsibilities
✅ **Maintenance** - Changes to one responsibility don't affect others
✅ **Reusability** - Individual components can be reused elsewhere
✅ **Debugging** - Easier to locate issues in specific responsibilities

## 4. Real-Life Laravel Implementation

### Scenario: Blog Post Management System

```php
<?php
// app/Services/PostValidationService.php
namespace App\Services;

use Illuminate\Support\Facades\Validator;
use App\Exceptions\ValidationException;

class PostValidationService
{
    public function validatePostData($data)
    {
        $validator = Validator::make($data, [
            'title' => 'required|string|max:255|unique:posts,title',
            'content' => 'required|string|min:100',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'array',
            'tags.*' => 'string|max:50',
            'featured_image' => 'nullable|image|max:2048',
            'publish_date' => 'nullable|date|after:now',
            'meta_description' => 'nullable|string|max:160'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
        
        return $this->sanitizeData($data);
    }
    
    public function validateUpdateData($data, $postId)
    {
        $validator = Validator::make($data, [
            'title' => "required|string|max:255|unique:posts,title,{$postId}",
            'content' => 'required|string|min:100',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'array',
            'tags.*' => 'string|max:50',
            'featured_image' => 'nullable|image|max:2048',
            'publish_date' => 'nullable|date',
            'meta_description' => 'nullable|string|max:160'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
        
        return $this->sanitizeData($data);
    }
    
    private function sanitizeData($data)
    {
        return [
            'title' => strip_tags($data['title']),
            'content' => $this->sanitizeContent($data['content']),
            'category_id' => (int)$data['category_id'],
            'tags' => $data['tags'] ?? [],
            'featured_image' => $data['featured_image'] ?? null,
            'publish_date' => $data['publish_date'] ?? null,
            'meta_description' => strip_tags($data['meta_description'] ?? '')
        ];
    }
    
    private function sanitizeContent($content)
    {
        // Allow certain HTML tags for blog content
        $allowedTags = '<p><br><strong><em><ul><ol><li><h2><h3><h4><blockquote><a><img>';
        return strip_tags($content
