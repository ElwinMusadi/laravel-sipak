<?php

test('Inertia SSR remains disabled until its bundle and server are deployed', function () {
    expect(config('inertia.ssr.enabled'))->toBeFalse();
});
