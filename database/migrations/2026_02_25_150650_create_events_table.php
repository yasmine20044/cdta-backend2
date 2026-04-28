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
    Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('slug');
        $table->text('description');
        $table->string('status')->default('draft'); // draft / published
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->string('location')->nullable();
        $table->string('category')->nullable();
        $table->string('image')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
