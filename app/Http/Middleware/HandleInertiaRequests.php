<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $this->shareUser($request->user()),
            ],
            'featureFlags' => config('codesprout.features', []),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function shareUser(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing(['profile', 'childProfile', 'teacherProfile', 'roles']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'dashboard_route' => $user->dashboard_route,
            'primary_role' => $user->primary_role,
            'roles' => $user->getRoleNames()->values()->all(),
            'is_active' => $user->isActiveAccount(),
            'profile' => $user->profile ? [
                'first_name' => $user->profile->first_name,
                'last_name' => $user->profile->last_name,
                'preferred_name' => $user->profile->preferred_name,
                'full_name' => $user->profile->full_name,
                'display_name' => $user->profile->display_name,
                'age' => $user->profile->age,
                'date_of_birth' => $user->profile->date_of_birth?->toDateString(),
                'avatar_url' => $user->profile->avatar_url,
            ] : null,
            'child_profile' => $user->childProfile ? [
                'learner_id' => $user->childProfile->learner_id,
                'pin_mode' => $user->childProfile->pin_mode,
                'last_pin_verified_at' => $user->childProfile->last_pin_verified_at?->toIso8601String(),
            ] : null,
            'teacher_profile' => $user->teacherProfile ? [
                'staff_code' => $user->teacherProfile->staff_code,
                'job_title' => $user->teacherProfile->job_title,
                'subject_focus' => $user->teacherProfile->subject_focus,
            ] : null,
        ];
    }
}
