
declare(strict_types=1);

namespace Tests\Unit\View;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The show/hide toggle of <x-form-text> positions its eye icon absolutely
 * inside a wrapper. The input itself is capped by the size classes
 * (input-sm/md/lg/xl → max-width), so the wrapper must carry the same
 * cap — otherwise the icon lands at the right edge of the whole row.
 */
class FormTextPasswordToggleTest extends TestCase
{
    public function test_toggle_wrapper_takes_the_input_size_class(): void
    {
        $html = Blade::render('<x-form-text name="secret" type="password" :showPasswordToggle="true" class="input-common input-xl" />');

        $this->assertMatchesRegularExpression('/<div[^>]*class="relative input-xl"/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*\binput-xl\b/', $html);
        $this->assertStringContainsString('fa-eye', $html);
    }

    public function test_toggle_wrapper_stays_full_width_without_a_size_class(): void
    {
        $html = Blade::render('<x-form-text name="db_password" type="password" :showPasswordToggle="true" class="input-full" />');

        $this->assertMatchesRegularExpression('/<div[^>]*class="relative"/', $html);
    }

    public function test_no_wrapper_without_the_toggle(): void
    {
        $html = Blade::render('<x-form-text name="plain" class="input-common input-xl" />');

        $this->assertStringNotContainsString('showPassword', $html);
        $this->assertStringNotContainsString('fa-eye', $html);
    }
}
