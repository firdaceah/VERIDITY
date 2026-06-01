<?php

test('guest is redirected to login from protected reseller landing page', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
