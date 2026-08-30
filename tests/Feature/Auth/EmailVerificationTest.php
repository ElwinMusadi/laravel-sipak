<?php

test('email verification endpoints are not available', function () {
    $this->get('/email/verify')->assertNotFound();
    $this->post('/email/verification-notification')->assertNotFound();
});
