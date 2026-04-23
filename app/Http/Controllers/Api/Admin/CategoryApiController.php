<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\JsonEnvelope;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CategoryApiController extends Controller
{
    use JsonEnvelope;

    public function index(Request $request)
    {
        // Spec: list screen supports filtering by section (even though no FK exists).
        // We'll just return all; UI can filter client-side.
        return $this->ok(Category::orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $id = (int) $request->input('id');

        $data = $request->validate([
            'id' => 'nullable|integer',
            'prefix' => [
                'required',
                'string',
                'max:3',
                Rule::unique('od_mst_categories', 'prefix')->ignore($id),
            ],
            'title' => 'required|string|max:50',
            'description' => 'required|string|max:300',
            'active' => 'required|boolean',
        ]);
        $userId = (int) $request->user()->id;

        if (!empty($data['id'])) {
            $cat = Category::findOrFail($data['id']);
            $cat->update([
                'prefix' => $data['prefix'],
                'title' => $data['title'],
                'description' => $data['description'],
                'active' => $data['active'],
                'modified_by' => $userId,
            ]);
            return $this->ok(['id' => $cat->id]);
        }

        $cat = Category::create([
            'prefix' => $data['prefix'],
            'title' => $data['title'],
            'description' => $data['description'],
            'active' => $data['active'],
            'created_by' => $userId,
            'modified_by' => $userId,
        ]);

        return $this->ok(['id' => $cat->id]);
    }

    public function destroy(int $id)
    {
        $usedInConfigRows = DB::table('od_mst_configuration_rows')->where('category_id', $id)->exists();
        $usedInLegacyPeriodRows = Schema::hasTable('od_trx_assessment_period_rows')
            ? DB::table('od_trx_assessment_period_rows')->where('category_id', $id)->exists()
            : false;
        $used = $usedInConfigRows || $usedInLegacyPeriodRows;

        if ($used) {
            return $this->fail('Category has been used and cannot be deleted', 409);
        }
        Category::where('id', $id)->delete();
        return $this->ok(null, 'deleted');
    }
}
