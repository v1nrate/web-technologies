<?php

namespace Tests\Feature;

use App\Models\Build;
use App\Models\Component;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ValidationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $admin = false): User
    {
        $user = User::create([
            'name' => 'Проверка безопасности',
            'email' => uniqid('security-', true).'@example.test',
            'password' => 'password123',
        ]);
        $user->is_admin = $admin;
        $user->save();
        return $user;
    }

    private function makeComponent(string $category = 'cpu'): Component
    {
        return Component::create([
            'category' => $category,
            'brand' => 'Test',
            'name' => 'Test '.$category,
            'price' => 10000,
            'power_watts' => 65,
            'specs' => [],
        ]);
    }

    public function test_build_rejects_unknown_component_id(): void
    {
        $this->actingAs($this->makeUser())->post(route('builder.calculate'), [
            'title' => 'Test',
            'parts' => ['cpu' => 999999],
        ])->assertSessionHasErrors('parts.cpu');
        $this->assertDatabaseCount('builds', 0);
    }

    public function test_build_rejects_unknown_category(): void
    {
        $cpu = $this->makeComponent();
        $this->actingAs($this->makeUser())->post(route('builder.calculate'), [
            'title' => 'Test',
            'parts' => ['unknown' => $cpu->id],
        ])->assertSessionHasErrors('parts');
        $this->assertDatabaseCount('builds', 0);
    }

    public function test_build_rejects_more_than_eight_categories(): void
    {
        $parts = array_fill_keys(['cpu', 'motherboard', 'gpu', 'ram', 'ssd', 'psu', 'case', 'cooler', 'extra'], null);
        $this->actingAs($this->makeUser())->post(route('builder.calculate'), [
            'title' => 'Test',
            'parts' => $parts,
        ])->assertSessionHasErrors('parts');
        $this->assertDatabaseCount('builds', 0);
    }

    public function test_preview_rejects_mismatched_category(): void
    {
        $cpu = $this->makeComponent();
        $this->postJson(route('builder.preview'), [
            'parts' => ['gpu' => $cpu->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('parts');
    }

    public function test_preview_rejects_invalid_component_id(): void
    {
        $this->postJson(route('builder.preview'), [
            'parts' => ['cpu' => 999999],
        ])->assertUnprocessable()->assertJsonValidationErrors('parts.cpu');
    }

    public function test_other_user_cannot_replace_build_contents(): void
    {
        $owner = $this->makeUser();
        $attacker = $this->makeUser();
        $build = Build::create([
            'title' => 'Original',
            'items' => [],
            'total_price' => 0,
            'warnings' => [],
            'user_id' => $owner->id,
        ]);
        $this->actingAs($attacker)->put(route('builds.update', $build), [
            'title' => 'Changed',
            'parts' => [],
        ])->assertForbidden();
        $this->assertDatabaseHas('builds', [
            'id' => $build->id,
            'title' => 'Original',
            'user_id' => $owner->id,
        ]);
    }

    public function test_non_admin_cannot_import_csv(): void
    {
        $csv = "category;brand;name;price;power_watts;specs\ncpu;Test;Injected;100;65;{}\n";
        $file = UploadedFile::fake()->createWithContent('components.csv', $csv);
        $this->actingAs($this->makeUser())->post(route('admin.components.import'), [
            'file' => $file,
        ])->assertForbidden();
        $this->assertDatabaseCount('components', 0);
    }

    public function test_admin_rejects_negative_component_price(): void
    {
        $this->actingAs($this->makeUser(true))->post(route('admin.components.store'), [
            'category' => 'cpu',
            'brand' => 'Test',
            'name' => 'Negative price',
            'price' => -1,
            'power_watts' => 65,
        ])->assertSessionHasErrors('price');
        $this->assertDatabaseCount('components', 0);
    }

    public function test_admin_rejects_oversized_component_price(): void
    {
        $this->actingAs($this->makeUser(true))->post(route('admin.components.store'), [
            'category' => 'cpu',
            'brand' => 'Test',
            'name' => 'Huge price',
            'price' => 1000000000,
            'power_watts' => 65,
        ])->assertSessionHasErrors('price');
        $this->assertDatabaseCount('components', 0);
    }

    public function test_admin_rejects_invalid_csv_extension(): void
    {
        $file = UploadedFile::fake()->createWithContent('components.php', '<?php echo 1;');
        $this->actingAs($this->makeUser(true))->post(route('admin.components.import'), [
            'file' => $file,
        ])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('components', 0);
    }

    public function test_admin_rejects_invalid_csv_header(): void
    {
        $file = UploadedFile::fake()->createWithContent('components.csv', "wrong;columns\nvalue;value\n");
        $this->actingAs($this->makeUser(true))->post(route('admin.components.import'), [
            'file' => $file,
        ])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('components', 0);
    }

    public function test_non_admin_cannot_change_user_role(): void
    {
        $target = $this->makeUser();
        $this->actingAs($this->makeUser())->patch(route('admin.users.role', $target), [
            'is_admin' => 1,
        ])->assertForbidden();
        $this->assertFalse((bool) $target->fresh()->is_admin);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = $this->makeUser(true);
        $this->actingAs($admin)->patch(route('admin.users.role', $admin), [
            'is_admin' => 0,
        ])->assertSessionHasErrors('role');
        $this->assertTrue((bool) $admin->fresh()->is_admin);
    }
}
