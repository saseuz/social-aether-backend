<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private function formatNotification(Notification $n): array
    {
        $sender = $n->sender;
        $post = $n->post;
        $comment = $n->comment;

        $data = [
            'id' => (string) $n->id,
            'type' => $n->type,
            'senderName' => $sender ? $sender->name : 'System',
            'senderHandle' => $sender ? '@' . $sender->username : '@system',
            'senderAvatarText' => $sender ? ($sender->avatar_text ?? strtoupper(substr($sender->name, 0, 2))) : 'SYS',
            'timestamp' => $n->created_at->diffForHumans(),
            'createdAt' => $n->created_at->toIso8601String(),
            'isRead' => (bool) $n->is_read,
        ];

        if ($post) {
            $data['postId'] = (string) $post->id;
            $data['postContent'] = $post->content;
        }

        if ($comment) {
            $data['commentId'] = (string) $comment->id;
            $data['commentContent'] = $comment->content;
        }

        return $data;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        $notifications = Notification::where('user_id', $user->id)
            ->with(['sender', 'post', 'comment'])
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $notifications->map(fn($n) => $this->formatNotification($n));

        return response()->json($formatted);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'sender_id' => 'nullable|integer|exists:users,id',
            'type' => 'required|string',
            'post_id' => 'nullable|integer|exists:posts,id',
            'comment_id' => 'nullable|integer|exists:comments,id',
        ]);

        $notification = Notification::create([
            'user_id' => $user->id,
            'sender_id' => $validated['sender_id'] ?? $user->id, // If null, self
            'type' => $validated['type'],
            'post_id' => $validated['post_id'] ?? null,
            'comment_id' => $validated['comment_id'] ?? null,
            'is_read' => false,
        ]);

        return response()->json($this->formatNotification($notification), 201);
    }

    public function markRead(Request $request)
    {
        $user = $request->user();
        $id = $request->input('id');

        if ($id) {
            Notification::where('user_id', $user->id)->where('id', $id)->update(['is_read' => true]);
        } else {
            Notification::where('user_id', $user->id)->update(['is_read' => true]);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $notification = Notification::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $notification->delete();

        return response()->json(['success' => true]);
    }

    public function clearAll(Request $request)
    {
        $user = $request->user();
        Notification::where('user_id', $user->id)->delete();

        return response()->json(['success' => true]);
    }
}
