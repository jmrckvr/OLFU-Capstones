<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** One test per security fix from PLAN.md. Run: php artisan test */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function project(User $owner, array $attrs = []): Project
    {
        $p = new Project([
            'title' => 'Test Project', 'proponents' => 'A, B', 'program' => 'BSIT',
            'academic_year' => '2025–2026', 'category' => 'Web Application',
        ]);
        $p->forceFill(['user_id' => $owner->id, 'status' => 'approved', ...$attrs])->save();

        return $p;
    }

    // --- Roles and URL guards -------------------------------------------------

    public function test_register_always_creates_a_student_even_if_role_is_sent(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky', 'email' => 'sneaky@olfu.edu.ph', 'role' => 'administrator', 'is_active' => true,
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->assertSame('student', User::where('email', 'sneaky@olfu.edu.ph')->value('role'));
    }

    public function test_user_cannot_change_own_role_from_my_account(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student)->put('/account', ['name' => 'New Name', 'role' => 'administrator', 'is_active' => false]);

        $student->refresh();
        $this->assertSame('New Name', $student->name);
        $this->assertSame('student', $student->role);
        $this->assertTrue($student->is_active);
    }

    public function test_student_is_blocked_from_staff_and_admin_urls(): void
    {
        $student = User::factory()->create();
        $other = $this->project(User::factory()->create(), ['status' => 'pending']);

        foreach (['/reviews', '/admin/projects', '/admin/users', '/admin/activity', '/admin/projects/export'] as $url) {
            $this->actingAs($student)->get($url)->assertForbidden();
        }
        $this->actingAs($student)->post("/reviews/{$other->id}", ['decision' => 'approved'])->assertForbidden();
        $this->actingAs($student)->patch("/admin/users/{$student->id}", ['role' => 'administrator'])->assertForbidden();
        $this->actingAs($student)->delete("/admin/projects/{$other->id}")->assertForbidden();
        $this->assertSame('student', $student->fresh()->role);
    }

    public function test_coordinator_is_blocked_from_admin_urls(): void
    {
        $coordinator = User::factory()->role('coordinator')->create();
        $this->actingAs($coordinator)->get('/admin/users')->assertForbidden();
        $this->actingAs($coordinator)->post('/admin/users', ['name' => 'X', 'email' => 'x@x.com', 'role' => 'administrator'])->assertForbidden();
        $this->actingAs($coordinator)->get('/reviews')->assertOk();
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/dashboard', '/repository', '/admin/users', '/reviews', '/submissions'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    // --- Admin lockout --------------------------------------------------------

    public function test_admin_cannot_deactivate_or_demote_themselves(): void
    {
        $admin = User::factory()->role('administrator')->create();

        $this->actingAs($admin)->patch("/admin/users/{$admin->id}", ['is_active' => false])->assertSessionHasErrors('is_active');
        $this->actingAs($admin)->patch("/admin/users/{$admin->id}", ['role' => 'student'])->assertSessionHasErrors('role');

        $admin->refresh();
        $this->assertTrue($admin->is_active);
        $this->assertSame('administrator', $admin->role);
    }

    public function test_admin_can_deactivate_others_and_they_are_signed_out(): void
    {
        $admin = User::factory()->role('administrator')->create();
        $student = User::factory()->create();

        $this->actingAs($admin)->patch("/admin/users/{$student->id}", ['is_active' => false])->assertSessionHasNoErrors();
        $this->assertFalse($student->fresh()->is_active);

        // Their next click logs them out.
        $this->actingAs($student->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_sign_in(): void
    {
        User::factory()->inactive()->create(['email' => 'off@olfu.edu.ph']);
        $this->post('/login', ['email' => 'off@olfu.edu.ph', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'This account has been deactivated.']);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_wrong_tries(): void
    {
        User::factory()->create(['email' => 'juan@olfu.edu.ph']);
        foreach (range(1, 5) as $_) {
            $this->post('/login', ['email' => 'juan@olfu.edu.ph', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'juan@olfu.edu.ph', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // --- Project visibility and files ----------------------------------------

    public function test_student_cannot_open_someone_elses_unapproved_project(): void
    {
        $owner = User::factory()->create();
        $draft = $this->project($owner, ['status' => 'pending']);

        $this->actingAs(User::factory()->create())->get("/projects/{$draft->id}")->assertForbidden();
        $this->actingAs($owner)->get("/projects/{$draft->id}")->assertOk();
    }

    public function test_restricted_project_files_are_staff_only(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $project = $this->project($owner, ['is_restricted' => true]);
        $file = $project->files()->create([
            'original_name' => 'doc.pdf', 'path' => UploadedFile::fake()->create('doc.pdf', 10)->store("projects/{$project->id}", 'local'),
            'mime_type' => 'application/pdf', 'size' => 10,
        ]);

        $this->actingAs(User::factory()->create())->get("/projects/{$project->id}/files/{$file->id}")->assertForbidden();
        $this->actingAs(User::factory()->create())->get("/projects/{$project->id}")
            ->assertInertia(fn (Assert $page) => $page->where('canSeeFiles', false)->missing('project.files'));
        $this->actingAs(User::factory()->role('coordinator')->create())->get("/projects/{$project->id}/files/{$file->id}")->assertOk();
    }

    public function test_file_must_belong_to_the_project_in_the_url(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $open = $this->project($owner);
        $secret = $this->project($owner, ['is_restricted' => true]);
        $file = $secret->files()->create(['original_name' => 's.pdf', 'path' => 'x/s.pdf', 'mime_type' => 'application/pdf', 'size' => 1]);

        $this->actingAs(User::factory()->create())->get("/projects/{$open->id}/files/{$file->id}")->assertNotFound();
    }

    public function test_student_cannot_edit_or_delete_someone_elses_submission(): void
    {
        $owner = User::factory()->create();
        $draft = $this->project($owner, ['status' => 'draft']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get("/submissions/{$draft->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->delete("/submissions/{$draft->id}")->assertForbidden();
        $this->assertNotNull($draft->fresh());
    }

    public function test_lists_do_not_leak_other_users_emails(): void
    {
        $owner = User::factory()->create(['email' => 'private@olfu.edu.ph']);
        $this->project($owner);

        $html = $this->actingAs(User::factory()->create())->get('/repository')->getContent();
        $this->assertStringNotContainsString('private@olfu.edu.ph', $html);
    }

    public function test_returning_a_project_requires_feedback(): void
    {
        $coordinator = User::factory()->role('coordinator')->create();
        $project = $this->project(User::factory()->create(), ['status' => 'pending']);

        $this->actingAs($coordinator)->post("/reviews/{$project->id}", ['decision' => 'returned', 'feedback' => ''])
            ->assertSessionHasErrors('feedback');
        $this->assertSame('pending', $project->fresh()->status);
    }
}
