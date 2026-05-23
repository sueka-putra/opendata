<?php

namespace App\Http\Controllers;

use App\Models\BdContact;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the profile form.
     */
    public function edit(Request $request): View
    {
        $context = $this->resolveContext($request);

        return view('profile.edit', [
            'user' => $context['target'],
            'actor' => $context['actor'],
            'isAdmin' => $context['is_admin'],
            'isManagingOtherUser' => $context['is_managing_other_user'],
            'targetUserId' => $context['target_user_id'],
            'countryOptions' => $this->countryOptions(),
        ]);
    }

    /**
     * Display dedicated change password page for current user.
     */
    public function changePassword(Request $request): View
    {
        $context = $this->resolveContext($request);

        return view('profile.change-password', [
            'user' => $context['target'],
            'actor' => $context['actor'],
            'isAdmin' => $context['is_admin'],
            'isManagingOtherUser' => $context['is_managing_other_user'],
            'targetUserId' => $context['target_user_id'],
        ]);
    }

    /**
     * Update profile fields.
     */
    public function update(Request $request): RedirectResponse
    {
        $context = $this->resolveContext($request);

        if (!$context['is_admin']) {
            $targetEmail = strtolower(trim((string) $context['target']->email));
            $incomingEmail = strtolower(trim((string) $request->input('email', $targetEmail)));
            if ($incomingEmail !== $targetEmail) {
                return Redirect::to($this->profileUrl($context['target_user_id']))
                    ->withErrors(['email' => 'You are not allowed to change email.']);
            }

            $incomingCountry = (string) $request->input('country_code', $context['target']->country_code);
            if ($incomingCountry !== (string) $context['target']->country_code) {
                return Redirect::to($this->profileUrl($context['target_user_id']))
                    ->withErrors(['country_code' => 'You are not allowed to change country.']);
            }
        }

        $rules = [
            'title' => ['nullable', 'string', 'max:5'],
            'person_name' => ['required', 'string', 'max:60'],
            'remarks' => ['nullable', 'string', 'max:200'],
        ];

        if ($context['is_admin']) {
            $rules['country_code'] = ['required', 'string', 'max:5'];
            $rules['isSelected'] = ['nullable', 'boolean'];
        }

        $validated = $request->validate($rules);

        $target = $context['target'];
        $target->title = $validated['title'] ?? null;
        $target->person_name = $validated['person_name'];
        $target->remarks = $validated['remarks'] ?? null;

        if ($context['is_admin']) {
            $target->country_code = $validated['country_code'];
            $target->isSelected = (int) ($request->boolean('isSelected'));
        }

        $target->save();

        return Redirect::to($this->profileUrl($context['target_user_id']))
            ->with('status', 'profile-updated');
    }

    /**
     * Update password from profile page.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $context = $this->resolveContext($request);

        if ($context['is_admin'] && $context['is_managing_other_user']) {
            $validated = $request->validateWithBag('updatePassword', [
                'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            ]);
        } else {
            $validated = $request->validateWithBag('updatePassword', [
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            ]);
        }

        $context['target']->password = Hash::make($validated['password']);
        $context['target']->save();

        if ($context['target_user_id']) {
            return Redirect::to($this->profileUrl($context['target_user_id']))
                ->with('status', 'password-updated');
        }

        return Redirect::route('password.edit')
            ->with('status', 'password-updated');
    }

    /**
     * Delete user account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $context = $this->resolveContext($request);

        if ($context['is_admin'] && $context['is_managing_other_user']) {
            $context['target']->delete();

            return Redirect::route('adm.users')->with('status', 'user-deleted');
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * @return array{actor:mixed,target:mixed,is_admin:bool,is_managing_other_user:bool,target_user_id:int|null}
     */
    private function resolveContext(Request $request): array
    {
        $actor = $request->user();
        $isAdmin = $actor && method_exists($actor, 'isAdmin') && $actor->isAdmin();

        $requestedUserId = (int) ($request->query('user_id', $request->input('user_id')) ?? 0);

        if (!$isAdmin && $requestedUserId > 0 && (int) $actor->id !== $requestedUserId) {
            abort(403, 'You are not allowed to manage other users.');
        }

        $isManagingOtherUser = $isAdmin
            && $requestedUserId > 0
            && (int) $actor->id !== $requestedUserId;

        if ($isManagingOtherUser) {
            $target = BdContact::query()
                ->where('event', 'OpenData')
                ->where('wg', 'WGDSA')
                ->findOrFail($requestedUserId);

            return [
                'actor' => $actor,
                'target' => $target,
                'is_admin' => true,
                'is_managing_other_user' => true,
                'target_user_id' => (int) $target->id,
            ];
        }

        return [
            'actor' => $actor,
            'target' => $actor,
            'is_admin' => (bool) $isAdmin,
            'is_managing_other_user' => false,
            'target_user_id' => null,
        ];
    }

    private function profileUrl(?int $targetUserId): string
    {
        if ($targetUserId) {
            return route('profile.edit', ['user_id' => $targetUserId]);
        }

        return route('profile.edit');
    }

    private function countryOptions(): array
    {
        $countries = Country::query()
            ->where('is_asean', true)
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn (Country $country) => [
                'code' => (string) $country->code,
                'name' => (string) $country->name,
            ])
            ->all();

        $hasAdminCountry = collect($countries)->contains(fn (array $country) => $country['code'] === '00');

        if (!$hasAdminCountry) {
            array_unshift($countries, [
                'code' => '00',
                'name' => 'ASEANstats',
            ]);
        }

        return $countries;
    }
}
