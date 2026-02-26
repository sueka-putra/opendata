<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionApiController extends Controller
{
    use JsonEnvelope;

    public function index()
    {
        return $this->ok(Section::orderBy('id')->get());
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

        $section = null;
        if (!empty($data['id'])) {
            $section = Section::findOrFail($data['id']);
            $section->update([
                'title' => $data['title'],
                'description' => $data['description'],
                'active' => $data['active'],
                'modified_by' => $userId,
            ]);
        } else {
            $section = Section::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'active' => $data['active'],
                'created_by' => $userId,
                'modified_by' => $userId,
            ]);
        }

        return $this->ok(['id' => $section->id]);
    }

    public function destroy(int $id)
    {
        // cannot delete if referenced in any period rows
        $used = DB::table('od_trx_assessment_period_rows')->where('section_id', $id)->exists();
        if ($used) {
            return $this->fail('Section has been used and cannot be deleted', 409);
        }
        Section::where('id', $id)->delete();
        return $this->ok(null, 'deleted');
    }
}
