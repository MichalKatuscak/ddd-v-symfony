<?php

declare(strict_types=1);

namespace App\Controller;

use App\Search\SearchIndexBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SearchController extends AbstractController
{
    public function __construct(
        private readonly SearchIndexBuilder $builder,
        private readonly CacheInterface $cache,
    ) {}

    #[Route('/search-index.json', name: 'search_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $data = $this->cache->get('search_index_v1', function (ItemInterface $item): array {
            $item->expiresAfter(86400);

            return $this->builder->build();
        });

        $response = new JsonResponse($data);
        // Statický obsah – nechej prohlížeč i CDN cachovat, ale revaliduj.
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
