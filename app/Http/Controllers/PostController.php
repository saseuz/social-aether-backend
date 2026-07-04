<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    private function formatComment($comment): array
    {
        return [
            'id' => (string) $comment->id,
            'authorName' => $comment->user->name,
            'authorHandle' => '@' . $comment->user->username,
            'avatarText' => $comment->user->avatar_text ?? strtoupper(substr($comment->user->name, 0, 2)),
            'content' => $comment->content,
            'timestamp' => $comment->created_at->diffForHumans(),
            'replies' => $comment->replies->map(fn($r) => $this->formatComment($r))->toArray(),
        ];
    }

    private function formatPost(Post $post, $currentUser = null): array
    {
        $targetPost = ($post->is_retransmission && $post->originalPost) ? $post->originalPost : $post;

        $author = $targetPost->user;
        $isLiked = false;
        $isBookmarked = false;
        $isReposted = false;

        if ($currentUser) {
            $isLiked = Like::where('user_id', $currentUser->id)->where('post_id', $targetPost->id)->exists();
            $isBookmarked = Bookmark::where('user_id', $currentUser->id)->where('post_id', $targetPost->id)->exists();
            $isReposted = Post::where('user_id', $currentUser->id)
                ->where('original_post_id', $targetPost->id)
                ->where('is_retransmission', true)
                ->exists();
        }

        $likesCount = Like::where('post_id', $targetPost->id)->count();
        $repostsCount = Post::where('original_post_id', $targetPost->id)->where('is_retransmission', true)->count();
        $commentsCount = $targetPost->comments()->count();

        // Top level comments
        $commentsList = $targetPost->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->get()
            ->map(fn($c) => $this->formatComment($c))
            ->toArray();

        $data = [
            'id' => (string) $post->id,
            'authorName' => $author->name,
            'authorHandle' => '@' . $author->username,
            'avatarText' => $author->avatar_text ?? strtoupper(substr($author->name, 0, 2)),
            'content' => $targetPost->content,
            'timestamp' => $post->created_at->diffForHumans(),
            'likes' => $likesCount,
            'reposts' => $repostsCount,
            'comments' => $commentsCount,
            'isLiked' => $isLiked,
            'isBookmarked' => $isBookmarked,
            'isReposted' => $isReposted,
            'commentsList' => $commentsList,
        ];

        if ($targetPost->media_url) {
            $data['mediaUrl'] = $targetPost->media_url;
        }

        if ($targetPost->alignment) {
            $data['alignment'] = $targetPost->alignment;
        }

        if ($post->is_retransmission) {
            $data['isRetransmission'] = true;
            $data['originalPostId'] = (string) $post->original_post_id;
            $data['repostedBy'] = '@' . $post->user->username;
        }

        return $data;
    }

    public function index(Request $request)
    {
        $currentUser = $request->user('sanctum');
        
        $posts = Post::with(['user', 'comments.user', 'originalPost.user', 'originalPost.comments.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $posts->map(fn($post) => $this->formatPost($post, $currentUser));

        return response()->json($formatted);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'content' => 'required|string',
            'media_url' => 'nullable|string',
            'alignment' => 'nullable|string',
            'original_post_id' => 'nullable|integer|exists:posts,id',
            'is_retransmission' => 'nullable|boolean',
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'content' => $validated['content'],
            'media_url' => $validated['media_url'] ?? null,
            'alignment' => $validated['alignment'] ?? null,
            'original_post_id' => $validated['original_post_id'] ?? null,
            'is_retransmission' => $validated['is_retransmission'] ?? false,
        ]);

        // Load relations for correct formatting
        $post->load(['user', 'originalPost.user']);

        return response()->json($this->formatPost($post, $user), 201);
    }

    public function like(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);
        
        if ($post->is_retransmission && $post->original_post_id) {
            $post = Post::findOrFail($post->original_post_id);
        }

        $like = Like::where('user_id', $user->id)->where('post_id', $post->id)->first();

        if ($like) {
            Like::where('user_id', $user->id)->where('post_id', $post->id)->delete();
            $isLiked = false;
        } else {
            Like::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
            $isLiked = true;

            // Trigger notification to the author if it's someone else
            if ($post->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $post->user_id,
                    'sender_id' => $user->id,
                    'type' => 'like',
                    'post_id' => $post->id,
                    'is_read' => false,
                ]);
            }
        }

        $likesCount = Like::where('post_id', $post->id)->count();

        return response()->json([
            'success' => true,
            'isLiked' => $isLiked,
            'likesCount' => $likesCount,
        ]);
    }

    public function bookmark(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        if ($post->is_retransmission && $post->original_post_id) {
            $post = Post::findOrFail($post->original_post_id);
        }

        $bookmark = Bookmark::where('user_id', $user->id)->where('post_id', $post->id)->first();

        if ($bookmark) {
            Bookmark::where('user_id', $user->id)->where('post_id', $post->id)->delete();
            $isBookmarked = false;
        } else {
            Bookmark::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
            $isBookmarked = true;
        }

        return response()->json([
            'success' => true,
            'isBookmarked' => $isBookmarked,
        ]);
    }

    public function repost(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        if ($post->is_retransmission && $post->original_post_id) {
            $post = Post::findOrFail($post->original_post_id);
        }

        // Check if already reposted
        $repost = Post::where('user_id', $user->id)
            ->where('original_post_id', $post->id)
            ->where('is_retransmission', true)
            ->first();

        if ($repost) {
            $repost->delete();
            $isReposted = false;
        } else {
            Post::create([
                'user_id' => $user->id,
                'content' => $post->content,
                'media_url' => $post->media_url,
                'alignment' => $post->alignment,
                'original_post_id' => $post->id,
                'is_retransmission' => true,
            ]);
            $isReposted = true;

            // Trigger notification
            if ($post->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $post->user_id,
                    'sender_id' => $user->id,
                    'type' => 'repost',
                    'post_id' => $post->id,
                    'is_read' => false,
                ]);
            }
        }

        $repostsCount = Post::where('original_post_id', $post->id)->where('is_retransmission', true)->count();

        return response()->json([
            'success' => true,
            'isReposted' => $isReposted,
            'repostsCount' => $repostsCount,
        ]);
    }

    public function bookmarks(Request $request)
    {
        $user = $request->user();
        $bookmarkedPostIds = Bookmark::where('user_id', $user->id)->pluck('post_id');

        $posts = Post::with(['user', 'comments.user', 'originalPost.user', 'originalPost.comments.user'])
            ->whereIn('id', $bookmarkedPostIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $posts->map(fn($post) => $this->formatPost($post, $user));

        return response()->json($formatted);
    }

    public function trends(Request $request)
    {
        $posts = Post::all();
        $tagsCount = [];
        $categories = [
            'reactCompiler' => 'Frontend',
            'quantumComputing' => 'Science & Tech',
            'minimalistDesign' => 'Aesthetics',
            'ambientWeb' => 'UX Trends',
            'vite8Release' => 'Development',
        ];

        foreach ($posts as $post) {
            preg_match_all('/#(\w+)/', $post->content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $tag) {
                    $tagsCount[$tag] = ($tagsCount[$tag] ?? 0) + 1;
                }
            }
        }

        arsort($tagsCount);

        $trends = [];
        foreach ($tagsCount as $tag => $count) {
            $category = $categories[$tag] ?? 'General';
            $trends[] = [
                'tag' => '#' . $tag,
                'category' => $category,
                'posts' => $count . ' transmission' . ($count > 1 ? 's' : ''),
            ];
        }

        if (count($trends) < 3) {
            $defaultTrends = [
                ['tag' => '#quantumComputing', 'category' => 'Science & Tech', 'posts' => '12.4k transmissions'],
                ['tag' => '#vite8Release', 'category' => 'Development', 'posts' => '8.2k transmissions'],
                ['tag' => '#minimalistDesign', 'category' => 'Aesthetics', 'posts' => '24.5k transmissions'],
                ['tag' => '#reactCompiler', 'category' => 'Frontend', 'posts' => '15.9k transmissions'],
                ['tag' => '#ambientWeb', 'category' => 'UX Trends', 'posts' => '4.1k transmissions'],
            ];
            foreach ($defaultTrends as $dt) {
                $exists = false;
                foreach ($trends as $t) {
                    if ($t['tag'] === $dt['tag']) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $trends[] = $dt;
                }
            }
        }

        return response()->json(array_slice($trends, 0, 5));
    }
}
