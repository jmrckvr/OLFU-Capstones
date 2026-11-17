<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The main path: student submits -> coordinator returns -> student resubmits -> coordinator approves. */
class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $extra = []): array
    {
        return [
            'intent' => 'submit', 'title' => 'Library Book Reservation System', 'proponents' => 'Juan Dela Cruz, Ana Reyes',
            'program' => 'BSIT', 'academic_year' => '2026–2027', 'category' => 'Web Application',
            'adviser' => 'Prof. D. Navarro', 'technology' => 'PHP, MySQL', 'abstract' => 'This study presents...',
            'documents' => [UploadedFile::fake()->create('documentation.pdf', 200, 'application/pdf')],
            ...$extra,
        ];
    }

    public function test_upload_works_and_files_are_stored(): void
    {
        Storage::fake('local');
        $student = User::factory()->create();

        $this->actingAs($student)->post('/submissions', $this->form())->assertRedirect('/submissions');

        $project = Project::firstOrFail();
        $this->assertSame('pending', $project->status);
        $this->assertCount(1, $project->files);
        Storage::disk('local')->assertExists($project->files[0]->path);
    }

    public function test_draft_needs_only_the_basics(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student)->post('/submissions', $this->form(['intent' => 'draft', 'abstract' => '', 'adviser' => '', 'documents' => []]))
            ->assertSessionHasNoErrors();
        $this->assertSame('draft', Project::first()->status);
    }

    public function test_submit_without_a_document_is_rejected(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student)->post('/submissions', $this->form(['documents' => []]))->assertSessionHasErrors('documents');
        $this->assertSame(0, Project::count());
    }

    public function test_wrong_file_type_shows_an_error(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student)->post('/submissions', $this->form(['documents' => [UploadedFile::fake()->create('virus.exe', 5)]]))
            ->assertSessionHasErrors('documents.0');
    }

    public function test_full_review_cycle_with_notifications(): void
    {
        Storage::fake('local');
        $student = User::factory()->create();
        $coordinator = User::factory()->role('coordinator')->create();

        $this->actingAs($student)->post('/submissions', $this->form());
        $project = Project::firstOrFail();
        $this->assertSame(1, $coordinator->alerts()->count());

        $this->actingAs($coordinator)->post("/reviews/{$project->id}", ['decision' => 'returned', 'feedback' => 'Add the ERD.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('returned', $project->fresh()->status);
        $this->assertSame('Add the ERD.', $project->fresh()->feedback);

        $this->actingAs($student)->post("/submissions/{$project->id}/submit")->assertRedirect('/submissions');
        $this->assertSame('pending', $project->fresh()->status);

        $this->actingAs($coordinator)->post("/reviews/{$project->id}", ['decision' => 'approved']);
        $project->refresh();
        $this->assertSame('approved', $project->status);
        $this->assertNotNull($project->approved_at);
        $this->assertSame(2, $project->reviews()->count());
        $this->assertSame(2, $student->alerts()->count());

        // Approved projects can no longer be edited by the student.
        $this->actingAs($student)->get("/submissions/{$project->id}/edit")->assertForbidden();
    }

    public function test_every_page_opens_for_its_role(): void
    {
        $pages = [
            'student' => ['/dashboard', '/repository', '/submissions', '/submissions/create', '/notifications', '/account'],
            'coordinator' => ['/dashboard', '/repository', '/reviews', '/notifications', '/account'],
            'administrator' => ['/dashboard', '/repository', '/reviews', '/admin/projects', '/admin/projects/create', '/admin/users', '/admin/activity', '/notifications', '/account'],
        ];
        foreach ($pages as $role => $urls) {
            $user = User::factory()->role($role)->create();
            foreach ($urls as $url) {
                $this->actingAs($user)->get($url)->assertOk();
            }
        }
        auth()->logout();
        $this->get('/')->assertOk();
    }

    public function test_csv_export_downloads(): void
    {
        $admin = User::factory()->role('administrator')->create();
        $response = $this->actingAs($admin)->get('/admin/projects/export');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
