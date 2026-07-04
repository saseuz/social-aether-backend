<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'displayName' => $user->name,
            'username' => str_starts_with($user->username, '@') ? $user->username : '@' . $user->username,
            'avatarText' => $user->avatar_text ?? strtoupper(substr($user->name, 0, 2)),
            'connections' => $user->following()->pluck('username')->map(fn($u) => str_starts_with($u, '@') ? substr($u, 1) : $u)->toArray(),
        ];
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'displayName' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $cleanUsername = ltrim($validated['username'], '@');

        // Generate initials for avatar
        $words = explode(' ', $validated['displayName']);
        $initials = '';
        foreach ($words as $w) {
            $initials .= mb_substr($w, 0, 1);
        }
        $avatarText = strtoupper(mb_substr($initials, 0, 2));

        $user = User::create([
            'name' => $validated['displayName'],
            'username' => $cleanUsername,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'avatar_text' => $avatarText,
        ]);

        $token = $user->createToken('aether_session')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Node synchronization failed. Check credentials.'],
            ]);
        }

        $token = $user->createToken('aether_session')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($this->formatUser($request->user()));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'displayName' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $cleanUsername = ltrim($validated['username'], '@');

        // Generate initials for avatar if name changed
        $avatarText = $user->avatar_text;
        if ($validated['displayName'] !== $user->name) {
            $words = explode(' ', $validated['displayName']);
            $initials = '';
            foreach ($words as $w) {
                $initials .= mb_substr($w, 0, 1);
            }
            $avatarText = strtoupper(mb_substr($initials, 0, 2));
        }

        $user->update([
            'name' => $validated['displayName'],
            'username' => $cleanUsername,
            'email' => $validated['email'],
            'avatar_text' => $avatarText,
        ]);

        return response()->json($this->formatUser($user));
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:6',
        ]);

        if (!Hash::check($validated['currentPassword'], $user->password)) {
            throw ValidationException::withMessages([
                'currentPassword' => ['Current password validation failed.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['newPassword']),
        ]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function profile(Request $request, $username = null)
    {
        $username = $username ?? $request->query('username');
        if (!$username) {
            return response()->json(['message' => 'Username parameter missing.'], 400);
        }

        $cleanUsername = ltrim($username, '@');
        $user = User::where('username', $cleanUsername)->first();

        if (!$user) {
            return response()->json(['message' => 'User node not found in Aether net.'], 404);
        }

        return response()->json($this->formatUser($user));
    }

    public function toggleFollow(Request $request, $username)
    {
        $currentUser = $request->user();
        $cleanUsername = ltrim($username, '@');
        $targetUser = User::where('username', $cleanUsername)->first();

        if (!$targetUser) {
            return response()->json(['message' => 'Target user not found.'], 404);
        }

        if ($currentUser->id === $targetUser->id) {
            return response()->json(['message' => 'Cannot connect with self.'], 400);
        }

        $isFollowing = $currentUser->following()->where('followed_id', $targetUser->id)->exists();

        if ($isFollowing) {
            $currentUser->following()->detach($targetUser->id);
            $isFollowing = false;
        } else {
            $currentUser->following()->attach($targetUser->id);
            $isFollowing = true;

            // Trigger notification
            \App\Models\Notification::create([
                'user_id' => $targetUser->id,
                'sender_id' => $currentUser->id,
                'type' => 'follow',
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'isFollowing' => $isFollowing,
        ]);
    }

    public function suggestions(Request $request)
    {
        $user = $request->user();
        
        $alreadyFollowingIds = $user->following()->pluck('followed_id');

        $suggestions = User::where('id', '!=', $user->id)
            ->whereNotIn('id', $alreadyFollowingIds)
            ->inRandomOrder()
            ->take(5)
            ->get();

        $formatted = $suggestions->map(function ($u) {
            return [
                'name' => $u->name,
                'handle' => '@' . $u->username,
                'avatarText' => $u->avatar_text ?? strtoupper(substr($u->name, 0, 2)),
            ];
        });

        return response()->json($formatted);
    }
}
