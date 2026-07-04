<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'post_id' => 'required|integer|exists:posts,id',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'content' => 'required|string',
        ]);

        $postId = $validated['post_id'];
        $post = Post::findOrFail($postId);
        if ($post->is_retransmission && $post->original_post_id) {
            $postId = $post->original_post_id;
            $post = Post::findOrFail($postId);
        }

        $comment = Comment::create([
            'post_id' => $postId,
            'user_id' => $user->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'],
        ]);

        // Trigger notification to post author or parent comment author
        if ($comment->parent_id) {
            $parentComment = Comment::findOrFail($comment->parent_id);
            if ($parentComment->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $parentComment->user_id,
                    'sender_id' => $user->id,
                    'type' => 'reply',
                    'post_id' => $post->id,
                    'comment_id' => $comment->id,
                    'is_read' => false,
                ]);
            }
        } else {
            if ($post->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $post->user_id,
                    'sender_id' => $user->id,
                    'type' => 'comment',
                    'post_id' => $post->id,
                    'comment_id' => $comment->id,
                    'is_read' => false,
                ]);
            }
        }

        return response()->json([
            'id' => (string) $comment->id,
            'authorName' => $user->name,
            'authorHandle' => '@' . $user->username,
            'avatarText' => $user->avatar_text ?? strtoupper(substr($user->name, 0, 2)),
            'content' => $comment->content,
            'timestamp' => $comment->created_at->diffForHumans(),
            'replies' => [],
        ], 201);
    }
}
