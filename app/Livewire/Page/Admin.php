<?php

namespace App\Livewire\Page;

use App\Models\CommentReport;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Admin extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'people';

    #[Url]
    public string $search = '';

    public function updatedTab(): void
    {
        $this->resetPage(pageName: 'peoplePage');
        $this->resetPage(pageName: 'postsPage');
        $this->resetPage(pageName: 'postReportsPage');
        $this->resetPage(pageName: 'commentReportsPage');
    }

    public function updatedSearch(): void
    {
        $this->resetPage(pageName: 'peoplePage');
        $this->resetPage(pageName: 'postsPage');
        $this->resetPage(pageName: 'postReportsPage');
        $this->resetPage(pageName: 'commentReportsPage');
    }

    public function mount(): void
    {
        $this->authorizeAdmin();
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function toggleUserStatus(int $userId): void
    {
        $this->authorizeAdmin();
        $user = User::findOrFail($userId);
        abort_if($user->role === 'admin' || $user->is(auth()->user()), 403);
        $user->is_active = ! $user->is_active;
        $user->save();

        $this->dispatch('admin-action', message: $user->is_active ? 'Account reactivated.' : 'Account deactivated.');
    }

    public function removePost(int $postId): void
    {
        $this->authorizeAdmin();
        $post = Post::withTrashed()->findOrFail($postId);
        abort_if($post->user?->role === 'admin', 403);
        if (! $post->trashed()) {
            $post->delete();
        }
        $post->reports()->where('status', 'pending')->update(['status' => 'reviewed']);
        $this->dispatch('admin-action', message: 'Post moved to Trash. Its media files are preserved.');
    }

    public function dismissPostReport(int $reportId): void
    {
        $this->authorizeAdmin();
        PostReport::query()->where('status', 'pending')->findOrFail($reportId)->update(['status' => 'reviewed']);
    }

    public function dismissCommentReport(int $reportId): void
    {
        $this->authorizeAdmin();
        CommentReport::query()->where('status', 'pending')->findOrFail($reportId)->update(['status' => 'reviewed']);
    }

    public function removeReportedComment(int $reportId): void
    {
        $this->authorizeAdmin();
        $report = CommentReport::query()->where('status', 'pending')->with('comment.user')->findOrFail($reportId);
        abort_if($report->comment->user?->role === 'admin', 403);
        $report->comment->delete();
    }

    public function render(): View
    {
        $this->authorizeAdmin();

        abort_unless(in_array($this->tab, ['people', 'posts', 'reported'], true), 404);

        $search = trim($this->search);
        $users = $this->tab === 'people'
            ? User::query()
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                }))
                ->orderBy('name')
                ->paginate(15, pageName: 'peoplePage')
            : null;
        $posts = $this->tab === 'posts'
            ? Post::withTrashed()->with('user')->withCount(['reports as pending_reports_count' => fn ($reports) => $reports->where('status', 'pending')])
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('cafe_name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->when(strtolower($search) === 'reported', fn ($query) => $query->orWhereHas('reports', fn ($reports) => $reports->where('status', 'pending')));
                }))
                ->orderByDesc('pending_reports_count')
                ->latest()
                ->paginate(15, pageName: 'postsPage')
            : null;
        $postReports = $this->tab === 'reported'
            ? PostReport::query()->where('status', 'pending')
                ->with(['post' => fn ($post) => $post->withTrashed()->with('user'), 'user'])
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('post', fn ($post) => $post->withTrashed()->where(function ($post) use ($search) {
                            $post->where('title', 'like', "%{$search}%")
                                ->orWhere('cafe_name', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%")
                                ->orWhereHas('user', fn ($author) => $author->where('name', 'like', "%{$search}%"));
                        }));
                }))
                ->latest()
                ->paginate(15, pageName: 'postReportsPage')
            : null;
        $commentReports = $this->tab === 'reported'
            ? CommentReport::query()->where('status', 'pending')
                ->with(['comment.user', 'comment.post' => fn ($post) => $post->withTrashed(), 'user'])
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('comment', fn ($comment) => $comment->where('body', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($author) => $author->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('post', fn ($post) => $post->withTrashed()->where('title', 'like', "%{$search}%")));
                }))
                ->latest()
                ->paginate(15, pageName: 'commentReportsPage')
            : null;

        return view('livewire.page.admin', [
            'users' => $users,
            'posts' => $posts,
            'postReports' => $postReports,
            'commentReports' => $commentReports,
        ])->layout('components.layouts.coffee', ['title' => 'Admin · CoffeeSpace', 'section' => 'profile']);
    }
}
