<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use App\DataFixtures\CourseFixtures;
use App\Tests\AbstractTest;

class CourseTest extends AbstractTest
{
    public function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    public function testGetCourses()
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/courses');
        $courses = json_decode($client->getResponse()->getContent());
        $this->assertEquals(3, count($courses));
    }

    public function testGetCurrentCourse()
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/courses/'. 'mern-stack-front-to-back-full-stack-react-redux-node-js');
        $this->assertContains('{"code":"mern-stack-front-to-back-full-stack-react-redux-node-js","type":"rent","price":25.55}', $client->getResponse()->getContent());
    }

    public function testGetNotExistingCourse()
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/courses/'. 'stack-front-to-back-full-stack-react-redux-node-js');
        $this->assertContains('{"message":"No course found"}', $client->getResponse()->getContent());
    }
}
