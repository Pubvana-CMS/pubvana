<?php

declare(strict_types=1);

namespace Pubvana\Models;

/**
 * ThemeOption ActiveRecord model.
 *
 * Per-theme key/value options declared in pubvana.json.
 *
 * @property int         $id
 * @property int         $theme_id
 * @property string      $option_key
 * @property string|null $option_value
 *
 * @package Pubvana\Models
 */
class ThemeOption extends \flight\ActiveRecord
{
    public function __construct($pdo = null, array $config = [])
    {
        parent::__construct($pdo, 'theme_options', $config);
    }

    public int $id;
    public int $theme_id;
    public string $option_key = '';
    public ?string $option_value = null;

    /**
     * Get all options for a theme.
     *
     * @return self[]
     */
    public function getForTheme(int $themeId): array
    {
        $query = new self($this->getDatabaseConnection());
        return $query->eq('theme_id', $themeId)->findAll();
    }

    /**
     * Get a single option value.
     */
    public function getOption(int $themeId, string $key, ?string $default = null): ?string
    {
        $query = new self($this->getDatabaseConnection());
        $query->eq('theme_id', $themeId)
              ->eq('option_key', $key)
              ->find();

        return $query->isHydrated() ? $query->option_value : $default;
    }

    /**
     * Set an option value, inserting or updating as needed.
     */
    public function saveOption(int $themeId, string $key, string $value): void
    {
        $existing = new self($this->getDatabaseConnection());
        $existing->eq('theme_id', $themeId)
                 ->eq('option_key', $key)
                 ->find();

        if ($existing->isHydrated()) {
            $existing->option_value = $value;
            $existing->save();
        } else {
            $new = new self($this->getDatabaseConnection());
            $new->theme_id = $themeId;
            $new->option_key = $key;
            $new->option_value = $value;
            $new->insert();
        }
    }

    /**
     * Seed a default option value (only if key does not exist yet).
     */
    public function seedDefault(int $themeId, string $key, string $value): void
    {
        $existing = new self($this->getDatabaseConnection());
        $existing->eq('theme_id', $themeId)
                 ->eq('option_key', $key)
                 ->find();

        if (!$existing->isHydrated()) {
            $new = new self($this->getDatabaseConnection());
            $new->theme_id = $themeId;
            $new->option_key = $key;
            $new->option_value = $value;
            $new->insert();
        }
    }
}
