<?php

test('email password reset endpoints are not available', function () {
    $this->get('/forgot-password')->assertNotFound();
    $this->post('/forgot-password', ['email' => 'user@example.com'])->assertNotFound();
});
