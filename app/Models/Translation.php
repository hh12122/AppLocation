<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $fillable = [
        'locale',
        'group',
        'key',
        'value',
    ];

    // Scopes
    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function scopeForGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('key', 'like', '%' . $search . '%')
              ->orWhere('value', 'like', '%' . $search . '%');
        });
    }

    // Helpers
    public function getFullKey(): string
    {
        return $this->group . '.' . $this->key;
    }

    public static function getGroups(): array
    {
        return static::select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->toArray();
    }

    public static function getMissingTranslations(string $baseLocale = 'fr'): array
    {
        $baseTranslations = static::where('locale', $baseLocale)
            ->select('group', 'key')
            ->get();

        $languages = Language::active()
            ->where('code', '!=', $baseLocale)
            ->pluck('code');

        $missing = [];

        foreach ($languages as $locale) {
            $existingKeys = static::where('locale', $locale)
                ->select('group', 'key')
                ->get()
                ->map(fn($t) => $t->group . '.' . $t->key)
                ->toArray();

            foreach ($baseTranslations as $translation) {
                $fullKey = $translation->group . '.' . $translation->key;
                if (!in_array($fullKey, $existingKeys)) {
                    $missing[$locale][] = $fullKey;
                }
            }
        }

        return $missing;
    }

    public static function copyTranslations(string $fromLocale, string $toLocale): int
    {
        $translations = static::where('locale', $fromLocale)->get();
        $count = 0;

        foreach ($translations as $translation) {
            static::firstOrCreate(
                [
                    'locale' => $toLocale,
                    'group' => $translation->group,
                    'key' => $translation->key,
                ],
                [
                    'value' => $translation->value . ' [TO TRANSLATE]',
                ]
            );
            $count++;
        }

        return $count;
    }
}