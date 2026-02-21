<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_application_id')->nullable()->constrained()->nullOnDelete();
            $table->text('cv_content');
            $table->text('job_description');
            $table->integer('match_score')->nullable();
            $table->integer('ats_score')->nullable();
            $table->json('missing_keywords')->nullable();
            $table->json('improvements')->nullable();
            $table->json('rewritten_bullets')->nullable();
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->text('full_analysis')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_analyses');
    }
};
