<?php

test('two factor endpoints return 404 when the feature is disabled', function () {
    $this->get('/two-factor-challenge')->assertNotFound();
    $this->post('/user/two-factor-authentication')->assertNotFound();
});
