<?php

it('serves the OpenAPI document with the business endpoints', function () {
    $response = $this->getJson('/docs/api.json')->assertOk();

    expect(array_keys($response->json('paths')))->toContain('/clients', '/contracts', '/claims', '/users')
        ->and($response->json('paths./clients.post.requestBody'))->not->toBeNull();
});

it('serves the documentation page', function () {
    $this->get('/docs/api')->assertOk();
});
