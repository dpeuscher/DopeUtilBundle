<?php

namespace Dope\UtilBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertNotEmpty($crawler);
        $this->assertContains('Hello World', $client->getResponse()->getContent());
    }
}
