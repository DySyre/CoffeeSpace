<?php

use App\Livewire\Posts\PostComments;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\CoffeeDemoSeeder;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

function commentablePost(): Post
{
    test()->seed(CoffeeDemoSeeder::class);

    return Post::firstOrFail();
}

test('a signed in user can save a comment belonging to its post and author', function () {
    $post = commentablePost();
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(PostComments::class, ['postId' => $post->id])
        ->set('body', '  This café looks lovely!  ')
        ->call('addComment')
        ->assertHasNoErrors()
        ->assertSet('body', '')
        ->assertSet('commentCount', 1)
        ->assertSee('This café looks lovely!');

    $comment = $post->comments()->sole();
    expect($comment->user->is($user))->toBeTrue();
    expect($comment->post->is($post))->toBeTrue();
    expect($comment->body)->toBe('This café looks lovely!');
    expect($user->comments()->count())->toBe(1);
    Livewire::test(PostComments::class, ['postId' => $post->id])->call('toggle')->assertSee('This café looks lovely!');
});

test('guests cannot submit comments', function () {
    $post = commentablePost();

    Livewire::test(PostComments::class, ['postId' => $post->id])->set('body', 'Guest comment')->call('addComment')->assertStatus(401);

    $this->assertDatabaseCount('comments', 0);
});

test('empty and oversized comments are rejected', function (string $body) {
    $post = commentablePost();
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(PostComments::class, ['postId' => $post->id])
        ->set('body', $body)->call('addComment')->assertHasErrors('body');

    $this->assertDatabaseCount('comments', 0);
})->with(['blank' => '   ', 'too long' => str_repeat('a', 1001)]);

test('a nonpublic post refuses comment reads and writes', function (string $visibility) {
    $post = commentablePost();
    $post->update(['visibility' => $visibility]);
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(PostComments::class, ['postId' => $post->id])
        ->set('body', 'Should not be stored')->call('addComment')->assertStatus(404);
    Livewire::test(PostComments::class, ['postId' => $post->id])->call('toggle')->assertStatus(404);

    $this->assertDatabaseCount('comments', 0);
})->with(['private', 'followers']);

test('comment text is escaped in the rendered page', function () {
    $post = commentablePost();
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(PostComments::class, ['postId' => $post->id])
        ->set('body', '<script>alert(1)</script>')->call('addComment')
        ->assertSeeHtml('&lt;script&gt;alert(1)&lt;/script&gt;');
});

test('comments from other posts are excluded and older comments can be loaded', function () {
    $post = commentablePost();
    $otherPost = Post::whereKeyNot($post->id)->firstOrFail();
    foreach (range(1, 7) as $number) {
        $comment = $post->comments()->make(['body' => 'Visit note '.$number]);
        $comment->user()->associate($post->user);
        $comment->save();
    }
    $other = $otherPost->comments()->make(['body' => 'Unrelated comment']);
    $other->user()->associate($post->user);
    $other->save();

    Livewire::test(PostComments::class, ['postId' => $post->id])->call('toggle')
        ->assertSee('Visit note 7')->assertDontSee('Visit note 1')
        ->assertDontSee('Unrelated comment')->call('loadMore')->assertSee('Visit note 1');
});

test('posting limits block another comment', function () {
    $post = commentablePost();
    $user = User::factory()->create();
    foreach (range(1, 5) as $attempt) {
        RateLimiter::hit('post-comments:'.$user->id, 60);
    }

    Livewire::actingAs($user)->test(PostComments::class, ['postId' => $post->id])
        ->set('body', 'One more')->call('addComment')->assertHasErrors('body');

    $this->assertDatabaseCount('comments', 0);
});

test('deleting a post removes its comments', function () {
    $post = commentablePost();
    $comment = $post->comments()->make(['body' => 'A memory']);
    $comment->user()->associate($post->user);
    $comment->save();

    $post->delete();

    $this->assertModelMissing($comment);
});
