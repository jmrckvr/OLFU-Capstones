<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The repository tables. Open phpMyAdmin (http://localhost/phpmyadmin),
 * pick the `ccs_repository` database, and you will see one table per block below.
 */
return new class extends Migration
{
    public function up(): void
    {
        // One capstone project. `status` moves: draft -> pending -> approved, or pending -> returned -> pending.
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // who submitted it
            $table->string('title');
            $table->string('proponents', 500);              // "Ana Reyes, Carlo Mendoza"
            $table->string('program', 10);                  // BSIT | BSCS
            $table->string('academic_year', 20);            // 2025–2026
            $table->string('category', 100);
            $table->string('adviser')->nullable();
            $table->string('technology')->nullable();       // "PHP, MySQL, Bootstrap"
            $table->string('description', 500)->nullable(); // one-line summary
            $table->text('abstract')->nullable();
            $table->string('source_url')->nullable();
            $table->string('status', 20)->default('draft')->index(); // draft | pending | returned | approved
            $table->boolean('is_restricted')->default(false); // files visible to coordinator/admin only
            $table->text('feedback')->nullable();            // latest reviewer feedback
            $table->string('cover_path')->nullable();
            $table->unsignedInteger('downloads')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'academic_year', 'category']);
        });

        // Uploaded documents (PDF, PPTX, ZIP...) for a project. Files live in storage/app/private.
        Schema::create('project_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        // Every review decision (approve / return) with the reviewer's feedback.
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision', 20); // approved | returned
            $table->text('feedback')->nullable();
            $table->timestamps();
        });

        // Personal notifications ("Your submission was approved").
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('message', 500);
            $table->string('link')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        // Announcements shown to everyone (posted by coordinator or admin).
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message', 500);
            $table->timestamps();
        });

        // Activity log for the admin: who did what, and when.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40); // e.g. project.approved, user.deactivated
            $table->string('description', 500);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('project_files');
        Schema::dropIfExists('projects');
    }
};
