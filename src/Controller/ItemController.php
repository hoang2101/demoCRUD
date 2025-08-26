<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Item;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class ItemController extends AbstractController
{
    #[Route('/item', methods: ['GET'])]
    public function index(EntityManagerInterface $em, CacheInterface $cache): JsonResponse {
        $data = $cache->get('items_all', function (ItemInterface $item) use ($em) {
            $item->expiresAfter(30);

            $items = $em->getRepository(Item::class)->findAll();

            return array_map(fn(Item $i) => [
                'id' => $i->getId(),
                'name' => $i->getName(),
                'date' => $i->getDate()->format('Y-m-d H:i:s'),
            ], $items);
        });

        return new JsonResponse($data);
    }

    #[Route('/item', methods: ['POST'])]
    public function create(Request $req, EntityManagerInterface $em): JsonResponse {
        $data = json_decode($req->getContent(), true);

        $item = new Item();
        $item->setName($data['name']);
        $item->setDate(new \DateTime());

        $em->persist($item);
        $em->flush();

        return new JsonResponse(['id' => $item->getId()], 201);
    }

    #[Route('/item/{id}', methods: ['PUT'])]
    public function update($id, Request $req, EntityManagerInterface $em): JsonResponse {
        $item = $em->getRepository(Item::class)->find($id);
        if (!$item) return new JsonResponse(['error' => 'Not found'], 404);

        $data = json_decode($req->getContent(), true);
        if (isset($data['name'])) $item->setName($data['name']);
        if (isset($data['date'])) $item->setDate(new \DateTime($data['date']));

        $em->flush();

        return new JsonResponse(['status' => 'updated']);
    }

    #[Route('/item/{id}', methods: ['DELETE'])]
    public function delete($id, EntityManagerInterface $em): JsonResponse {
        $item = $em->getRepository(Item::class)->find($id);
        if (!$item) return new JsonResponse(['error' => 'Not found'], 404);

        $em->remove($item);
        $em->flush();

        return new JsonResponse(['status' => 'deleted']);
    }
}
