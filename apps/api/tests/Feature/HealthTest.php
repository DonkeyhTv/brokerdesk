<?php

it('reports a healthy API connected to PostgreSQL', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertExactJson(['status' => 'ok', 'database' => 'pgsql']);
});

it('answers unknown API routes with JSON', function () {
    $this->getJson('/api/v1/unknown')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});
