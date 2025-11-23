<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeMigration extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dls_theme_migrations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'theme',
        'migration',
        'batch',
    ];

    /**
     * Get the next batch number.
     *
     * @return int
     */
    public static function getNextBatchNumber(): int
    {
        return (int) static::max('batch') + 1;
    }

    /**
     * Get migrations for a specific theme.
     *
     * @param string $themeName
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getThemeMigrations(string $themeName)
    {
        return static::where('theme_name', $themeName)
            ->orderBy('batch')
            ->orderBy('migration')
            ->get();
    }

    /**
     * Check if a migration has been run for a theme.
     *
     * @param string $themeName
     * @param string $migration
     * @return bool
     */
    public static function hasRun(string $themeName, string $migration): bool
    {
        return static::where('theme_name', $themeName)
            ->where('migration', $migration)
            ->exists();
    }

    /**
     * Delete all migrations for a specific theme.
     *
     * @param string $themeName
     * @return int
     */
    public static function deleteThemeMigrations(string $themeName): int
    {
        return static::where('theme_name', $themeName)->delete();
    }
}
