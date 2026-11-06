<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">Admin</flux:heading>
        <flux:subheading size="lg" class="mb-6">Manage people and posts across CoffeeSpace</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="flex items-start max-md:flex-col">
        <nav class="mr-10 w-full pb-4 md:w-[220px]" aria-label="Admin sections">
            <flux:navlist>
                <flux:navlist.item href="{{ route('admin.index', ['tab' => 'people']) }}" wire:navigate :current="$tab === 'people'">People</flux:navlist.item>
                <flux:navlist.item href="{{ route('admin.index', ['tab' => 'posts']) }}" wire:navigate :current="$tab === 'posts'">Posts</flux:navlist.item>
                <flux:navlist.item href="{{ route('admin.index', ['tab' => 'reported']) }}" wire:navigate :current="$tab === 'reported'">Reported</flux:navlist.item>
            </flux:navlist>
        </nav>

        <flux:separator class="md:hidden" />

        <div class="flex-1 self-stretch max-md:pt-6">
            <flux:heading>{{ ucfirst($tab) }}</flux:heading>
            <flux:subheading>{{ match($tab) { 'people' => 'Search accounts and manage access', 'posts' => 'Find and remove posts from the community', default => 'Review posts and comments reported by the community' } }}</flux:subheading>

            <div class="mt-5 w-full max-w-2xl">
                <label for="admin-search" class="sr-only">Search {{ $tab }}</label>
                <flux:input id="admin-search" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ $tab === 'people' ? 'Search name, email, or role' : ($tab === 'posts' ? 'Search title, café, city, or author' : 'Search reported posts, comments, or reporters') }}" />

                @if($tab === 'people')
                    <div class="mt-5 divide-y divide-line">
                        @forelse($users as $user)
                            <div wire:key="admin-user-{{ $user->id }}" class="flex items-center justify-between gap-4 py-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink">{{ $user->name }} <span class="font-normal text-muted">· {{ $user->role ?? 'user' }}</span></p>
                                    <p class="truncate text-sm text-muted">{{ $user->email }} · {{ $user->is_active ? 'Active' : 'Deactivated' }}</p>
                                </div>
                                @unless($user->role === 'admin' || $user->is(auth()->user()))
                                    <flux:button size="sm" variant="ghost" wire:click="toggleUserStatus({{ $user->id }})" wire:confirm="{{ $user->is_active ? 'Deactivate this account?' : 'Reactivate this account?' }}">{{ $user->is_active ? 'Deactivate' : 'Reactivate' }}</flux:button>
                                @endunless
                            </div>
                        @empty
                            <div class="py-12 text-center">
                                <flux:heading size="sm">{{ $search === '' ? 'No people to show' : 'No people found' }}</flux:heading>
                                <flux:subheading>{{ $search === '' ? 'Accounts will appear here when people join.' : 'Try another name, email, or role.' }}</flux:subheading>
                            </div>
                        @endforelse
                    </div>
                    @if($users->hasPages())<div class="mt-4">{{ $users->links() }}</div>@endif
                @elseif($tab === 'posts')
                    <div class="mt-5 divide-y divide-line">
                        @forelse($posts as $post)
                            <div wire:key="admin-post-{{ $post->id }}" class="flex items-center justify-between gap-4 py-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink">{{ $post->title }}</p>
                                    <p class="truncate text-sm text-muted">{{ $post->user?->name ?? 'Unknown author' }} · {{ $post->trashed() ? 'In trash' : $post->cafe_name }} @if($post->pending_reports_count > 0)<span class="font-medium text-brand">· {{ $post->pending_reports_count }} {{ \Illuminate\Support\Str::plural('report', $post->pending_reports_count) }}</span>@endif</p>
                                </div>
                                @if(! $post->trashed() && $post->user?->role !== 'admin')
                                    <flux:button size="sm" variant="danger" wire:click="removePost({{ $post->id }})" wire:confirm="Move this post to Trash? Its media files will be preserved.">Remove</flux:button>
                                @endif
                            </div>
                        @empty
                            <div class="py-12 text-center">
                                <flux:heading size="sm">{{ $search === '' ? 'No posts to show' : 'No posts found' }}</flux:heading>
                                <flux:subheading>{{ $search === '' ? 'Community posts will appear here.' : 'Try another title, café, city, or author.' }}</flux:subheading>
                            </div>
                        @endforelse
                    </div>
                    @if($posts->hasPages())<div class="mt-4">{{ $posts->links() }}</div>@endif
                @else
                    <div class="mt-5 space-y-8">
                        <section aria-labelledby="reported-posts-heading">
                            <flux:heading id="reported-posts-heading" size="sm">Reported posts</flux:heading>
                            <div class="mt-2 divide-y divide-line border-y border-line">
                                @forelse($postReports as $report)
                                    <article wire:key="reported-post-{{ $report->id }}" class="space-y-2 py-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0"><p class="text-sm font-semibold">{{ $report->post?->title ?? 'Post unavailable' }}</p><p class="text-xs text-muted">{{ $report->post?->user?->name ?? 'Unknown author' }} · Reported by {{ $report->user->name }} · {{ $report->created_at->diffForHumans() }}</p></div>
                                            <div class="flex shrink-0 gap-2"><flux:button size="sm" variant="ghost" wire:click="dismissPostReport({{ $report->id }})">Dismiss</flux:button>@if($report->post && ! $report->post->trashed() && $report->post->user?->role !== 'admin')<flux:button size="sm" variant="danger" wire:click="removePost({{ $report->post->id }})" wire:confirm="Move this post to Trash? Its media files will be preserved.">Remove post</flux:button>@endif</div>
                                        </div>
                                        @if($report->post)<p class="text-xs text-muted">{{ $report->post->cafe_name }} · {{ $report->post->city }}</p>@endif
                                    </article>
                                @empty
                                    <p class="py-6 text-sm text-muted">{{ $search === '' ? 'No pending post reports.' : 'No reported posts match your search.' }}</p>
                                @endforelse
                            </div>
                            @if($postReports->hasPages())<div class="mt-3">{{ $postReports->links() }}</div>@endif
                        </section>

                        <section aria-labelledby="reported-comments-heading">
                            <flux:heading id="reported-comments-heading" size="sm">Reported comments</flux:heading>
                            <div class="mt-2 divide-y divide-line border-y border-line">
                                @forelse($commentReports as $report)
                                    <article wire:key="reported-comment-{{ $report->id }}" class="space-y-2 py-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0"><p class="text-sm font-semibold">{{ $report->comment?->user?->name ?? 'Unknown author' }} · {{ $report->created_at->diffForHumans() }}</p><p class="text-xs text-muted">On {{ $report->comment?->post?->title ?? 'Post unavailable' }} · Reported by {{ $report->user->name }}</p></div>
                                            <div class="flex shrink-0 gap-2"><flux:button size="sm" variant="ghost" wire:click="dismissCommentReport({{ $report->id }})">Dismiss</flux:button>@if($report->comment && $report->comment->user?->role !== 'admin')<flux:button size="sm" variant="danger" wire:click="removeReportedComment({{ $report->id }})" wire:confirm="Permanently remove this comment and its replies?">Remove comment</flux:button>@endif</div>
                                        </div>
                                        @if($report->comment)<p class="whitespace-pre-wrap break-words text-sm leading-6">{{ $report->comment->body }}</p>@endif
                                    </article>
                                @empty
                                    <p class="py-6 text-sm text-muted">{{ $search === '' ? 'No pending comment reports.' : 'No reported comments match your search.' }}</p>
                                @endforelse
                            </div>
                            @if($commentReports->hasPages())<div class="mt-3">{{ $commentReports->links() }}</div>@endif
                        </section>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
