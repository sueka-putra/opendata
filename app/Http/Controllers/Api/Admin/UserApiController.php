<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\BdContact;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    use JsonEnvelope;

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
        ]);

        $payload = [
            'event' => 'OpenData',
            'wg' => 'WGDSA',
            'country_code' => $data['country_code'],
            'person_name' => $data['person_name'],
            'title' => $data['title'] ?? null,
            'agency' => $data['agency'] ?? null,
            'email' => $data['email'],
            'remarks' => $data['remarks'] ?? null,
        ];

        if (!empty($data['id'])) {
            $u = $this->baseQuery()->findOrFail($data['id']);
            $u->update($payload);
            return $this->ok(['id' => $u->id]);
        }

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
}
