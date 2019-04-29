<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use App\DataFixtures\BillingUserFixtures;
use App\Tests\AbstractTest;

class BillingUserControllerTest extends AbstractTest
{
    public function getFixtures(): array
    {
        return [BillingUserFixtures::class];
    }

    public function testRegisterNewUser()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/register', [], [], [], json_encode(['email' => 'user@gmail.com', 'password' => '1234567']));
        $this->AssertContains('"token"', $client->getResponse()->getContent());
        $this->AssertContains('"roles":["ROLE_USER"]', $client->getResponse()->getContent());
        $this->assertSame(201, $client->getResponse()->getStatusCode());
    }

    public function testRegisterExistingUser()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/register', [], [], [], json_encode(['email' => 'simpleUser@gmail.com', 'password' => '1234567']));
        $this->AssertContains('"errors":["Email already exists"]', $client->getResponse()->getContent());
        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testRegisterBlankData()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/register', [], [], [], json_encode(['email' => '', 'password' => '']));
        $this->AssertContains('"errors":["Blank email","Blank password"]', $client->getResponse()->getContent());
        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testRegisterInvalidData()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/register', [], [], [], json_encode(['email' => 'userGmail.com', 'password' => '123']));
        $this->AssertContains('"errors":["Invalid email","Password must be at least 6 symbols"]', $client->getResponse()->getContent());
        $this->assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testLoginTrueUser()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['username' => 'simpleUser@gmail.com', 'password' => 'passwordForSimpleUser']));
        $this->AssertContains('"token"', $client->getResponse()->getContent());
        $this->AssertContains('"roles":["ROLE_USER"]', $client->getResponse()->getContent());
    }

    public function testLoginUserInvalidPassword()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['username' => 'simpleUser@gmail.com', 'password' => 'passwordSimpleUser']));
        $this->AssertContains('"code":401,"message":"Bad credentials, please verify your username and password"', $client->getResponse()->getContent());
    }

    public function testLoginUserInvalidEmail()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['username' => 'User@gmail.com', 'password' => 'passwordForSimpleUser']));
        $this->AssertContains('"code":401,"message":"Bad credentials, please verify your username and password"', $client->getResponse()->getContent());
    }

    public function testLoginUserBlankData()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['username' => '', 'password' => '']));
        $this->AssertContains('"code":401,"message":"Bad credentials, please verify your username and password"', $client->getResponse()->getContent());
    }
}
