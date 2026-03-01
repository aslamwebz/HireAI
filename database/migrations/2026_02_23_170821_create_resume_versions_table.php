<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->onDelete('cascade');
            $table->integer('version_number');
            $table->json('content');
            $table->string('change_summary')->nullable();
            $table->string('optimization_type')->nullable();
            $table->text('optimization_notes')->nullable();
            $table->timestamps();

            $table->unique(['resume_id', 'version_number']);
            $table->index(['resume_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_versions');
    }
};
