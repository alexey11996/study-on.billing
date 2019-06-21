<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use App\DataFixtures\BillingUserFixtures;
use App\Tests\AbstractTest;

class CourseTest extends AbstractTest
{
    public function getFixtures(): array
    {
        return [BillingUserFixtures::class];
    }

    public function testGetCourses()
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/courses');
        $courses = json_decode($client->getResponse()->getContent());
        $this->assertEquals(4, count($courses));
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

    public function testCoursePay()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['username' => 'simpleUser@gmail.com', 'password' => 'passwordForSimpleUser']));
        $response = json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/v1/courses/mern-stack-front-to-back-full-stack-react-redux-node-js/pay', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$response['token']]);
        $this->assertContains('{"success":true,"course_type":"rent"', $client->getResponse()->getContent());
    }

    public function testNotFoundCoursePay()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['username' => 'simpleUser@gmail.com', 'password' => 'passwordForSimpleUser']));
        $response = json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/v1/courses/stack-front-to-back-full-stack-react-redux-node-js/pay', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$response['token']]);
        $this->assertContains('"code":404,"message":"No course found"', $client->getResponse()->getContent());
    }

    public function testInvalidTokenPay()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/courses/mern-stack-front-to-back-full-stack-react-redux-node-js/pay', [], [], ['HTTP_AUTHORIZATION' => 'Bearer t5464g65f']);
        $this->assertContains('"code":401,"message":"Invalid JWT Token"', $client->getResponse()->getContent());
    }

    public function testNotEnoughCash()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['username' => 'simpleUser@gmail.com', 'password' => 'passwordForSimpleUser']));
        $response = json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/v1/courses/symfony-course-from-absolute-zero/pay', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$response['token']]);
        $this->assertContains('{"code":400,"message":"Not enough cash in your account"}', $client->getResponse()->getContent());
    }
}
