<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\NavItem;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NavItemController extends Controller
{
    public function adminList()
    {
        return response()->json(NavItem::with('parent')->orderBy('order')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'url' => 'required|string',
            'parent_id' => 'nullable|exists:nav_items,id',
            'section_heading' => 'nullable|string|max:255',
            'order' => 'integer',
            'has_intro_card' => 'boolean',
            'intro_card_image' => 'nullable|string',
            'intro_card_button_label' => 'nullable|string',
            'intro_card_url' => 'nullable|string',
            'is_external' => 'boolean',
        ]);

        $navItem = NavItem::create($validated);

        // Auto-create placeholder page if URL is an internal slug
        if (str_starts_with($navItem->url, '/') && !in_array($navItem->url, ['/fr/', '/', '#0', '#'])) {
            $slug = ltrim($navItem->url, '/');
            if (!Page::where('slug', $slug)->exists()) {
                Page::create([
                    'title' => $navItem->label,
                    'slug' => $slug,
                    'content' => "
                        <div style='padding: 40px; border: 2px dashed #cbd5e1; border-radius: 16px; text-align: center; background: #f8fafc;'>
                            <h2 style='color: #0f172a; margin-bottom: 16px;'>🚧 Modifications Needed</h2>
                            <p style='color: #64748b; font-size: 18px; line-height: 1.6;'>
                                This is a placeholder page for <strong>\"{$navItem->label}\"</strong>.<br>
                                Please log into the Admin Dashboard to replace this content with the actual information.
                            </p>
                        </div>
                    ",
                    'status' => 'draft',
                ]);
            }
        }

        return response()->json($navItem, 201);
    }

    public function update(Request $request, $id)
    {
        $navItem = NavItem::findOrFail($id);
        
        $validated = $request->validate([
            'label' => 'sometimes|required|string|max:255',
            'url' => 'sometimes|required|string',
            'parent_id' => 'nullable|exists:nav_items,id',
            'section_heading' => 'nullable|string|max:255',
            'order' => 'integer',
            'has_intro_card' => 'boolean',
            'intro_card_image' => 'nullable|string',
            'intro_card_button_label' => 'nullable|string',
            'intro_card_url' => 'nullable|string',
            'is_external' => 'boolean',
        ]);

        $navItem->update($validated);
        return response()->json($navItem);
    }

    public function destroy($id)
    {
        $navItem = NavItem::findOrFail($id);
        $navItem->delete();
        return response()->json(['message' => 'Navigation item deleted successfully']);
    }

    public function index()
    {
        $navItems = NavItem::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->with('children');
            }])
            ->orderBy('order')
            ->get();

        $formatted = $navItems->map(function ($item) {
            $sections = collect();
            
            if ($item->children->count() > 0) {
                $grouped = $item->children->groupBy('section_heading');
                
                foreach ($grouped as $heading => $children) {
                    $sections->push([
                        'heading' => $heading ?: null,
                        'items' => $children->map(function ($child) {
                            return [
                                'id' => $child->id,
                                'label' => $child->label,
                                'url' => $child->url,
                                'is_external' => $child->is_external,
                                'children' => $child->children->map(function ($grandchild) {
                                    return [
                                        'id' => $grandchild->id,
                                        'label' => $grandchild->label,
                                        'url' => $grandchild->url,
                                        'is_external' => $grandchild->is_external,
                                    ];
                                })->values()
                            ];
                        })->values()
                    ]);
                }
            }

            $data = [
                'id' => $item->id,
                'label' => $item->label,
                'url' => $item->url,
                'has_intro_card' => $item->has_intro_card,
            ];

            if ($item->has_intro_card) {
                $data['intro_card_image'] = $item->intro_card_image;
                $data['intro_card_button_label'] = $item->intro_card_button_label;
                $data['intro_card_url'] = $item->intro_card_url;
            }

            if ($sections->isNotEmpty()) {
                $data['sections'] = $sections;
            }

            return $data;
        });

        return response()->json($formatted);
    }
}
