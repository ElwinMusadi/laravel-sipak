<?php

test('public registration is not available', function () {
    $this->get('/register')->assertNotFound();

    $this->post('/register', [
        'name' => 'Public User',
        'username' => 'public-user',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});
