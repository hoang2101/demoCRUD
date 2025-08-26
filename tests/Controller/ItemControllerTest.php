<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;

class ItemControllerTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testIndex(): void
    {
        // Tạo sẵn 1 item trong DB
        $item = new Item();
        $item->setName('Test Item');
        $item->setDate(new \DateTime());
        $this->em->persist($item);
        $this->em->flush();

        $this->client->request('GET', '/item');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotNull($data);
        $this->assertEquals('Test Item', $data[0]['name']);
    }

    public function testCreate(): void
    {
        $this->client->request(
            'POST',
            '/item',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'New Item'])
        );

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);

        // Check DB
        $item = $this->em->getRepository(Item::class)->find($data['id']);
        $this->assertNotNull($item);
        $this->assertEquals('New Item', $item->getName());
    }

    public function testUpdate(): void
    {
        $item = new Item();
        $item->setName('Old Name');
        $item->setDate(new \DateTime());
        $this->em->persist($item);
        $this->em->flush();

        $this->client->request(
            'PUT',
            '/item/'.$item->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Updated Name'])
        );

        $this->assertResponseIsSuccessful();

        $updated = $this->em->getRepository(Item::class)->find($item->getId());
        $this->assertEquals('Updated Name', $updated->getName());
    }

    public function testDelete(): void
    {
        $item = new Item();
        $item->setName('Delete Me');
        $item->setDate(new \DateTime());
        $this->em->persist($item);
        $this->em->flush();
        $id = $item->getId();

        $this->client->request('DELETE', '/item/'.$id);
        $this->assertResponseIsSuccessful();

        $deleted = $this->em->getRepository(Item::class)->find($id);
        $this->assertNull($deleted);
    }
}
