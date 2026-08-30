<?php

test('email verification notification is not available', function () {
    $this->post('/email/verification-notification')->assertNotFound();
});
