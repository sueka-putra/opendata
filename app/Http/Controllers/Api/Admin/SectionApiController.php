<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SectionApiController extends Controller
{
    use JsonEnvelope;

    public function index()
    {
        return $this->ok(Section::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $id = (int) $request->input('id');

        $data = $request->validate([
            'id' => 'nullable|integer',
            'prefix' => [
                'required',
                'string',
                'max:2',
                Rule::unique('od_mst_sections', 'prefix')->ignore($id),
            ],
            'title' => 'required|string|max:50',
            'description' => 'required|string|max:300',
            'active' => 'required|boolean',
        ]);

        $userId = (int) $request->user()->id;

        $section = null;
        if (!empty($data['id'])) {
            $section = Section::findOrFail($data['id']);
            $section->update([
                'prefix' => $data['prefix'],
                'title' => $data['title'],
                'description' => $data['description'],
                'active' => $data['active'],
                'modified_by' => $userId,
            ]);
        } else {
            $section = Section::create([
                'prefix' => $data['prefix'],
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
        $usedInConfigRows = DB::table('od_mst_configuration_rows')->where('section_id', $id)->exists();
        $usedInLegacyPeriodRows = Schema::hasTable('od_trx_assessment_period_rows')
            ? DB::table('od_trx_assessment_period_rows')->where('section_id', $id)->exists()
            : false;
        $used = $usedInConfigRows || $usedInLegacyPeriodRows;

        if ($used) {
            return $this->fail('Section has been used and cannot be deleted', 409);
        }
        Section::where('id', $id)->delete();
        return $this->ok(null, 'deleted');
    }
}
