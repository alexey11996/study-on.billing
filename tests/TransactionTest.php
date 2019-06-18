<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use App\DataFixtures\CourseFixtures;
use App\Tests\AbstractTest;

class TransactionTest extends AbstractTest
{
    public function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    public function testGetFirstDeposit()
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/register', [], [], [], json_encode(['email' => 'user@gmail.com', 'password' => '1234567']));
        $response = json_decode($client->getResponse()->getContent(), true);
        $client->request('GET', '/api/v1/transactions', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$response['token']]);
        $this->AssertContains('"type":"deposit","amount":1000.0', $client->getResponse()->getContent());
    }
}
