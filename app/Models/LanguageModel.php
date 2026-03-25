<?php

namespace App\Models;

use CodeIgniter\Model;

class LanguageModel extends Model
{
    protected $table      = 'languages';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'code', 'name', 'native_name', 'direction',
        'is_default', 'is_active', 'sort_order',
    ];

    /**
     * Return all active languages ordered by sort_order.
     */
    public function getActive(): array
    {
        return $this->where('is_active', 1)
                    ->orderBy('sort_order', 'ASC')
                    ->findAll();
    }

    /**
     * Return the default language, or null if none set.
     */
    public function getDefault(): ?object
    {
        return $this->where('is_default', 1)->first();
    }

    /**
     * Toggle is_active for a language.
     * Prevents deactivating the default language.
     *
     * @return bool  false if the language is the default (cannot deactivate)
     */
    public function toggleActive(int $id): bool
    {
        $lang = $this->find($id);

        if ($lang === null) {
            return false;
        }

        // Do not allow deactivating the default language
        if ($lang->is_active && $lang->is_default) {
            return false;
        }

        $this->update($id, ['is_active' => $lang->is_active ? 0 : 1]);

        return true;
    }

    /**
     * Set a language as the default.
     * Clears any existing default, then sets the target as default + active.
     */
    public function makeDefault(int $id): bool
    {
        $lang = $this->find($id);

        if ($lang === null) {
            return false;
        }

        // Clear all defaults
        $this->db->table($this->table)->update(['is_default' => 0]);

        // Set the new default (also ensures it is active)
        $this->update($id, ['is_default' => 1, 'is_active' => 1]);

        return true;
    }

    /**
     * Return a flat array of active language codes for Config\App::$supportedLocales.
     */
    public function getSupportedLocales(): array
    {
        $langs = $this->getActive();
        $locales = [];

        foreach ($langs as $lang) {
            $locales[] = $lang->code;
        }

        return $locales;
    }
}
