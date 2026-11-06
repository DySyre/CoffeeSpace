<?php

use App\Livewire\Page\Admin;
use App\Models\User;
use Livewire\Livewire;

test('only admins can access moderation and moderate accounts and posts', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = User::factory()->create();
    $post = $member->posts()->create([
        'author_handle' => 'member', 'title' => 'A post to moderate', 'body' => 'Story',
        'cafe_name' => 'Cafe', 'city' => 'Manila',
    ]);

    $this->actingAs($member)->get(route('admin.index'))->assertForbidden();
    Livewire::actingAs($member)->test(Admin::class)->assertForbidden();

    Livewire::actingAs($admin)->test(Admin::class)
        ->call('toggleUserStatus', $member->id)->assertHasNoErrors()
        ->call('removePost', $post->id)->assertHasNoErrors();

    expect($member->fresh()->is_active)->toBeFalse();
    $this->assertSoftDeleted($post);
    expect($post->fresh()->title)->toBe('A post to moderate');
});

test('inactive members cannot log in and admins cannot deactivate themselves', function () {
    $member = User::factory()->create(['is_active' => false]);
    $this->post(route('login.store'), [
        'email' => $member->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $admin = User::factory()->create(['role' => 'admin']);
    Livewire::actingAs($admin)->test(Admin::class)->call('toggleUserStatus', $admin->id)->assertForbidden();
    expect($admin->fresh()->is_active)->toBeTrue();
});
