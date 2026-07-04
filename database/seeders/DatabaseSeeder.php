<?php

namespace Database\Seeders;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create default pilot user
        $pilot = User::factory()->create([
            'name' => 'Aether Pilot',
            'username' => 'zypp_pilot',
            'email' => 'pilot@aether.net',
            'password' => Hash::make('password123'),
            'avatar_text' => 'Æ',
        ]);

        // 2. Create other regular users
        $astro = User::factory()->create([
            'name' => 'Astro Coder',
            'username' => 'astro_coder',
            'email' => 'astro@aether.net',
            'avatar_text' => 'AC',
        ]);

        $minimalist = User::factory()->create([
            'name' => 'Minimalist Lab',
            'username' => 'minimalist_lab',
            'email' => 'minimalist@aether.net',
            'avatar_text' => 'ML',
        ]);

        $protocol = User::factory()->create([
            'name' => 'Aether Protocol',
            'username' => 'aether_net',
            'email' => 'protocol@aether.net',
            'avatar_text' => 'AP',
        ]);

        // 3. Create Posts
        $p1 = Post::create([
            'user_id' => $astro->id,
            'content' => 'Just configured the solar sails on the React 19 compiler. The latency overhead across interstellar relays has dropped by exactly 42ms. Absolute magic. #reactCompiler #quantumComputing 🚀☄️',
            'alignment' => 'left',
        ]);

        $p2 = Post::create([
            'user_id' => $minimalist->id,
            'content' => 'Visual clarity is not about what you add; it is about what you leave behind. Every rule, every pixel, every font weight must justify its presence. Build light. Breathe deep. #minimalistDesign #ambientWeb',
            'alignment' => 'left',
        ]);

        $p3 = Post::create([
            'user_id' => $protocol->id,
            'content' => 'Welcome to Aether. A premium, glassmorphic space designed for deep content and micro-transmissions. Our nodes are synchronized, and the solar winds are favorable. Synthesize your first message above. #vite8Release #ambientWeb',
            'alignment' => 'center',
        ]);

        // 4. Create Likes & Bookmarks
        Like::create(['user_id' => $pilot->id, 'post_id' => $p1->id]);
        Bookmark::create(['user_id' => $pilot->id, 'post_id' => $p1->id]);
        Like::create(['user_id' => $pilot->id, 'post_id' => $p3->id]);

        // 5. Create Comments & Nested replies
        $c1 = Comment::create([
            'post_id' => $p1->id,
            'user_id' => $pilot->id,
            'content' => 'Insane optimization! Did you run it on the Vercel edge runtime too?',
        ]);

        Comment::create([
            'post_id' => $p1->id,
            'user_id' => $astro->id,
            'parent_id' => $c1->id,
            'content' => 'Yes! Deployed edge function cold start dropped to 0ms. Insane speed.',
        ]);

        Comment::create([
            'post_id' => $p1->id,
            'user_id' => $minimalist->id,
            'content' => '42ms is huge for deep space routing. Excellent work.',
        ]);

        Comment::create([
            'post_id' => $p2->id,
            'user_id' => $astro->id,
            'content' => "Couldn't agree more. Less code, fewer renders, clearer mind.",
        ]);

        Comment::create([
            'post_id' => $p3->id,
            'user_id' => $minimalist->id,
            'content' => 'The glassmorphism looks absolutely stunning. The performance is very responsive.',
        ]);

        // 6. Create follows: others follow pilot so pilot receives follow notifications
        $pilot->followers()->attach($astro->id);
        $pilot->followers()->attach($minimalist->id);

        // 7. Create likes on pilot's posts by others
        // First create a pilot post to receive likes/comments on
        $pilotPost = Post::create([
            'user_id' => $pilot->id,
            'content' => 'Initializing Aether node. Running diagnostics on the quantum relay stack. All systems nominal. #vite8Release #ambientWeb 🛰️',
            'alignment' => 'left',
        ]);

        Like::create(['user_id' => $astro->id, 'post_id' => $pilotPost->id]);
        Like::create(['user_id' => $minimalist->id, 'post_id' => $pilotPost->id]);
        Like::create(['user_id' => $protocol->id, 'post_id' => $pilotPost->id]);

        $pilotComment = Comment::create([
            'post_id' => $pilotPost->id,
            'user_id' => $astro->id,
            'content' => 'Welcome to the network! Your relay stack looks solid.',
        ]);

        Comment::create([
            'post_id' => $pilotPost->id,
            'user_id' => $protocol->id,
            'content' => 'Aether nodes are fully synchronized. Welcome aboard.',
        ]);

        // 8. Seed Notifications FOR pilot user (user_id = pilot->id)
        // System welcome
        \App\Models\Notification::create([
            'user_id' => $pilot->id,
            'sender_id' => null,
            'type' => 'system',
            'is_read' => false,
        ]);

        // Follow notification: astro followed pilot
        \App\Models\Notification::create([
            'user_id' => $pilot->id,
            'sender_id' => $astro->id,
            'type' => 'follow',
            'is_read' => false,
        ]);

        // Follow notification: minimalist followed pilot
        \App\Models\Notification::create([
            'user_id' => $pilot->id,
            'sender_id' => $minimalist->id,
            'type' => 'follow',
            'is_read' => true,
        ]);

        // Like notification: astro liked pilot's post
        \App\Models\Notification::create([
            'user_id' => $pilot->id,
            'sender_id' => $astro->id,
            'type' => 'like',
            'post_id' => $pilotPost->id,
            'is_read' => false,
        ]);

        // Like notification: minimalist liked pilot's post
        \App\Models\Notification::create([
            'user_id' => $pilot->id,
            'sender_id' => $minimalist->id,
            'type' => 'like',
            'post_id' => $pilotPost->id,
            'is_read' => true,
        ]);

        // Like notification: protocol liked pilot's post
        \App\Models\Notification::create([
            'user_id' => $pilot->id,
            'sender_id' => $protocol->id,
            'type' => 'like',
            'post_id' => $pilotPost->id,
            'is_read' => true,
        ]);

        // Comment notification: astro commented on pilot's post
        \App\Models\Notification::create([
            'user_id' => $pilot->id,
            'sender_id' => $astro->id,
            'type' => 'comment',
            'post_id' => $pilotPost->id,
            'comment_id' => $pilotComment->id,
            'is_read' => false,
        ]);

        // Repost notification: astro reposted pilot's post
        $astroRepost = Post::create([
            'user_id' => $astro->id,
            'content' => $pilotPost->content,
            'original_post_id' => $pilotPost->id,
            'is_retransmission' => true,
        ]);

        \App\Models\Notification::create([
            'user_id' => $pilot->id,
            'sender_id' => $astro->id,
            'type' => 'repost',
            'post_id' => $pilotPost->id,
            'is_read' => true,
        ]);
    }
}
