<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nav_items', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('url')->default('#');
            $table->foreignId('parent_id')->nullable()->constrained('nav_items')->onDelete('cascade');
            $table->string('section_heading')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('has_intro_card')->default(false);
            $table->string('intro_card_image')->nullable();
            $table->string('intro_card_button_label')->nullable();
            $table->string('intro_card_url')->nullable();
            $table->boolean('is_external')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nav_items');
    }
};
