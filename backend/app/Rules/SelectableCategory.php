<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SelectableCategory implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $category = Category::find($value);

        if (! $category || ! $category->is_active) {
            $fail('The selected category is not selectable.');

            return;
        }

        // Root (active) is selectable.
        if ($category->parent_id === null) {
            return;
        }

        // Direct child of an active root is selectable.
        $parent = $category->parent;

        if ($parent && $parent->is_active && $parent->parent_id === null) {
            return;
        }

        $fail('The selected category is not selectable.');
    }
}