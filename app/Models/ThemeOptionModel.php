<?php

namespace App\Models;

use CodeIgniter\Model;

class ThemeOptionModel extends Model
{
    protected $table      = 'theme_options';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = ['theme_id', 'option_key', 'option_value'];

    /**
     * Get all options for a theme as key-value pairs.
     */
    public function getForTheme(int $themeId): array
    {
        return $this->where('theme_id', $themeId)->findAll();
    }

    /**
     * Get a single option value for a theme.
     */
    public function getOption(int $themeId, string $key, ?string $default = null): ?string
    {
        $row = $this->where('theme_id', $themeId)
                    ->where('option_key', $key)
                    ->first();
        return $row ? $row->option_value : $default;
    }

    /**
     * Set an option value, inserting or updating as needed.
     */
    public function saveOption(int $themeId, string $key, string $value): void
    {
        $row = $this->where('theme_id', $themeId)
                    ->where('option_key', $key)
                    ->first();
        if ($row) {
            $this->where('theme_id', $themeId)
                 ->where('option_key', $key)
                 ->set('option_value', $value)
                 ->update();
        } else {
            $this->insert([
                'theme_id'     => $themeId,
                'option_key'   => $key,
                'option_value' => $value,
            ]);
        }
    }

    /**
     * Seed a default option value (only if key does not exist yet).
     */
    public function seedDefault(int $themeId, string $key, string $value): void
    {
        $exists = $this->where('theme_id', $themeId)
                       ->where('option_key', $key)
                       ->countAllResults();
        if ($exists === 0) {
            $this->insert([
                'theme_id'     => $themeId,
                'option_key'   => $key,
                'option_value' => $value,
            ]);
        }
    }
}
