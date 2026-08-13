<?php

use Illuminate\Support\Facades\Blade;

/**
 * The Blade components under resources/views/components/ui are hand-kept
 * mirrors of the shadcn-vue components in resources/js/components/ui.
 * These tests only guard the Blade half: that it renders, and that each
 * variant emits the class string it is supposed to. Visual equivalence with
 * the Vue components is checked separately at /_ui-parity in local dev.
 */
it('renders a button as a <button> by default', function () {
    $html = Blade::render('<x-ui.button>Trimite</x-ui.button>');

    expect($html)
        ->toContain('<button')
        ->toContain('data-slot="button"')
        ->toContain('bg-primary text-primary-foreground')
        ->toContain('Trimite');
});

it('renders a button as an <a> when given an href', function () {
    $html = Blade::render('<x-ui.button href="/stomatologi/iasi">Vezi</x-ui.button>');

    expect($html)
        ->toContain('<a')
        ->toContain('href="/stomatologi/iasi"')
        ->not->toContain('<button');
});

it('applies each button variant', function (string $variant, string $expected) {
    $html = Blade::render('<x-ui.button variant="'.$variant.'">X</x-ui.button>');

    expect($html)->toContain($expected);
})->with([
    ['destructive', 'bg-destructive'],
    ['outline', 'hover:bg-accent'],
    ['secondary', 'bg-secondary text-secondary-foreground'],
    ['ghost', 'hover:bg-accent hover:text-accent-foreground'],
    ['link', 'underline-offset-4'],
]);

it('applies each button size', function (string $size, string $expected) {
    $html = Blade::render('<x-ui.button size="'.$size.'">X</x-ui.button>');

    expect($html)->toContain($expected);
})->with([
    ['sm', 'h-8'],
    ['lg', 'h-10'],
    ['icon', 'size-9'],
]);

it('falls back to the default variant when given an unknown one', function () {
    $html = Blade::render('<x-ui.button variant="nonsense">X</x-ui.button>');

    expect($html)->toContain('bg-primary text-primary-foreground');
});

it('renders badges with their variants', function () {
    expect(Blade::render('<x-ui.badge>Nou</x-ui.badge>'))
        ->toContain('data-slot="badge"')
        ->toContain('bg-primary');

    expect(Blade::render('<x-ui.badge variant="outline">CNAS</x-ui.badge>'))
        ->toContain('text-foreground');
});

it('renders the card family', function () {
    $html = Blade::render(<<<'BLADE'
        <x-ui.card>
            <x-ui.card-header>
                <x-ui.card-title>NovaDent</x-ui.card-title>
                <x-ui.card-description>Iași</x-ui.card-description>
            </x-ui.card-header>
            <x-ui.card-content>Implant dentar</x-ui.card-content>
            <x-ui.card-footer>Vezi profilul</x-ui.card-footer>
        </x-ui.card>
    BLADE);

    expect($html)
        ->toContain('data-slot="card"')
        ->toContain('data-slot="card-header"')
        ->toContain('data-slot="card-title"')
        ->toContain('data-slot="card-description"')
        ->toContain('data-slot="card-content"')
        ->toContain('data-slot="card-footer"')
        ->toContain('NovaDent');
});

it('lets a card title change heading level for correct document outline', function () {
    // City pages own the <h1>; clinic cards inside them must be <h2>.
    $html = Blade::render('<x-ui.card-title as="h2">NovaDent</x-ui.card-title>');

    expect($html)->toContain('<h2')->toContain('</h2>');
});

it('renders input and separator', function () {
    expect(Blade::render('<x-ui.input placeholder="Caută" />'))
        ->toContain('data-slot="input"')
        ->toContain('placeholder="Caută"');

    expect(Blade::render('<x-ui.separator orientation="vertical" />'))
        ->toContain('data-orientation="vertical"');
});
