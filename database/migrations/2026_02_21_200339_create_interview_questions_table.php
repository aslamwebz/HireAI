<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_prep_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->text('question');
            $table->text('user_answer')->nullable();
            $table->text('ai_feedback')->nullable();
            $table->integer('score')->nullable();
            $table->text('model_answer')->nullable();
            $table->timestamps();

            $table->index(['interview_prep_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_questions');
    }
};
