<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('label')->nullable();
            // Full JSON snapshot: book metadata + chapters + pages
            $table->json('snapshot');
            $table->timestamp('created_at')->nullable();

            $table->unique(['book_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_versions');
    }
};
