<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Mail\TemporaryPasswordGeneratedMail;
use App\Models\BdContact;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserApiController extends Controller
{
    use JsonEnvelope;
    private const DEFAULT_TEMPORARY_COPY_EMAIL = 'sueka.putra@asean.org';

    private function baseQuery()
    {
        return BdContact::query()
            ->where('event', 'OpenData')
            ->where('wg', 'WGDSA');
    }

    public function index()
    {
        return $this->ok($this->baseQuery()->orderBy('country_code')->orderBy('person_name')->get());
    }

    public function show(int $id)
    {
        return $this->ok($this->baseQuery()->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'country_code' => 'required|string|max:5',
            'person_name' => 'required|string|max:60',
            'title' => 'nullable|string|max:5',
            'agency' => 'nullable|string|max:300',
            'email' => 'required|email|max:100',
            'remarks' => 'nullable|string|max:200',
            'password' => 'nullable|string|min:8|max:100|confirmed',
        ]);

        $email = strtolower(trim($data['email']));
        $existingByEmail = $this->baseQuery()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        $payload = [
            'event' => 'OpenData',
            'wg' => 'WGDSA',
            'country_code' => $data['country_code'],
            'person_name' => $data['person_name'],
            'title' => $data['title'] ?? null,
            'agency' => $data['agency'] ?? null,
            'email' => $email,
            'remarks' => $data['remarks'] ?? null,
        ];

        if (!empty($data['id'])) {
            $u = $this->baseQuery()->findOrFail($data['id']);
            if ($existingByEmail && (int) $existingByEmail->id !== (int) $u->id) {
                return $this->fail('Email sudah terdaftar untuk user lain.', 409);
            }
            $updatePayload = $payload;
            if (!empty($data['password'])) {
                $updatePayload['password'] = Hash::make($data['password']);
            }
            $u->update($updatePayload);
            return $this->ok(['id' => $u->id]);
        }

        if ($existingByEmail) {
            return $this->fail('Email sudah terdaftar. Gunakan email lain atau edit user yang sudah ada.', 409);
        }

        if (empty($data['password'])) {
            return $this->fail('Password is required for new user', 422);
        }

        $payload['password'] = Hash::make($data['password']);
        $u = BdContact::create($payload);
        return $this->ok(['id' => $u->id]);
    }

    public function update(Request $request, int $id)
    {
        $request->merge(['id' => $id]);
        return $this->store($request);
    }

    public function destroy(int $id)
    {
        $u = $this->baseQuery()->findOrFail($id);
        $u->delete();
        return $this->ok(null, 'deleted');
    }

    public function generateTemporaryPasswords(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct'],
        ]);

        $ids = collect(Arr::get($validated, 'user_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            return $this->fail('No users selected.', 422);
        }

        $users = $this->baseQuery()
            ->whereIn('id', $ids->all())
            ->get()
            ->keyBy('id');

        $result = [
            'selected' => $ids->count(),
            'updated' => 0,
            'emails_sent' => 0,
            'failed' => [],
        ];
        $ccRecipients = $this->resolveTemporaryCopyRecipients();

        foreach ($ids as $id) {
            $user = $users->get($id);
            if (!$user) {
                $result['failed'][] = [
                    'id' => $id,
                    'email' => null,
                    'reason' => 'User not found or not eligible.',
                ];
                continue;
            }

            $email = strtolower(trim((string) ($user->email ?? '')));
            if ($email === '') {
                $result['failed'][] = [
                    'id' => (int) $user->id,
                    'email' => null,
                    'reason' => 'User has no email address.',
                ];
                continue;
            }

            $temporaryPassword = Str::password(12, true, true, true, false);
            $user->password = Hash::make($temporaryPassword);
            if ($this->bdContactsHasColumn('must_change_password')) {
                $user->must_change_password = true;
            }
            if ($this->bdContactsHasColumn('password_generated_at')) {
                $user->password_generated_at = now();
            }
            $user->save();
            $result['updated']++;

            try {
                Mail::to($email)->cc($ccRecipients)->send(new TemporaryPasswordGeneratedMail(
                    (string) ($user->person_name ?: $user->name ?: 'User'),
                    $temporaryPassword
                ));
                $result['emails_sent']++;
            } catch (\Throwable $e) {
                $result['failed'][] = [
                    'id' => (int) $user->id,
                    'email' => $email,
                    'reason' => 'Email delivery failed.',
                ];
            }
        }

        $result['failed_count'] = count($result['failed']);
        return $this->ok($result);
    }

    private function bdContactsHasColumn(string $column): bool
    {
        static $columns = null;
        if (is_array($columns)) {
            return in_array($column, $columns, true);
        }

        try {
            $columns = array_map(
                static fn ($name) => (string) $name,
                Schema::getColumnListing('bd_contacts')
            );
        } catch (\Throwable $e) {
            $columns = [];
        }

        return in_array($column, $columns, true);
    }

    /**
     * @return array<int, string>
     */
    private function resolveTemporaryCopyRecipients(): array
    {
        $raw = (string) config('opendata.temporary_copy', '');
        $candidates = preg_split('/[,\n;\s]+/', $raw) ?: [];
        $emails = collect($candidates)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        if (!empty($emails)) {
            return $emails;
        }

        return [self::DEFAULT_TEMPORARY_COPY_EMAIL];
    }
}
