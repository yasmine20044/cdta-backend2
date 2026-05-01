<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\NavItem;
use Illuminate\Http\Request;

class NavItemController extends Controller
{
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
