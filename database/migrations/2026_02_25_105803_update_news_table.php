<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('news', function (Blueprint $table) {
        $table->string('excerpt')->nullable();
        $table->string('author')->default('CDTA');
        $table->timestamp('published_at')->nullable();
        $table->string('category')->nullable();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
