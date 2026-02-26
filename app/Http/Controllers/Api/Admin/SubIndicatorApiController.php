<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\Aggregation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubIndicatorApiController extends Controller
{
    use JsonEnvelope;

    public function index()
    {
        return $this->ok(Aggregation::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'title' => 'required|string|max:50',
            'description' => 'required|string|max:300',
            'active' => 'required|boolean',
        ]);

        $userId = (int) $request->user()->id;
        if (!empty($data['id'])) {
            $a = Aggregation::findOrFail($data['id']);
            $a->update([
                'title' => $data['title'],
                'description' => $data['description'],
                'active' => $data['active'],
                'modified_by' => $userId,
            ]);
            return $this->ok(['id' => $a->id]);
        }

        $a = Aggregation::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'active' => $data['active'],
            'created_by' => $userId,
            'modified_by' => $userId,
        ]);

        return $this->ok(['id' => $a->id]);
    }

    public function destroy(int $id)
    {
        $used = DB::table('od_trx_assessment_period_rows')->where('sub_indicator_id', $id)->exists();
        if ($used) {
            return $this->fail('Sub-indicator has been used and cannot be deleted', 409);
        }
        Aggregation::where('id', $id)->delete();
        return $this->ok(null, 'deleted');
    }
}
