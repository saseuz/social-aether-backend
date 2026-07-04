<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SocialApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'displayName' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'email', 'displayName', 'username', 'avatarText'],
                'token'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'username' => 'testuser',
        ]);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'email', 'displayName', 'username', 'avatarText'],
                'token'
            ]);
    }

    public function test_user_can_get_me(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'id' => (string) $user->id,
                'email' => $user->email,
                'displayName' => $user->name,
                'username' => '@' . $user->username,
            ]);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'username' => 'oldusername',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/update', [
                'displayName' => 'New Name',
                'username' => 'newusername',
                'email' => 'new@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'displayName' => 'New Name',
                'username' => '@newusername',
                'email' => 'new@example.com',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'username' => 'newusername',
            'email' => 'new@example.com',
        ]);
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/auth/change-password', [
                'currentPassword' => 'old_password',
                'newPassword' => 'new_password',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password updated successfully.']);

        $this->assertTrue(Hash::check('new_password', $user->fresh()->password));
    }

    public function test_get_profile_by_username(): void
    {
        $user = User::factory()->create([
            'name' => 'Target User',
            'username' => 'targetuser',
        ]);

        $response = $this->getJson('/api/users/profile?username=targetuser');

        $response->assertStatus(200)
            ->assertJson([
                'displayName' => 'Target User',
                'username' => '@targetuser',
            ]);
    }

    public function test_posts_crud_and_feed(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $otherUser->id,
            'content' => 'Hello World #test',
        ]);

        // List posts
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => (string) $post->id,
                'content' => 'Hello World #test',
                'authorName' => $otherUser->name,
                'isLiked' => false,
                'isBookmarked' => false,
                'isReposted' => false,
            ]);

        // Create post
        $createResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => 'My first post',
                'alignment' => 'left',
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonStructure(['id', 'content', 'authorName', 'authorHandle']);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'My first post',
        ]);
    }

    public function test_like_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Like post
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'isLiked' => true,
                'likesCount' => 1
            ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        // Unlike post
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'isLiked' => false,
                'likesCount' => 0
            ]);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);
    }

    public function test_bookmark_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Bookmark post
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'isBookmarked' => true,
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        // Get bookmarks
        $responseBookmarks = $this->actingAs($user, 'sanctum')
            ->getJson('/api/bookmarks');

        $responseBookmarks->assertStatus(200)
            ->assertJsonFragment([
                'id' => (string) $post->id,
                'isBookmarked' => true
            ]);
    }

    public function test_repost_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'content' => 'Original Content'
        ]);

        // Repost
        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/repost");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'isReposted' => true,
                'repostsCount' => 1
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'original_post_id' => $post->id,
            'is_retransmission' => true,
        ]);
    }

    public function test_repost_formatting_and_interaction_redirection(): void
    {
        $originalAuthor = User::factory()->create(['name' => 'Original Author', 'username' => 'orig_author']);
        $reposter = User::factory()->create(['name' => 'Reposter User', 'username' => 'reposter_user']);
        $post = Post::factory()->create([
            'user_id' => $originalAuthor->id,
            'content' => 'Original Content here #hello',
        ]);

        // Reposter reposts original post
        $this->actingAs($reposter, 'sanctum')
            ->postJson("/api/posts/{$post->id}/repost")
            ->assertStatus(200);

        // Fetch posts feed
        $response = $this->actingAs($reposter, 'sanctum')
            ->getJson('/api/posts');

        $response->assertStatus(200);
        
        // Find the retransmission post in the feed
        $feed = $response->json();
        $repostInFeed = collect($feed)->firstWhere('isRetransmission', true);
        
        $this->assertNotNull($repostInFeed);
        // The display authorName and authorHandle should belong to the original author
        $this->assertEquals('Original Author', $repostInFeed['authorName']);
        $this->assertEquals('@orig_author', $repostInFeed['authorHandle']);
        $this->assertEquals('Original Content here #hello', $repostInFeed['content']);
        $this->assertEquals('@reposter_user', $repostInFeed['repostedBy']);
        
        // Try to like the repost (using its own unique ID)
        $repostId = $repostInFeed['id'];
        $this->actingAs($reposter, 'sanctum')
            ->postJson("/api/posts/{$repostId}/like")
            ->assertStatus(200)
            ->assertJson(['isLiked' => true, 'likesCount' => 1]);

        // Check database to ensure the like was created on the original post, not the repost
        $this->assertDatabaseHas('likes', [
            'user_id' => $reposter->id,
            'post_id' => $post->id,
        ]);
        $this->assertDatabaseMissing('likes', [
            'user_id' => $reposter->id,
            'post_id' => $repostId,
        ]);
    }

    public function test_create_comment_and_reply(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Create comment
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/comments', [
                'post_id' => $post->id,
                'content' => 'Nice post!',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'content', 'authorName', 'authorHandle']);

        $commentId = $response->json('id');

        $this->assertDatabaseHas('comments', [
            'id' => $commentId,
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Nice post!',
        ]);

        // Create nested reply
        $replyResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/comments', [
                'post_id' => $post->id,
                'parent_id' => $commentId,
                'content' => 'Replying to myself',
            ]);

        $replyResponse->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'parent_id' => $commentId,
            'content' => 'Replying to myself',
        ]);
    }

    public function test_notifications_flow(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $notification = Notification::create([
            'user_id' => $user->id,
            'sender_id' => $sender->id,
            'type' => 'like',
            'post_id' => $post->id,
            'is_read' => false,
        ]);

        // List notifications
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => (string) $notification->id,
                'type' => 'like',
                'senderName' => $sender->name,
                'isRead' => false,
            ]);

        // Mark notification read
        $markReadResponse = $this->actingAs($user, 'sanctum')
            ->putJson('/api/notifications/mark-read', [
                'id' => $notification->id
            ]);

        $markReadResponse->assertStatus(200);
        $this->assertTrue((bool) $notification->fresh()->is_read);

        // Delete single
        $deleteResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/notifications/{$notification->id}");

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }
}
