<?php

namespace App\Models\Concerns;

/**
 * Bilingual content columns follow the `field` / `field_ar` convention.
 * tr('field') returns the Arabic value when the active locale is Arabic
 * (falling back to English when no translation exists).
 */
trait Translatable
{
    public function tr(string $field): mixed
    {
        if (app()->getLocale() === 'ar') {
            $ar = $this->getAttribute($field.'_ar');
            if ($ar !== null && $ar !== '' && $ar !== []) {
                return $ar;
            }
        }

        return $this->getAttribute($field);
    }
}
