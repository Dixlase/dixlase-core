<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\Services\CommentTranslation;

use App\Services\CommentTranslation\CommentBuilderService;
use App\Services\CommentTranslation\TranslationFileService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The comment build reads scaffold templates (`*.stub`) as well as PHP
 * sources. A stub's dictionary is its path plus `.php`; a stub with a
 * `<?php` tag is read as PHP, any other stub only has its Blade comment
 * blocks rewritten, and template placeholders survive the build.
 *
 * Every file lives in a throwaway tree under storage/framework/testing/.
 */
class CommentBuilderServiceStubTest extends TestCase
{
    private string $root;

    private string $extension;

    private string $dict;

    private string $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/comment-build-stubs-'.uniqid());
        $this->extension = $this->root.'/extension';
        $this->dict = $this->extension.'/resources/comment-translations/ja';
        $this->output = $this->root.'/dist';

        File::ensureDirectoryExists($this->extension.'/stubs');
        File::ensureDirectoryExists($this->dict.'/stubs');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_dictionary_path_of_a_stub_gains_php(): void
    {
        $this->assertSame('stubs/routes.web.stub.php', TranslationFileService::dictionaryPathFor('stubs/routes.web.stub'));
        $this->assertSame('app/Models/Foo.php', TranslationFileService::dictionaryPathFor('app/Models/Foo.php'));
        $this->assertSame('resources/views/a.blade.php', TranslationFileService::dictionaryPathFor('resources/views/a.blade.php'));
    }

    public function test_stubs_and_php_files_are_source_files(): void
    {
        $this->assertTrue(CommentBuilderService::isSourceFile('/x/stubs/model.stub'));
        $this->assertTrue(CommentBuilderService::isSourceFile('/x/app/Foo.php'));
        $this->assertFalse(CommentBuilderService::isSourceFile('/x/stubs/readme.md'));
        $this->assertFalse(CommentBuilderService::isSourceFile('/x/package.json'));
    }

    public function test_php_stub_comments_are_translated_and_placeholders_kept(): void
    {
        $source = <<<'STUB'
<?php

{{ license }}

namespace {{ namespace }};

use Illuminate\Support\Facades\Route;

// Define the front-end routes here
Route::get('/', fn () => 'Define the front-end routes here')->name('{{ name }}.index');
STUB;
        File::put($this->extension.'/stubs/routes.web.stub', $source);
        $this->writeDictionary('stubs/routes.web.stub.php', [
            'Define the front-end routes here' => 'フロントエンドのルートをここに定義します',
        ]);

        $stats = $this->builder()->applyExtension($this->extension, $this->dict, 'stubs', $this->output);

        $built = File::get($this->output.'/stubs/routes.web.stub');
        $this->assertSame(1, $stats['files']);
        $this->assertSame(1, $stats['translated']);
        $this->assertStringContainsString('// フロントエンドのルートをここに定義します', $built);
        // The string literal with the same text is not a comment.
        $this->assertStringContainsString("fn () => 'Define the front-end routes here'", $built);
        $this->assertStringContainsString('{{ license }}', $built);
        $this->assertStringContainsString('namespace {{ namespace }};', $built);
        $this->assertStringContainsString("->name('{{ name }}.index')", $built);
        // The source itself is untouched in copy mode.
        $this->assertSame($source, File::get($this->extension.'/stubs/routes.web.stub'));
    }

    public function test_blade_stub_only_rewrites_blade_comment_blocks(): void
    {
        $source = <<<'STUB'
{{ license }}

@extends('front::partials.layout')

@section('content')
    <p>Write the inline script here: {{ filename }}</p>
    {{-- Write the inline script here --}}
    {{-- Livewire Component: {{ class }} --}}
@endsection
STUB;
        File::put($this->extension.'/stubs/blade-front.stub', $source);
        $this->writeDictionary('stubs/blade-front.stub.php', [
            'Write the inline script here' => 'インラインスクリプトはここに記述',
            'Livewire Component:' => 'Livewire コンポーネント:',
        ]);

        $this->builder()->applyExtension($this->extension, $this->dict, 'stubs', $this->output);

        $built = File::get($this->output.'/stubs/blade-front.stub');
        $this->assertStringContainsString('{{-- インラインスクリプトはここに記述 --}}', $built);
        $this->assertStringContainsString('<p>Write the inline script here: {{ filename }}</p>', $built);
        $this->assertStringContainsString('{{-- Livewire コンポーネント: {{ class }} --}}', $built);
        $this->assertStringStartsWith('{{ license }}', $built);
    }

    public function test_non_php_stub_text_is_never_read_as_a_php_comment(): void
    {
        $source = "# {{ pluginName }}\n\nSee https://example.com/Title for details.\n";
        File::put($this->extension.'/stubs/README.plugin.stub', $source);
        $this->writeDictionary('stubs/README.plugin.stub.php', [
            '{{ pluginName }}' => 'should not appear',
            'example.com/Title' => 'should not appear',
        ]);

        $stats = $this->builder()->applyExtension($this->extension, $this->dict, 'stubs', $this->output);

        $this->assertSame(0, $stats['translated']);
        $this->assertSame($source, File::get($this->output.'/stubs/README.plugin.stub'));
    }

    public function test_stub_without_dictionary_is_copied_verbatim(): void
    {
        $source = "{\n    \"name\": \"{{ vendor }}/{{ package }}\"\n}\n";
        File::put($this->extension.'/stubs/composer.plugin.stub', $source);

        $stats = $this->builder()->applyExtension($this->extension, $this->dict, 'stubs', $this->output);

        $this->assertSame(1, $stats['files']);
        $this->assertSame($source, File::get($this->output.'/stubs/composer.plugin.stub'));
    }

    public function test_in_place_round_trip_restores_the_stub(): void
    {
        $source = "<?php\n\n/*\n| Web Routes\n*/\n\n// Define the front-end routes here\n";
        $path = $this->extension.'/stubs/routes.web.stub';
        File::put($path, $source);
        $this->writeDictionary('stubs/routes.web.stub.php', [
            'Web Routes' => 'Web ルート',
            'Define the front-end routes here' => 'フロントエンドのルートをここに定義します',
        ]);

        $builder = $this->builder();
        $builder->applyExtension($this->extension, $this->dict, 'stubs');
        $this->assertStringContainsString('| Web ルート', File::get($path));

        $builder->applyExtension($this->extension, $this->dict, 'stubs', reverse: true);
        $this->assertSame($source, File::get($path));
    }

    public function test_stub_dictionary_is_listed_for_status(): void
    {
        $this->writeDictionary('stubs/routes.web.stub.php', ['Web Routes' => 'Web ルート']);
        File::put($this->dict.'/_glossary.php', "<?php\n\nreturn [];\n");

        $files = (new TranslationFileService())->getAllTranslationFilesIn($this->dict);

        $this->assertSame([$this->dict.'/stubs/routes.web.stub.php'], $files);
    }

    private function builder(): CommentBuilderService
    {
        return new CommentBuilderService(new TranslationFileService());
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function writeDictionary(string $relativePath, array $entries): void
    {
        (new TranslationFileService())->save($this->dict.'/'.$relativePath, $entries);
    }
}
