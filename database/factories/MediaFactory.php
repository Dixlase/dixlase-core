<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isJapanese = $this->faker->boolean();
        
        // 日本語・英語のファイル名候補
        $japaneseNames = [
            '桜の写真', '富士山の風景', '東京タワー', '神社の鳥居', '紅葉の景色',
            '海の夕日', '山の朝霧', '花火大会', '祭りの様子', '街の夜景',
            '猫の写真', '犬の散歩', '公園の風景', '川の流れ', '森の小道',
            '料理の写真', 'お弁当', 'ラーメン', '寿司', 'お菓子',
            '建物の外観', '橋の写真', '駅の風景', '商店街', '学校'
        ];
        
        $englishNames = [
            'sunset-beach', 'mountain-view', 'city-skyline', 'forest-path', 'lake-reflection',
            'flower-garden', 'autumn-leaves', 'winter-snow', 'spring-blossoms', 'summer-festival',
            'cat-portrait', 'dog-playing', 'bird-flying', 'butterfly-flower', 'fish-swimming',
            'food-delicious', 'coffee-cup', 'pizza-slice', 'burger-fries', 'ice-cream',
            'building-modern', 'bridge-sunset', 'train-station', 'shopping-street', 'library-books'
        ];
        
        $fileName = $isJapanese 
            ? $this->faker->randomElement($japaneseNames)
            : $this->faker->randomElement($englishNames);
        
        // 画像の種類とサイズ
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $imageType = $this->faker->randomElement($imageTypes);
        
        // ファイルパスの生成
        $year = $this->faker->dateTimeBetween('-2 years', 'now')->format('Y');
        $month = str_pad($this->faker->numberBetween(1, 12), 2, '0', STR_PAD_LEFT);
        $randomString = $this->faker->lexify('??????');
        
        $path = "uploads/media/{$year}/{$month}/{$fileName}_{$randomString}.{$imageType}";
        
        return [
            'name' => $fileName . '.' . $imageType,
            'path' => $path,
            'type' => 'image/' . ($imageType === 'jpg' ? 'jpeg' : $imageType),
            'uploaded_by' => Member::factory(),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
        ];
    }

    /**
     * 日本語ファイル名のメディアを生成
     */
    public function japanese(): static
    {
        return $this->state(function (array $attributes) {
            $japaneseNames = [
                '桜の写真', '富士山の風景', '東京タワー', '神社の鳥居', '紅葉の景色',
                '海の夕日', '山の朝霧', '花火大会', '祭りの様子', '街の夜景',
                '猫の写真', '犬の散歩', '公園の風景', '川の流れ', '森の小道',
                '料理の写真', 'お弁当', 'ラーメン', '寿司', 'お菓子'
            ];
            
            $fileName = $this->faker->randomElement($japaneseNames);
            $imageType = $this->faker->randomElement(['jpg', 'jpeg', 'png', 'gif', 'webp']);
            
            $year = $this->faker->dateTimeBetween('-2 years', 'now')->format('Y');
            $month = str_pad($this->faker->numberBetween(1, 12), 2, '0', STR_PAD_LEFT);
            $randomString = $this->faker->lexify('??????');
            
            return [
                'name' => $fileName . '.' . $imageType,
                'path' => "uploads/media/{$year}/{$month}/{$fileName}_{$randomString}.{$imageType}",
                'type' => 'image/' . ($imageType === 'jpg' ? 'jpeg' : $imageType),
            ];
        });
    }

    /**
     * 英語ファイル名のメディアを生成
     */
    public function english(): static
    {
        return $this->state(function (array $attributes) {
            $englishNames = [
                'sunset-beach', 'mountain-view', 'city-skyline', 'forest-path', 'lake-reflection',
                'flower-garden', 'autumn-leaves', 'winter-snow', 'spring-blossoms', 'summer-festival',
                'cat-portrait', 'dog-playing', 'bird-flying', 'butterfly-flower', 'fish-swimming',
                'food-delicious', 'coffee-cup', 'pizza-slice', 'burger-fries', 'ice-cream'
            ];
            
            $fileName = $this->faker->randomElement($englishNames);
            $imageType = $this->faker->randomElement(['jpg', 'jpeg', 'png', 'gif', 'webp']);
            
            $year = $this->faker->dateTimeBetween('-2 years', 'now')->format('Y');
            $month = str_pad($this->faker->numberBetween(1, 12), 2, '0', STR_PAD_LEFT);
            $randomString = $this->faker->lexify('??????');
            
            return [
                'name' => $fileName . '.' . $imageType,
                'path' => "uploads/media/{$year}/{$month}/{$fileName}_{$randomString}.{$imageType}",
                'type' => 'image/' . ($imageType === 'jpg' ? 'jpeg' : $imageType),
            ];
        });
    }

    /**
     * 古いメディアファイルを生成（テスト用）
     */
    public function old(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'created_at' => $this->faker->dateTimeBetween('-3 years', '-1 year'),
                'updated_at' => $this->faker->dateTimeBetween('-3 years', '-1 year'),
            ];
        });
    }

    /**
     * 最近のメディアファイルを生成（テスト用）
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            ];
        });
    }

    /**
     * 特定のメンバーがアップロードしたメディアを生成
     */
    public function uploadedBy(Member $member): static
    {
        return $this->state(function (array $attributes) use ($member) {
            return [
                'uploaded_by' => $member->id,
            ];
        });
    }

    /**
     * 削除済みメディアを生成（ソフトデリート）
     */
    public function deleted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'deleted_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            ];
        });
    }
}
