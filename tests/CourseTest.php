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

    public function auth($client, $username, $password)
    {
        $client->request('POST', '/api/v1/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['username' => $username, 'password' => $password]));
        $response = json_decode($client->getResponse()->getContent(), true);
        return $response['token'];
    }

    public function testAddCourse()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/courses', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$this->auth($client, 'simpleUser@gmail.com', 'passwordForSimpleUser')], json_encode(['code' => 'new-course', 'title' => 'New Course', 'type' => 'rent', 'price' => '100.5']));
        $this->assertContains('Access denied', $client->getResponse()->getContent());
        $client->request('POST', '/api/v1/courses', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$this->auth($client, 'alex@mail.ru', '123456')], json_encode(['code' => 'new-course', 'title' => 'New Course', 'type' => 'rent', 'price' => '100.5']));
        $this->assertContains('{"success":true}', $client->getResponse()->getContent());
        $client->request('GET', '/api/v1/courses');
        $courses = json_decode($client->getResponse()->getContent());
        $this->assertEquals(5, count($courses));
    }

    public function testEditCourse()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/courses/mern-stack-front-to-back-full-stack-react-redux-node-js', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$this->auth($client, 'simpleUser@gmail.com', 'passwordForSimpleUser')], json_encode(['code' => 'new-course', 'title' => 'New Course', 'type' => 'rent', 'price' => '100.5']));
        $this->assertContains('Access denied', $client->getResponse()->getContent());
        $client->request('POST', '/api/v1/courses/mern-stack-front-to-back-full-stack-react-redux-node-js', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$this->auth($client, 'alex@mail.ru', '123456')], json_encode(['code' => 'mern-stack-front-to-back', 'title' => 'New Title For Mern Stack Course', 'type' => 'rent', 'price' => '100.5']));
        $this->assertContains('{"success":true}', $client->getResponse()->getContent());
        $client->request('GET', '/api/v1/courses');
        $courses = json_decode($client->getResponse()->getContent());
        $this->assertContains('mern-stack-front-to-back', $client->getResponse()->getContent());
    }

    public function testDeleteCourse()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/courses/java-programming-masterclass-for-software-developers', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$this->auth($client, 'simpleUser@gmail.com', 'passwordForSimpleUser')], json_encode(['code' => 'new-course', 'title' => 'New Course', 'type' => 'rent', 'price' => '100.5']));
        $this->assertContains('Access denied', $client->getResponse()->getContent());
        $client->request('DELETE', '/api/v1/courses/java-programming-masterclass-for-software-developers', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$this->auth($client, 'alex@mail.ru', '123456')]);
        $this->assertContains('{"success":true}', $client->getResponse()->getContent());
        $client->request('GET', '/api/v1/courses');
        $courses = json_decode($client->getResponse()->getContent());
        $this->assertEquals(3, count($courses));
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
        $this->assertContains('No course found', $client->getResponse()->getContent());
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

    public function testExpiredTokenPay()
    {
        $expiredToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE1NjEzODAwMDQsImV4cCI6MTU2MTM4MDAzNCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoic2ltcGxlVXNlckBnbWFpbC5jb20ifQ.XF2CurGJ6dq6T8pKFfz-WFMzWsDmoHSHMLK3QzDepvla29-dzXsy0U5J_yINdRItMDv5rsdVH4B0e1Qs19-MteWMJBCsUkj4Hh5Ca70sQl5EN1Eu6ceGGte6Jzw9OK7yCSu1I917qklxcTZ3ZmAL3a6-UQK5nDN5LKpHGJ82oZ4kZfJSM_fNjVpSpHgT7yRELXH6P9NDok2ITdwbOVE8bZqDYytFcE7_FBRsE5AckE1dWG7Zn-QKS5uEMekxwa50fZRWqnxDl8uJQBI3EC6r3xdZvFniKDJ4oD3oVCb1cTWvurrj86B786l2-uLk5TY-BjBK_YpcQhcj4Q6AYd0ksdKCJSrhZ5HtoHjG3crGombImZKjxI889cDDbM3xOYZp8PHYo7-uhYbsSZqGffSAye940qONwZXDlMoiPQ6yztpWRWKfHtEu_G8Wb50Behni_WU2zQcBiH19ZeaPLue80prVCMgRnbowsjk9hj45jouLtptSDmxhp6LKgF20bNmwBVHhKzKU-IDmf3QfQ_EoB5R-PrdCitUPNuAie_4SrMJvrFOfVLJWjLFGemp84X3d_vJALGgFHjElklUbT9zIDFqgRKGnQ_YsfbTCSqIijiE0LoDOR7v331O3GFk-y5CNJBBQcQCHHiCONwDTN4ofRaptryz8qtKnocITmfMjiv4';
        $client = static::createClient();
        $client->request('POST', '/api/v1/courses/stack-front-to-back-full-stack-react-redux-node-js/pay', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$expiredToken]);
        $this->AssertContains('"code":401,"message":"Expired JWT Token"', $client->getResponse()->getContent());
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
