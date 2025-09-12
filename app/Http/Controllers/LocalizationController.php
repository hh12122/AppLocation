<?php

namespace App\Http\Controllers;

use App\Services\LocalizationService;
use App\Models\Language;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LocalizationController extends Controller
{
    protected LocalizationService $localizationService;

    public function __construct(LocalizationService $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    /**
     * Change the application locale
     */
    public function changeLocale(Request $request)
    {
        $request->validate([
            'locale' => 'required|string|exists:languages,code',
        ]);

        $this->localizationService->setLocale($request->locale);

        return back()->with('success', __('Language changed successfully'));
    }

    /**
     * Get available languages
     */
    public function getLanguages()
    {
        $languages = Language::active()->ordered()->get();

        return response()->json([
            'languages' => $languages,
            'current' => $this->localizationService->getCurrentLocale(),
        ]);
    }

    /**
     * Get translations for a specific group
     */
    public function getTranslations(Request $request)
    {
        $locale = $request->get('locale', $this->localizationService->getCurrentLocale());
        $group = $request->get('group', 'common');

        $translations = $this->localizationService->getTranslations($locale, $group);

        return response()->json([
            'locale' => $locale,
            'group' => $group,
            'translations' => $translations,
        ]);
    }

    /**
     * Update user language preferences
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'locale' => 'nullable|string|exists:languages,code',
            'timezone' => 'nullable|string|timezone',
            'date_format' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
        ]);

        $user = Auth::user();
        $user->update($request->only(['locale', 'timezone', 'date_format', 'currency']));

        if ($request->locale) {
            $this->localizationService->setLocale($request->locale);
        }

        return back()->with('success', __('Preferences updated successfully'));
    }

    /**
     * Admin: Manage translations
     */
    public function manageTranslations()
    {
        $languages = Language::active()->get();
        $groups = Translation::getGroups();
        $missingTranslations = Translation::getMissingTranslations();

        return Inertia::render('Admin/Translations', [
            'languages' => $languages,
            'groups' => $groups,
            'missingTranslations' => $missingTranslations,
        ]);
    }

    /**
     * Admin: Update translation
     */
    public function updateTranslation(Request $request)
    {
        $request->validate([
            'locale' => 'required|string|exists:languages,code',
            'group' => 'required|string',
            'key' => 'required|string',
            'value' => 'required|string',
        ]);

        $this->localizationService->saveTranslation(
            $request->locale,
            $request->group,
            $request->key,
            $request->value
        );

        return response()->json([
            'success' => true,
            'message' => 'Translation updated successfully',
        ]);
    }

    /**
     * Admin: Bulk import translations
     */
    public function importTranslations(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json,csv',
            'locale' => 'required|string|exists:languages,code',
        ]);

        $file = $request->file('file');
        $locale = $request->locale;
        $content = file_get_contents($file->getRealPath());

        if ($file->getClientOriginalExtension() === 'json') {
            $translations = json_decode($content, true);
        } else {
            // Handle CSV
            $lines = explode("\n", $content);
            $translations = [];
            foreach ($lines as $line) {
                $parts = str_getcsv($line);
                if (count($parts) >= 3) {
                    $translations[] = [
                        'group' => $parts[0],
                        'key' => $parts[1],
                        'value' => $parts[2],
                    ];
                }
            }
        }

        $count = 0;
        foreach ($translations as $translation) {
            if (isset($translation['group']) && isset($translation['key']) && isset($translation['value'])) {
                $this->localizationService->saveTranslation(
                    $locale,
                    $translation['group'],
                    $translation['key'],
                    $translation['value']
                );
                $count++;
            }
        }

        return back()->with('success', "Imported {$count} translations successfully");
    }

    /**
     * Admin: Export translations
     */
    public function exportTranslations(Request $request)
    {
        $request->validate([
            'locale' => 'required|string|exists:languages,code',
            'format' => 'required|in:json,csv',
        ]);

        $locale = $request->locale;
        $format = $request->format;

        $translations = Translation::where('locale', $locale)
            ->orderBy('group')
            ->orderBy('key')
            ->get();

        if ($format === 'json') {
            $data = [];
            foreach ($translations as $translation) {
                if (!isset($data[$translation->group])) {
                    $data[$translation->group] = [];
                }
                $data[$translation->group][$translation->key] = $translation->value;
            }

            return response()->json($data)
                ->header('Content-Disposition', "attachment; filename=translations_{$locale}.json");
        } else {
            $csv = "Group,Key,Value\n";
            foreach ($translations as $translation) {
                $csv .= '"' . $translation->group . '","' . $translation->key . '","' . str_replace('"', '""', $translation->value) . "\"\n";
            }

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=translations_{$locale}.csv");
        }
    }

    /**
     * Admin: Add new language
     */
    public function addLanguage(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:languages,code',
            'name' => 'required|string',
            'native_name' => 'required|string',
            'flag' => 'nullable|string',
            'is_rtl' => 'boolean',
            'copy_from' => 'nullable|string|exists:languages,code',
        ]);

        $language = Language::create($request->only([
            'code', 'name', 'native_name', 'flag', 'is_rtl'
        ]));

        // Copy translations from another language if specified
        if ($request->copy_from) {
            $count = Translation::copyTranslations($request->copy_from, $language->code);
            return back()->with('success', "Language added and {$count} translations copied");
        }

        return back()->with('success', 'Language added successfully');
    }

    /**
     * Admin: Toggle language status
     */
    public function toggleLanguage(Language $language)
    {
        $language->update(['is_active' => !$language->is_active]);

        return back()->with('success', 'Language status updated');
    }

    /**
     * Admin: Set default language
     */
    public function setDefaultLanguage(Language $language)
    {
        $language->makeDefault();

        return back()->with('success', 'Default language updated');
    }
}