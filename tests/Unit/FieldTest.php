<?php

beforeEach(function () {
    themePalette([
        ['name' => 'Green', 'slug' => 'green', 'color' => '#0e9f6e'],
    ]);

    $this->field = array_merge(field()->defaults, [
        'id' => 'acf-field_color',
        'name' => 'acf[field_color]',
        'class' => '',
        'value' => null,
        'custom_colors' => [
            ['name' => 'Magenta', 'slug' => 'magenta', 'color' => '#ff00ff'],
            ['name' => 'Cyan', 'slug' => 'cyan', 'color' => '#00ffff'],
        ],
    ]);
});

it('formats a custom color value', function () {
    expect(field()->format_value('magenta', 1, $this->field))->toBe('magenta');

    expect(field()->format_value('magenta', 1, array_merge($this->field, ['return_format' => 'color'])))
        ->toBe('#ff00ff');
});

it('validates against the custom colors', function () {
    expect(field()->validate_value(true, 'magenta', $this->field, ''))->toBeTrue();

    expect(field()->validate_value(true, 'green', $this->field, ''))->toBeString();
});

it('validates against the theme palette without custom colors', function () {
    $field = array_merge($this->field, ['custom_colors' => []]);

    expect(field()->validate_value(true, 'green', $field, ''))->toBeTrue();
});

it('saves the custom color', function () {
    expect(field()->update_value('cyan', 1, $this->field))
        ->name->toBe('Cyan')
        ->color->toBe('#00ffff');
});

it('normalizes the custom colors repeater when saving the field', function () {
    $field = field()->update_field(['custom_colors' => [
        'row-0' => ['name' => 'Magenta', 'slug' => '', 'color' => '#ff00ff'],
        'row-1' => ['name' => 'Brand Blue', 'slug' => 'brand', 'color' => '#0066cc'],
        'acfcloneindex' => ['name' => '', 'slug' => '', 'color' => ''],
    ]]);

    expect($field['custom_colors'])->toBe([
        ['name' => 'Magenta', 'slug' => 'magenta', 'color' => '#ff00ff'],
        ['name' => 'Brand Blue', 'slug' => 'brand', 'color' => '#0066cc'],
    ]);
});

it('renders the custom colors', function () {
    ob_start();
    field()->render_field($this->field);
    $html = ob_get_clean();

    expect($html)
        ->toContain('value="magenta"', 'value="cyan"', 'background-color: #ff00ff')
        ->not->toContain('value="green"');
});

it('filters the custom colors', function () {
    ob_start();
    field()->render_field(array_merge($this->field, ['exclude_colors' => ['cyan']]));
    $html = ob_get_clean();

    expect($html)->toContain('value="magenta"')->not->toContain('value="cyan"');
});

it('renders the custom colors repeater setting', function () {
    field()->render_field_settings($this->field);

    expect($GLOBALS['field_settings']['custom_colors'])
        ->type->toBe('repeater')
        ->and(array_column($GLOBALS['field_settings']['custom_colors']['sub_fields'], 'key', '_name'))
        ->toBe(['name' => 'name', 'slug' => 'slug', 'color' => 'color']);

    expect($GLOBALS['field_settings'])
        ->default_value->choices->toBe(['magenta' => 'Magenta', 'cyan' => 'Cyan']);
});

it('renders the custom colors setting without a theme palette', function () {
    $GLOBALS['theme_support'] = [];

    field()->render_field_settings(array_merge($this->field, ['custom_colors' => []]));

    expect($GLOBALS['field_settings'])->toHaveKeys(['custom_colors', 'color_palette_empty']);
});
