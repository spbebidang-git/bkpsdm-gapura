<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use App\Models\Support\Help;
use App\Services\Support\HelpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HelpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Admin');
        Role::findOrCreate('Participant');
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        return $admin;
    }

    public function test_guest_is_redirected_to_sign_in(): void
    {
        $this->get('/admin/help')->assertRedirect(route('sign-in.index'));
        $this->get('/admin/help/create')->assertRedirect(route('sign-in.index'));
    }

    public function test_participant_is_redirected_away_from_admin_help(): void
    {
        $participant = User::factory()->create();
        $participant->assignRole('Participant');

        $this->actingAs($participant)->get('/admin/help')->assertRedirect(route('home'));
    }

    public function test_admin_can_view_help_index_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/help')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Help/Index'));
    }

    public function test_admin_can_view_help_create_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/help/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Help/Form')->has('help', null));
    }

    public function test_admin_can_view_help_edit_page(): void
    {
        $help = Help::create([
            'question' => 'Apa itu Gapura?',
            'answer' => 'Gapura adalah portal.',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->get("/admin/help/{$help->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Help/Form')
                ->where('help.question', 'Apa itu Gapura?')
                ->where('help.answer', 'Gapura adalah portal.')
                ->where('help.is_active', true));
    }

    public function test_admin_can_create_help_entry(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/help', [
            'question' => 'Bagaimana cara mendaftar kelas?',
            'answer' => '<p>Klik tombol <strong>Daftar</strong>.</p>',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.help.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('helps', [
            'question' => 'Bagaimana cara mendaftar kelas?',
            'answer' => '<p>Klik tombol <strong>Daftar</strong>.</p>',
            'is_active' => true,
        ]);
    }

    public function test_create_defaults_is_active_to_true_when_omitted(): void
    {
        $this->actingAs($this->admin())->post('/admin/help', [
            'question' => 'Pertanyaan tanpa status',
            'answer' => 'Jawaban.',
        ])->assertRedirect(route('admin.help.index'));

        $this->assertDatabaseHas('helps', [
            'question' => 'Pertanyaan tanpa status',
            'is_active' => true,
        ]);
    }

    public function test_create_requires_question_and_answer(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/help/create')
            ->post('/admin/help', [])
            ->assertRedirect('/admin/help/create')
            ->assertSessionHasErrors(['question', 'answer']);

        $this->assertSame(0, Help::count());
    }

    public function test_create_validates_question_max_length(): void
    {
        $this->actingAs($this->admin())->post('/admin/help', [
            'question' => str_repeat('a', 256),
            'answer' => 'Jawaban.',
        ])->assertSessionHasErrors('question');
    }

    public function test_admin_can_update_help_entry(): void
    {
        $help = Help::create([
            'question' => 'Pertanyaan lama',
            'answer' => 'Jawaban lama',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/help/{$help->id}", [
                'question' => 'Pertanyaan baru',
                'answer' => '<p>Jawaban baru</p>',
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.help.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('helps', [
            'id' => $help->id,
            'question' => 'Pertanyaan baru',
            'answer' => '<p>Jawaban baru</p>',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_help_entry(): void
    {
        $help = Help::create([
            'question' => 'Dihapus',
            'answer' => 'Jawaban',
        ]);

        $this->actingAs($this->admin())
            ->delete("/admin/help/{$help->id}")
            ->assertRedirect(route('admin.help.index'));

        $this->assertSoftDeleted('helps', ['id' => $help->id]);
    }

    public function test_admin_datatable_lists_help_entries(): void
    {
        Help::create(['question' => 'Pertanyaan', 'answer' => 'Jawaban', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->get('/admin/help', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonFragment(['is_active_label' => 'Active']);
    }

    public function test_service_portal_only_returns_active_entries(): void
    {
        Help::create(['question' => 'Aktif', 'answer' => 'Jawaban aktif', 'is_active' => true]);
        Help::create(['question' => 'Nonaktif', 'answer' => 'Jawaban nonaktif', 'is_active' => false]);

        $portal = app(HelpService::class)->getForPortal();

        $this->assertCount(1, $portal);
        $this->assertSame('Aktif', $portal[0]['question']);
        $this->assertArrayNotHasKey('is_active', $portal[0]);
    }
}
