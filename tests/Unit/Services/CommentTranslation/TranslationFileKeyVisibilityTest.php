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
 * Every dictionary entry is visible, whatever its key looks like (#527).
 *
 * The dictionaries used to be read with a prefix rule: any key starting
 * with `_` was metadata. `_review_status` is the only key that is, and a
 * comment may legitimately start with an underscore -- `_optional と
 * _notes を保持` is one of several in core. Those entries disappeared
 * from the coverage figure, from the `--filter` listing and from the
 * substitution the build performs, so they were never reported as
 * pending and never replaced in the source.
 *
 * Numeric keys were dropped as well: PHP casts `'1' => ''` to the integer
 * key `1`, and the reader required a string.
 *
 * Every file lives in a throwaway tree under storage/framework/testing/.
 */
class TranslationFileKeyVisibilityTest extends TestCase
{
    private string $root;

    private string $dictionary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/dictionary-keys-'.uniqid());
        File::ensureDirectoryExists($this->root);

        $this->dictionary = $this->root.'/dictionary.php';
        File::put($this->dictionary, <<<'PHP'
<?php

return [
    '通常のコメント' => 'An ordinary comment',
    '_optional と _notes を保持' => '',
    '_insert_before の処理' => 'Handle _insert_before',
    '1' => '',
    'まだ訳していない' => '',

    '_review_status' => [
        'An ordinary comment' => 'machine',
    ],
];
PHP);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_only_the_reserved_key_counts_as_metadata(): void
    {
        $this->assertTrue(TranslationFileService::isMetaKey('_review_status'));
        $this->assertFalse(TranslationFileService::isMetaKey('_optional と _notes を保持'));
        $this->assertFalse(TranslationFileService::isMetaKey('_insert_before の処理'));
        $this->assertFalse(TranslationFileService::isMetaKey(1));
        $this->assertFalse(TranslationFileService::isMetaKey('通常のコメント'));
    }

    public function test_entries_whose_key_starts_with_an_underscore_are_loaded(): void
    {
        $entries = (new TranslationFileService())->loadTranslations($this->dictionary);

        $this->assertArrayHasKey('_optional と _notes を保持', $entries);
        $this->assertArrayHasKey('_insert_before の処理', $entries);
        $this->assertSame('', $entries['_optional と _notes を保持']);
        $this->assertSame('Handle _insert_before', $entries['_insert_before の処理']);
    }

    public function test_a_numeric_key_is_loaded_as_a_string(): void
    {
        $entries = (new TranslationFileService())->loadTranslations($this->dictionary);

        $this->assertArrayHasKey('1', $entries);
        // Read through ?? so static analysis does not reason about a key
        // it cannot see; the assertion above is what proves it is there.
        $this->assertSame('', $entries['1'] ?? 'missing');
    }

    public function test_the_reserved_key_is_not_loaded_as_an_entry(): void
    {
        $entries = (new TranslationFileService())->loadTranslations($this->dictionary);

        $this->assertArrayNotHasKey('_review_status', $entries);
        $this->assertCount(5, $entries);
    }

    public function test_the_reserved_key_is_the_only_thing_load_meta_returns(): void
    {
        $meta = (new TranslationFileService())->loadMeta($this->dictionary);

        $this->assertSame(['_review_status'], array_keys($meta));
    }

    public function test_pending_counts_the_entries_that_were_hidden(): void
    {
        // Three empty values: the underscore one, the numeric one and the
        // ordinary one. The prefix rule used to report one.
        $pending = (function (array $t): int {
            return $this->countPending($t);
        })->call(
            new CommentBuilderService(new TranslationFileService()),
            (new TranslationFileService())->loadTranslations($this->dictionary)
        );

        $this->assertSame(3, $pending);
    }

    public function test_merge_keeps_an_underscore_entry_as_content(): void
    {
        $service = new TranslationFileService();

        [$merged, $meta] = $service->merge(
            $service->load($this->dictionary),
            ['_optional と _notes を保持' => '']
        );

        // The entry stays on the content side; only the reserved key is meta.
        $this->assertArrayHasKey('_optional と _notes を保持', $merged);
        $this->assertArrayHasKey('_insert_before の処理', $merged);
        $this->assertArrayNotHasKey('_review_status', $merged);
        $this->assertSame(['_review_status'], array_keys($meta));
    }
}
