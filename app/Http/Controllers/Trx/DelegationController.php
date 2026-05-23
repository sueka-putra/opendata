<?php

namespace App\Http\Controllers\Trx;

use App\Http\Controllers\Controller;
use App\Models\BdContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DelegationController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        if ((int) ($actor->isSelected ?? 0) !== 1) {
            abort(403, 'You are not allowed to access Delegation.');
        }

        $delegations = BdContact::query()
            ->whereRaw('LOWER(event) = ?', ['opendata'])
            ->where('country_code', (string) $actor->country_code)
            ->orderBy('person_name')
            ->orderBy('email')
            ->get([
                'id',
                'email',
                'person_name',
                'country_code',
                'title',
                'agency',
                'wg',
                'isSelected',
                'created_at',
            ]);

        return view('trx.delegation', [
            'delegations' => $delegations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        if ((int) ($actor->isSelected ?? 0) !== 1) {
            abort(403, 'You are not allowed to manage Delegation.');
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:100', 'unique:bd_contacts,email'],
            'person_name' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        BdContact::query()->create([
            'email' => strtolower(trim((string) $data['email'])),
            'person_name' => trim((string) $data['person_name']),
            'password' => Hash::make($data['password']),
            'country_code' => (string) $actor->country_code,
            'wg' => (string) ($actor->wg ?? ''),
            'title' => (string) ($actor->title ?? ''),
            'agency' => (string) ($actor->agency ?? ''),
            'event' => (string) ($actor->event ?: 'OpenData'),
            'isSelected' => 0,
        ]);

        return redirect()->route('trx.delegation.index')->with('status', 'delegation-created');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $actor = $request->user();
        if ((int) ($actor->isSelected ?? 0) !== 1) {
            abort(403, 'You are not allowed to manage Delegation.');
        }

        $target = BdContact::query()
            ->where('id', $id)
            ->whereRaw('LOWER(event) = ?', ['opendata'])
            ->where('country_code', (string) $actor->country_code)
            ->firstOrFail();

        if ((int) ($target->isSelected ?? 0) === 1) {
            return redirect()->route('trx.delegation.index')
                ->withErrors(['delegation' => 'Default user cannot be deleted.']);
        }

        $target->delete();

        return redirect()->route('trx.delegation.index')->with('status', 'delegation-deleted');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $actor = $request->user();
        if ((int) ($actor->isSelected ?? 0) !== 1) {
            abort(403, 'You are not allowed to manage Delegation.');
        }

        $target = BdContact::query()
            ->where('id', $id)
            ->whereRaw('LOWER(event) = ?', ['opendata'])
            ->where('country_code', (string) $actor->country_code)
            ->firstOrFail();

        if ((int) ($target->isSelected ?? 0) === 1) {
            return redirect()->route('trx.delegation.index')
                ->withErrors(['delegation' => 'Default user cannot be edited.']);
        }

        $data = $request->validate([
            'person_name' => ['required', 'string', 'max:60'],
            'password' => ['nullable', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        $target->person_name = trim((string) $data['person_name']);
        if (!empty($data['password'])) {
            $target->password = Hash::make((string) $data['password']);
        }
        $target->save();

        return redirect()->route('trx.delegation.index')->with('status', 'delegation-updated');
    }
}
