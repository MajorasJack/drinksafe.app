<?php

test('the password reset routes do not exist', function (string $method, string $uri) {
    $this->json($method, $uri)->assertNotFound();
})->with([
    'forgot password page' => ['GET', '/forgot-password'],
    'forgot password request' => ['POST', '/forgot-password'],
    'reset password page' => ['GET', '/reset-password/token'],
    'reset password request' => ['POST', '/reset-password'],
]);

test('the email verification page does not exist', function () {
    $this->getJson('/email/verify')->assertNotFound();
});
