<?php

namespace Log1x\AcfEditorPalette\Concerns;

trait Palette
{
    /**
     * Retrieve the editor color palette.
     *
     * @param  string  $color
     * @param  string|array  $custom
     * @return string[]
     */
    public function palette($color = null, $custom = [])
    {
        $colors = [];

        $palette = ! empty($custom)
            ? $this->customPalette($custom)
            : $this->themePalette();

        if (empty($palette)) {
            return $color ?: $colors;
        }

        foreach ($palette as $value) {
            if (empty($value['slug'])) {
                continue;
            }

            $colors = array_merge($colors, [
                $value['slug'] => array_merge($value, [
                    'text' => sprintf('has-text-color has-%s-color', $value['slug']),
                    'background' => sprintf('has-background has-%s-background-color', $value['slug']),
                ]),
            ]);
        }

        return ! empty($color) && is_string($color) && is_array($colors) ? (
            array_key_exists($color, $colors) ? $colors[$color] : null
        ) : $colors;
    }

    /**
     * Retrieve the theme color palette.
     *
     * @return array
     */
    protected function themePalette()
    {
        $themeJson = [];

        $palette = (array) current(
            get_theme_support('editor-color-palette') ?: []
        );

        if (function_exists('wp_get_global_settings')) {
            $themeJson = wp_get_global_settings(['color', 'palette', 'theme']);
        }

        if (! isset($themeJson['border'])) {
            $palette = array_merge($palette, $themeJson);
        }

        return $palette;
    }

    /**
     * Retrieve the custom color palette.
     *
     * @param  string|array  $colors
     * @return array
     */
    public function customPalette($colors = [])
    {
        if (is_string($colors)) {
            $colors = $this->decodeColors($colors);
        }

        if (! is_array($colors)) {
            return [];
        }

        $palette = [];

        foreach ($colors as $value) {
            if (! is_array($value) || empty($value['name']) || empty($value['color'])) {
                continue;
            }

            $palette[] = array_merge($value, [
                'slug' => ! empty($value['slug']) ? $value['slug'] : sanitize_title($value['name']),
            ]);
        }

        return $palette;
    }

    /**
     * Decode custom colors from `color : Name` lines.
     *
     * @param  string  $colors
     * @return array
     */
    public function decodeColors($colors = '')
    {
        $palette = [];

        foreach (explode("\n", $colors) as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }

            [$color, $name] = array_map('trim', explode(':', $line, 2));

            $palette[] = compact('name', 'color');
        }

        return $palette;
    }

    /**
     * Encode custom colors as `color : Name` lines.
     *
     * @param  string|array  $colors
     * @return string
     */
    public function encodeColors($colors = [])
    {
        return implode("\n", array_map(function ($color) {
            return sprintf('%s : %s', $color['color'], $color['name']);
        }, $this->customPalette($colors)));
    }
}
