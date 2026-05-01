<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavItem extends Model
{
    protected $fillable = [
        'label',
        'url',
        'parent_id',
        'section_heading',
        'order',
        'has_intro_card',
        'intro_card_image',
        'intro_card_button_label',
        'intro_card_url',
        'is_external',
    ];

    protected $casts = [
        'has_intro_card' => 'boolean',
        'is_external' => 'boolean',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(NavItem::class, 'parent_id')->orderBy('order');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavItem::class, 'parent_id');
    }
}
