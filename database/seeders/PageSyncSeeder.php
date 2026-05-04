<?php

namespace Database\Seeders;

use App\Models\NavItem;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PageSyncSeeder extends Seeder
{
    public function run(): void
    {
        $navItems = NavItem::all();

        foreach ($navItems as $item) {
            // Check if URL is a slug (starts with / and isn't a special link or external)
            if (str_starts_with($item->url, '/') && !in_array($item->url, ['/fr/', '/', '#0', '#'])) {
                
                // Extract slug (remove leading slash)
                $slug = ltrim($item->url, '/');

                // Check if page already exists
                $exists = Page::where('slug', $slug)->exists();

                if (!$exists) {
                    Page::create([
                        'title' => $item->label,
                        'slug' => $slug,
                        'content' => "
                            <div style='padding: 40px; border: 2px dashed #cbd5e1; border-radius: 16px; text-align: center; background: #f8fafc;'>
                                <h2 style='color: #0f172a; margin-bottom: 16px;'>🚧 Modifications Needed</h2>
                                <p style='color: #64748b; font-size: 18px; line-height: 1.6;'>
                                    This is a placeholder page for <strong>\"{$item->label}\"</strong>.<br>
                                    Please log into the Admin Dashboard to replace this content with the actual information.
                                </p>
                                <div style='margin-top: 24px; display: inline-block; padding: 10px 20px; background: #0f172a; color: #fff; border-radius: 8px; font-weight: 600;'>
                                    Dashboard > Pages > Edit
                                </div>
                            </div>
                        ",
                        'status' => 'draft', // Created as draft as requested
                    ]);
                    
                    $this->command->info("Created placeholder page for: {$item->label}");
                }
            }
        }
    }
}
