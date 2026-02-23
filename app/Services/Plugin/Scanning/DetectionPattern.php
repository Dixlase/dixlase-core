<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

namespace App\Services\Plugin\Scanning;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 検出パターンの抽象基底クラス
 *
 * 各パターンクラスは特定の権限カテゴリに対応し、
 * コンテキストを考慮した検出ロジックを実装します。
 */
abstract class DetectionPattern
{
    /**
     * パターンが対応する権限キー（ドット記法）
     */
    abstract public function permissionKey(): string;

    /**
     * パターンが対応する拡張機能種別（plugin, theme, both）
     */
    public function applicableTo(): string
    {
        return 'both';
    }

    /**
     * 存在確認対象のファイルパターン
     *
     * @return array<string>
     */
    public function filePatterns(): array
    {
        return [];
    }

    /**
     * 検出対象の正規表現パターン
     *
     * @return array<string>
     */
    public function regexPatterns(): array
    {
        return [];
    }

    /**
     * コンテキストを考慮してマッチ結果を検証する
     *
     * 誤検出を減らすため、正規表現マッチ後にコンテキスト検証を行います。
     * デフォルトではすべてのマッチを有効と判定します。
     *
     * @param  string  $match  マッチした文字列
     * @param  string  $line  マッチを含む行全体
     * @param  string  $fileContent  ファイル全体の内容
     * @param  string  $filePath  ファイルパス（拡張子ディレクトリからの相対パス）
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        // デフォルト: コメント行を除外
        $trimmedLine = ltrim($line);

        if (str_starts_with($trimmedLine, '//') || str_starts_with($trimmedLine, '*') || str_starts_with($trimmedLine, '/*')) {
            return false;
        }

        return true;
    }

    /**
     * ファイル内でパターンを検出し、検証済みの結果を返す
     *
     * @return array<array{type: string, file: string, line: int, match: string}>
     */
    public function scan(string $fileContent, string $filePath): array
    {
        $results = [];

        foreach ($this->regexPatterns() as $pattern) {
            if (preg_match_all($pattern, $fileContent, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as [$matchText, $offset]) {
                    $lineNumber = substr_count(substr($fileContent, 0, $offset), "\n") + 1;
                    $line = $this->getLineAtOffset($fileContent, $offset);

                    if ($this->validateMatch($matchText, $line, $fileContent, $filePath)) {
                        $results[] = [
                            'type' => 'pattern_match',
                            'file' => $filePath,
                            'line' => $lineNumber,
                            'match' => trim($matchText),
                        ];
                    }
                }
            }
        }

        return $results;
    }

    /**
     * 指定オフセット位置の行全体を取得
     */
    protected function getLineAtOffset(string $content, int $offset): string
    {
        $lineStart = strrpos($content, "\n", $offset - strlen($content));
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;

        $lineEnd = strpos($content, "\n", $offset);
        $lineEnd = $lineEnd === false ? strlen($content) : $lineEnd;

        return substr($content, $lineStart, $lineEnd - $lineStart);
    }
}
