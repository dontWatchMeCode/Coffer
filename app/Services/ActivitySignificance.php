<?php

declare(strict_types=1);

namespace App\Services;

class ActivitySignificance
{
    /**
     * @param  array<string, mixed>|null  $attributeChanges
     * @param  array<string, mixed>|null  $properties
     */
    public static function isSignificant(?array $attributeChanges, ?array $properties): bool
    {
        $changes = self::filterAttributeChanges($attributeChanges ?? []);
        $properties ??= [];
        $attributes = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $changedFields = array_values(array_filter(array_keys($attributes), is_string(...)));

        return $changedFields !== []
            || is_array($properties['relation_changes'] ?? null)
            || is_array($properties['block_changes'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    public static function filterAttributeChanges(array $changes): array
    {
        $changes = self::filterDrawingViewportChanges($changes);

        return self::filterEmptyFieldChanges($changes);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private static function filterDrawingViewportChanges(array $changes): array
    {
        if (! isset($changes['attributes']['drawing_data'])) {
            return $changes;
        }

        $old = $changes['old']['drawing_data'] ?? null;
        $new = $changes['attributes']['drawing_data'] ?? null;

        if (! is_array($old) || ! is_array($new)) {
            return $changes;
        }

        if (self::drawingDataEqualsIgnoringViewport($old, $new)) {
            unset($changes['attributes']['drawing_data']);
            unset($changes['old']['drawing_data']);
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private static function filterEmptyFieldChanges(array $changes): array
    {
        if (! is_array($changes['attributes'] ?? null)) {
            return $changes;
        }

        foreach (array_keys($changes['attributes']) as $field) {
            if (! is_string($field)) {
                continue;
            }

            $old = $changes['old'][$field] ?? null;
            $new = $changes['attributes'][$field] ?? null;

            if (! self::isEmptyActivityFieldValue($field, $old)) {
                continue;
            }

            if (! self::isEmptyActivityFieldValue($field, $new)) {
                continue;
            }

            unset($changes['attributes'][$field]);

            if (isset($changes['old'][$field])) {
                unset($changes['old'][$field]);
            }
        }

        return $changes;
    }

    private static function isEmptyActivityFieldValue(string $field, mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            $value = in_array($field, ['body', 'description'], true)
                ? strip_tags($value)
                : $value;

            return str($value)->squish()->isEmpty();
        }

        if (! is_array($value)) {
            return false;
        }

        if ($field !== 'drawing_data') {
            return $value === [];
        }

        $elements = $value['elements'] ?? [];
        $files = $value['files'] ?? [];

        return is_array($elements) && $elements === []
            && is_array($files) && $files === [];
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private static function drawingDataEqualsIgnoringViewport(array $old, array $new): bool
    {
        $withoutViewport = function (array $data): array {
            $data['appState'] ??= [];

            unset(
                $data['appState']['scrollX'],
                $data['appState']['scrollY'],
                $data['appState']['zoom'],
            );

            return $data;
        };

        return $withoutViewport($old) === $withoutViewport($new);
    }
}
