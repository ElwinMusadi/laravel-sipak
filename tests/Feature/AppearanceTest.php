<?php

test('light appearance is the default theme', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('<html class="dark"', false);
});

test('dark appearance is applied only after a user selects it', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('login'))
        ->assertOk()
        ->assertSee('class="dark"', false);
});
