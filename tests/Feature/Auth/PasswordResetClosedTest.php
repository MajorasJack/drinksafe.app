<?php

test('the forgot password page does not exist', function () {
    $this->getJson('/forgot-password')->assertNotFound();
});
