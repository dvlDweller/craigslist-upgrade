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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('title', 120);
            $table->text('description');
            $table->string('price_type', 20);
            $table->decimal('price', 10, 2)->nullable();
            $table->char('currency', 3)->default('PHP');
            $table->string('municipality', 100)->nullable();
            $table->string('barangay', 100)->nullable();
            $table->string('province', 100)->default('Camarines Sur');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('municipality');
            $table->index('price');
            $table->index('created_at');
            $table->index(['status', 'category_id', 'municipality']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
