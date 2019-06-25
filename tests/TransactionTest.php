<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use App\DataFixtures\BillingUserFixtures;
use App\Tests\AbstractTest;

class TransactionTest extends AbstractTest
{
    public function getFixtures(): array
    {
        return [BillingUserFixtures::class];
    }

    public function regNewUser($client)
    {
        $client->request('POST', '/api/v1/register', [], [], [], json_encode(['email' => 'user@gmail.com', 'password' => '1234567']));
        $response = json_decode($client->getResponse()->getContent(), true);
        return $response['token'];
    }

    public function authUser($client)
    {
        $client->request('POST', '/api/v1/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['username' => 'alex@mail.ru', 'password' => '123456']));
        $response = json_decode($client->getResponse()->getContent(), true);
        return $response['token'];
    }

    public function testGetFirstDeposit()
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/transactions', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '. $this->regNewUser($client)]);
        $this->AssertContains('"type":"deposit","amount":1000.0', $client->getResponse()->getContent());
    }

    public function testFilterByPayment()
    {
        $client = static::createClient();
        $token = $this->authUser($client);
        $client->request('POST', '/api/v1/courses/mern-stack-front-to-back-full-stack-react-redux-node-js/pay', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        $client->request('GET', '/api/v1/transactions?type=payment', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '. $token]);
        $this->AssertContains('"type":"payment","course_code":"mern-stack-front-to-back-full-stack-react-redux-node-js"', $client->getResponse()->getContent());
    }

    public function testFilterByCourse()
    {
        $client = static::createClient();
        $token = $this->authUser($client);
        $client->request('POST', '/api/v1/courses/build-a-blockchain-and-a-cryptocurrency-from-scratch/pay', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        $client->request('GET', '/api/v1/transactions?course_code=build-a-blockchain-and-a-cryptocurrency-from-scratch', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '. $token]);
        $this->AssertContains('"type":"payment","course_code":"build-a-blockchain-and-a-cryptocurrency-from-scratch","amount":20.25', $client->getResponse()->getContent());
    }

    public function testFilterSkipExpired()
    {
        $client = static::createClient();
        $token = $this->authUser($client);
        $client->request('GET', '/api/v1/transactions?skip_expired=true', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '. $token]);
        $this->AssertSame(3, count(json_decode($client->getResponse()->getContent())));
    }

    public function testFilterWrongType()
    {
        $client = static::createClient();
        $token = $this->authUser($client);
        $client->request('GET', '/api/v1/transactions?type=paymenttt', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '. $token]);
        $this->AssertContains('"code":400,"message":"Type must be payment or deposit"', $client->getResponse()->getContent());
    }

    public function testFilterWrongCourse()
    {
        $client = static::createClient();
        $token = $this->authUser($client);
        $client->request('GET', '/api/v1/transactions?course_code=build-from-scratch', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '. $token]);
        $this->AssertContains('"code":404,"message":"No course found"', $client->getResponse()->getContent());
    }

    public function testGetTransactionsExpiredToken()
    {
        $expiredToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE1NjEzODAwMDQsImV4cCI6MTU2MTM4MDAzNCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoic2ltcGxlVXNlckBnbWFpbC5jb20ifQ.XF2CurGJ6dq6T8pKFfz-WFMzWsDmoHSHMLK3QzDepvla29-dzXsy0U5J_yINdRItMDv5rsdVH4B0e1Qs19-MteWMJBCsUkj4Hh5Ca70sQl5EN1Eu6ceGGte6Jzw9OK7yCSu1I917qklxcTZ3ZmAL3a6-UQK5nDN5LKpHGJ82oZ4kZfJSM_fNjVpSpHgT7yRELXH6P9NDok2ITdwbOVE8bZqDYytFcE7_FBRsE5AckE1dWG7Zn-QKS5uEMekxwa50fZRWqnxDl8uJQBI3EC6r3xdZvFniKDJ4oD3oVCb1cTWvurrj86B786l2-uLk5TY-BjBK_YpcQhcj4Q6AYd0ksdKCJSrhZ5HtoHjG3crGombImZKjxI889cDDbM3xOYZp8PHYo7-uhYbsSZqGffSAye940qONwZXDlMoiPQ6yztpWRWKfHtEu_G8Wb50Behni_WU2zQcBiH19ZeaPLue80prVCMgRnbowsjk9hj45jouLtptSDmxhp6LKgF20bNmwBVHhKzKU-IDmf3QfQ_EoB5R-PrdCitUPNuAie_4SrMJvrFOfVLJWjLFGemp84X3d_vJALGgFHjElklUbT9zIDFqgRKGnQ_YsfbTCSqIijiE0LoDOR7v331O3GFk-y5CNJBBQcQCHHiCONwDTN4ofRaptryz8qtKnocITmfMjiv4';
        $client = static::createClient();
        $client->request('GET', '/api/v1/transactions', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$expiredToken]);
        $this->AssertContains('"code":401,"message":"Expired JWT Token"', $client->getResponse()->getContent());
    }
}
