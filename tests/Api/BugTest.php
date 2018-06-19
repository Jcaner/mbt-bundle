<?php

namespace Tienvx\Bundle\MbtBundle\Tests\Api;

class BugTest extends ApiTestCase
{
    public function testGetBugs()
    {
        $response = $this->makeApiRequest('GET', '/mbt/bugs');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertJsonStringEqualsJsonString('
        [
          {
            "id": 1,
            "reproducePath": "/mbt/reproduce_paths/1",
            "title": "Bug 1",
            "status": "unverified",
            "reporter": "email"
          },
          {
            "id": 2,
            "reproducePath": "/mbt/reproduce_paths/2",
            "title": "Bug 2",
            "status": "valid",
            "reporter": "email"
          }
        ]', $response->getContent());
    }

    public function testCreateBug()
    {
        $response = $this->makeApiRequest('POST', '/mbt/bugs', '
        {
            "reproducePath": "/mbt/reproduce_paths/3",
            "title": "Bug 3",
            "status": "unverified",
            "reporter": "email"
        }');

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('application/json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertJsonStringEqualsJsonString('
        {
            "id": 3,
            "reproducePath": "/mbt/reproduce_paths/3",
            "title": "Bug 3",
            "status": "unverified",
            "reporter": "email"
        }', $response->getContent());
    }

    public function testCreateInvalidBug()
    {
        $response = $this->makeApiRequest('POST', '/mbt/bugs', '
        {
            "reproducePath": "/mbt/reproduce_paths/3",
            "title": "Bug 4",
            "status": "invalid-bug",
            "reporter": "invalid-reporter"
        }');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/problem+json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertArraySubset(json_decode('
        {
          "title": "An error occurred",
          "detail": "status: The value you selected is not a valid choice.\nreporter: \"invalid-reporter\" is not a valid reporter.",
          "violations": [
              {
                  "propertyPath": "status",
                  "message": "The value you selected is not a valid choice."
              },
              {
                  "propertyPath": "reporter",
                  "message": "\"invalid-reporter\" is not a valid reporter."
              }
          ]
        }', true), json_decode($response->getContent(), true));
    }
}
