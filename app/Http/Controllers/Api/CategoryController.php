<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Workspace;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        return response()->json($workspace->categories()->withCount('products')->orderBy('name')->get());
    }

    public function store(Request $request, AuditService $audit): JsonResponse
    {
        /** @var Workspace $workspace */
        $workspace = $request->attributes->get('workspace');
        $validated = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $slug = Str::slug($validated['name']) ?: 'category';

        $request->validate([
            'name' => [Rule::unique('categories', 'name')->where(fn ($q) => $q->where('workspace_id', $workspace->id))],
        ]);

        $category = $workspace->categories()->create(['name' => $validated['name'], 'slug' => $slug]);
        $audit->log($request, $workspace, 'category.created', $category);
        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($category->workspace_id === $workspace->id, 404);
        $validated = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $category->update(['name' => $validated['name'], 'slug' => Str::slug($validated['name']) ?: $category->slug]);
        $audit->log($request, $workspace, 'category.updated', $category);
        return response()->json($category->fresh());
    }

    public function destroy(Request $request, Category $category, AuditService $audit): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        abort_unless($category->workspace_id === $workspace->id, 404);
        $audit->log($request, $workspace, 'category.deleted', $category, ['name' => $category->name]);
        $category->delete();
        return response()->json(['message' => 'Category deleted.']);
    }
}
