<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Setting;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Multi-line list fields must render as real inputs so saving never wipes them. */
class AdminFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_vision_fields_render_with_their_current_lines(): void
    {
        $this->seed(ContentSeeder::class);
        Setting::put('vision', ['vision' => ['First point', 'Second point'], 'vision_ar' => [], 'mission' => ['Do good'], 'mission_ar' => []]);

        $html = $this->actingAs($this->superAdmin())->get('/admin/settings')->assertOk()->getContent();
        $this->assertStringNotContainsString('<x-field', $html);
        $this->assertMatchesRegularExpression('/<textarea[^>]+name="vision"[^>]*>First point\R?Second point<\/textarea>/', $html);
        $this->assertMatchesRegularExpression('/<textarea[^>]+name="mission"[^>]*>Do good<\/textarea>/', $html);
    }

    public function test_course_highlight_fields_render(): void
    {
        $this->seed(ContentSeeder::class);
        $course = Course::firstOrFail();
        $course->update(['highlights' => ['Alpha', 'Beta']]);

        $html = $this->actingAs($this->superAdmin())->get("/admin/courses/{$course->id}/edit")->assertOk()->getContent();
        $this->assertStringNotContainsString('<x-field', $html);
        $this->assertMatchesRegularExpression('/<textarea[^>]+name="highlights"[^>]*>Alpha\R?Beta<\/textarea>/', $html);
    }
}
