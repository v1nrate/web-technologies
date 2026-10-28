<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Build;
use App\Models\Component;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    private function user(bool $admin = false): User
    {
        $user = User::create([
            'name' => 'Тестовый пользователь',
            'email' => uniqid('test-', true) . '@example.test',
            'password' => 'password123',
        ]);
        $user->is_admin = $admin;
        $user->save();
        return $user;
    }

    private function createTestComponent(string $category, array $specs = [], int $price = 10000): Component
    {
        return Component::create([
            'category' => $category,
            'brand' => 'Test',
            'name' => 'Test ' . $category,
            'price' => $price,
            'power_watts' => 65,
            'specs' => $specs,
        ]);
    }

    private function build(User $owner): Build
    {
        return Build::create([
            'title' => 'Тестовая сборка',
            'items' => [],
            'total_price' => 0,
            'warnings' => [],
            'user_id' => $owner->id,
        ]);
    }

    public function test_registration_creates_non_admin_user_with_hashed_password(): void
    {
        $this->post(route('register.submit'), [
            'name' => 'Новый пользователь',
            'email' => 'new@example.test',
            'password' => 'securePassword123',
            'password_confirmation' => 'securePassword123',
        ])->assertRedirect(route('account'));

        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertFalse($user->is_admin);
        $this->assertTrue(Hash::check('securePassword123', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_invalid_password(): void
    {
        $user = $this->user();
        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'wrongPassword',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_cannot_access_account_or_admin(): void
    {
        $this->get(route('account'))->assertRedirect(route('login'));
        $this->get(route('admin.index'))->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_admin_or_export(): void
    {
        $this->actingAs($this->user());
        $this->get(route('admin.index'))->assertForbidden();
        $this->get(route('admin.components.index'))->assertForbidden();
        $this->get(route('admin.components.export'))->assertForbidden();
    }

    public function test_admin_can_open_dashboard(): void
    {
        $this->actingAs($this->user(true))
            ->get(route('admin.index'))
            ->assertOk();
    }

    public function test_user_can_save_build_and_total_is_calculated_on_server(): void
    {
        $user = $this->user();
        $cpu = $this->createTestComponent('cpu', ['socket' => 'AM5'], 20000);
        $board = $this->createTestComponent('motherboard', ['socket' => 'AM5'], 15000);

        $response = $this->actingAs($user)->post(route('builder.calculate'), [
            'title' => 'Игровой ПК',
            'parts' => ['cpu' => $cpu->id, 'motherboard' => $board->id],
            'total_price' => 1,
        ]);

        $build = Build::firstOrFail();
        $response->assertRedirect(route('builds.show', $build));
        $this->assertSame(35000, (int) $build->total_price);
        $this->assertSame($user->id, $build->user_id);
    }

    public function test_wrong_component_category_is_rejected(): void
    {
        $cpu = $this->createTestComponent('cpu');
        $this->actingAs($this->user())->post(route('builder.calculate'), [
            'title' => 'Ошибка',
            'parts' => ['gpu' => $cpu->id],
        ])->assertSessionHasErrors('parts');
        $this->assertDatabaseCount('builds', 0);
    }

    public function test_another_user_cannot_edit_update_duplicate_or_delete_build(): void
    {
        $build = $this->build($this->user());
        $this->actingAs($this->user());

        $this->get(route('builds.edit', $build))->assertForbidden();
        $this->put(route('builds.update', $build), ['title' => 'Чужая', 'parts' => []])->assertForbidden();
        $this->post(route('builds.duplicate', $build))->assertForbidden();
        $this->delete(route('builds.destroy', $build))->assertForbidden();
        $this->assertDatabaseHas('builds', ['id' => $build->id]);
    }

    public function test_owner_can_duplicate_and_delete_build(): void
    {
        $owner = $this->user();
        $build = $this->build($owner);
        $this->actingAs($owner)->post(route('builds.duplicate', $build))->assertRedirect();
        $this->assertDatabaseCount('builds', 2);
        $this->actingAs($owner)->delete(route('builds.destroy', $build))->assertRedirect(route('account'));
        $this->assertDatabaseMissing('builds', ['id' => $build->id]);
    }

    public function test_preview_detects_incompatible_cpu_socket_and_memory(): void
    {
        $cpu = $this->createTestComponent('cpu', ['socket' => 'AM4']);
        $board = $this->createTestComponent('motherboard', ['socket' => 'AM5', 'memory_type' => 'DDR5']);
        $ram = $this->createTestComponent('ram', ['memory_type' => 'DDR4']);

        $response = $this->postJson(route('builder.preview'), [
            'parts' => ['cpu' => $cpu->id, 'motherboard' => $board->id, 'ram' => $ram->id],
        ])->assertOk()->assertJsonPath('total', 30000);

        $titles = collect($response->json('check.issues'))->pluck('title');
        $this->assertContains('Несовместимые сокеты', $titles);
        $this->assertContains('Несовместимая оперативная память', $titles);
    }

    public function test_draft_article_is_hidden_from_public_but_visible_to_admin(): void
    {
        $article = Article::create([
            'title' => 'Черновик',
            'slug' => 'draft-test',
            'excerpt' => 'Тест',
            'body' => 'Содержание',
            'author' => 'Редакция',
            'category' => 'guides',
            'status' => 'draft',
        ]);

        $this->get(route('articles.show', $article->slug))->assertNotFound();
        $this->actingAs($this->user(true))
            ->get(route('articles.show', $article->slug))
            ->assertOk();
    }
}
