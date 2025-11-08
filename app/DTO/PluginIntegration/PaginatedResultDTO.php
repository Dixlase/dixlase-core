<?php

namespace App\DTO\PluginIntegration;

use JsonSerializable;

/**
 * ページネーション結果DTO
 * 
 * プラグイン間で検索結果を受け渡しする際の
 * ページネーション情報を含む不変データオブジェクトです。
 * 
 * @template T of JsonSerializable
 * @package App\DTO\PluginIntegration
 */
final readonly class PaginatedResultDTO implements JsonSerializable
{
    /**
     * @param array<T> $items 検索結果アイテム
     * @param int $total 総件数
     * @param int $page 現在のページ番号
     * @param int $perPage 1ページあたりの件数
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    /**
     * 総ページ数を取得
     * 
     * @return int
     */
    public function totalPages(): int
    {
        if ($this->perPage <= 0) {
            return 0;
        }
        return (int)ceil($this->total / $this->perPage);
    }

    /**
     * 次のページが存在するか
     * 
     * @return bool
     */
    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages();
    }

    /**
     * 前のページが存在するか
     * 
     * @return bool
     */
    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    /**
     * 現在のページの開始位置（1始まり）
     * 
     * @return int
     */
    public function from(): int
    {
        if ($this->total === 0) {
            return 0;
        }
        return ($this->page - 1) * $this->perPage + 1;
    }

    /**
     * 現在のページの終了位置
     * 
     * @return int
     */
    public function to(): int
    {
        $to = $this->page * $this->perPage;
        return min($to, $this->total);
    }

    /**
     * 結果が空かどうか
     * 
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * 結果が存在するかどうか
     * 
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * JSON形式にシリアライズ
     * 
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'items' => array_map(
                fn($item) => $item instanceof JsonSerializable ? $item->jsonSerialize() : $item,
                $this->items
            ),
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => $this->totalPages(),
            'has_next_page' => $this->hasNextPage(),
            'has_previous_page' => $this->hasPreviousPage(),
            'from' => $this->from(),
            'to' => $this->to(),
        ];
    }

    /**
     * 配列形式に変換
     * 
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Laravelのページネータから生成
     * 
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     * @param callable|null $transformer アイテム変換関数
     * @return self
     */
    public static function fromPaginator(
        \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator,
        ?callable $transformer = null
    ): self {
        $items = $paginator->items();
        
        if ($transformer !== null) {
            $items = array_map($transformer, $items);
        }

        return new self(
            items: $items,
            total: $paginator->total(),
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
        );
    }

    /**
     * 配列から生成
     * 
     * @param array<string,mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            items: $data['items'] ?? [],
            total: (int)($data['total'] ?? 0),
            page: (int)($data['page'] ?? 1),
            perPage: (int)($data['per_page'] ?? 20),
        );
    }

    /**
     * アイテムを変換した新しいDTOを生成
     * 
     * @param callable $callback 変換関数
     * @return self
     */
    public function map(callable $callback): self
    {
        return new self(
            items: array_map($callback, $this->items),
            total: $this->total,
            page: $this->page,
            perPage: $this->perPage,
        );
    }

    /**
     * アイテムをフィルタした新しいDTOを生成
     * 
     * @param callable $callback フィルタ関数
     * @return self
     */
    public function filter(callable $callback): self
    {
        $filtered = array_filter($this->items, $callback);
        
        return new self(
            items: array_values($filtered),
            total: count($filtered),
            page: $this->page,
            perPage: $this->perPage,
        );
    }
}
