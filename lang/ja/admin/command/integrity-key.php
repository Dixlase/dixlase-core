<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

return [
    'env_missing' => ':path に .env がありません。',
    'current_invalid' => '.env の INTEGRITY_KEY が <版>:base64:<鍵> の形ではありません。直すか消してから、もう一度実行してください。',
    'already_configured' => '整合性の鍵はすでに設定されています（版 :version）。',
    'rotate_hint' => '入れ替えるには `php artisan dls:integrity:key --rotate` を実行します。古い鍵は残すので、それで署名したものは引き続き検証できます。',
    'nothing_to_rotate' => '整合性の鍵がまだ設定されていません。`php artisan dls:integrity:key` で作成してください。',
    'versions_exhausted' => '使える鍵の版が残っていません。',
    'show_intro' => '次の行を .env に加えてください（何も書き込んでいません）:',
    'created' => '整合性の鍵（版 :version）を .env に書き込みました。新しい監査ログの行と日次封印はこの鍵で署名されます。',
    'rotated' => '整合性の鍵（版 :version）を .env に書き込みました。以前の鍵は INTEGRITY_PREVIOUS_KEYS に移しました。それで署名した行と封印を残すあいだは、消さずに置いてください。',
    'baseline_sealed' => '既存のファイル整合性のベースラインを、内容を変えずに新しい鍵で封印しました。',
    'config_cached' => '設定がキャッシュされています。新しい鍵を効かせるには `php artisan config:cache` を実行してください。',
    'restart_hint' => '常駐するプロセス（キューのワーカー、スケジューラー）を再起動して、新しい鍵を読ませてください。',
];
