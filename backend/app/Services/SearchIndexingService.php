<?php

namespace App\Services;

use App\Models\Vendor;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class SearchIndexingService
{
    private $client;
    private string $indexName = 'vendors';

    public function __construct()
    {
        $hosts = [config('services.elasticsearch.host', 'localhost:9200')];

        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }

    /**
     * Create Elasticsearch index with mappings
     */
    public function createIndex(): void
    {
        try {
            $params = [
                'index' => $this->indexName,
                'body' => [
                    'settings' => [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 0,
                        'analysis' => [
                            'analyzer' => [
                                'autocomplete' => [
                                    'tokenizer' => 'autocomplete',
                                    'filter' => ['lowercase'],
                                ],
                                'autocomplete_search' => [
                                    'tokenizer' => 'lowercase',
                                ],
                            ],
                            'tokenizer' => [
                                'autocomplete' => [
                                    'type' => 'edge_ngram',
                                    'min_gram' => 2,
                                    'max_gram' => 10,
                                    'token_chars' => ['letter'],
                                ],
                            ],
                        ],
                    ],
                    'mappings' => [
                        'properties' => [
                            'business_name' => [
                                'type' => 'text',
                                'analyzer' => 'autocomplete',
                                'search_analyzer' => 'autocomplete_search',
                            ],
                            'description' => ['type' => 'text'],
                            'category' => ['type' => 'keyword'],
                            'city' => ['type' => 'keyword'],
                            'country' => ['type' => 'keyword'],
                            'rating_average' => ['type' => 'float'],
                            'rating_count' => ['type' => 'integer'],
                            'verified' => ['type' => 'boolean'],
                            'featured' => ['type' => 'boolean'],
                            'services' => [
                                'type' => 'nested',
                                'properties' => [
                                    'name' => ['type' => 'text'],
                                    'price_from' => ['type' => 'float'],
                                    'price_to' => ['type' => 'float'],
                                ],
                            ],
                            'location' => ['type' => 'geo_point'],
                            'tags' => ['type' => 'keyword'],
                        ],
                    ],
                ],
            ];

            $this->client->indices()->create($params);
        } catch (\Exception $e) {
            Log::error('Failed to create Elasticsearch index: ' . $e->getMessage());
        }
    }

    /**
     * Index a vendor document
     */
    public function indexVendor(Vendor $vendor): void
    {
        try {
            $params = [
                'index' => $this->indexName,
                'id' => $vendor->id,
                'body' => [
                    'business_name' => $vendor->business_name,
                    'description' => $vendor->description,
                    'category' => $vendor->category->name ?? null,
                    'city' => $vendor->city,
                    'country' => $vendor->country,
                    'rating_average' => $vendor->rating_average,
                    'rating_count' => $vendor->rating_count,
                    'verified' => $vendor->verified,
                    'featured' => $vendor->featured,
                    'services' => $vendor->services->map(function ($service) {
                        return [
                            'name' => $service->name,
                            'price_from' => $service->price_from,
                            'price_to' => $service->price_to,
                        ];
                    })->toArray(),
                    'location' => [
                        'lat' => $vendor->latitude,
                        'lon' => $vendor->longitude,
                    ],
                    'tags' => $vendor->tags ?? [],
                ],
            ];

            $this->client->index($params);
        } catch (\Exception $e) {
            Log::error("Failed to index vendor {$vendor->id}: " . $e->getMessage());
        }
    }

    /**
     * Update a vendor document
     */
    public function updateVendor(Vendor $vendor): void
    {
        $this->indexVendor($vendor); // Elasticsearch will update if document exists
    }

    /**
     * Remove vendor from index
     */
    public function deleteVendor(string $vendorId): void
    {
        try {
            $params = [
                'index' => $this->indexName,
                'id' => $vendorId,
            ];

            $this->client->delete($params);
        } catch (\Exception $e) {
            Log::error("Failed to delete vendor {$vendorId} from index: " . $e->getMessage());
        }
    }

    /**
     * Bulk index multiple vendors
     */
    public function bulkIndexVendors(array $vendors): void
    {
        try {
            $params = ['body' => []];

            foreach ($vendors as $vendor) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $this->indexName,
                        '_id' => $vendor->id,
                    ],
                ];

                $params['body'][] = [
                    'business_name' => $vendor->business_name,
                    'description' => $vendor->description,
                    'category' => $vendor->category->name ?? null,
                    'city' => $vendor->city,
                    'country' => $vendor->country,
                    'rating_average' => $vendor->rating_average,
                    'rating_count' => $vendor->rating_count,
                    'verified' => $vendor->verified,
                    'featured' => $vendor->featured,
                    'location' => [
                        'lat' => $vendor->latitude,
                        'lon' => $vendor->longitude,
                    ],
                ];
            }

            $this->client->bulk($params);
        } catch (\Exception $e) {
            Log::error('Failed to bulk index vendors: ' . $e->getMessage());
        }
    }

    /**
     * Search vendors using Elasticsearch
     */
    public function search(array $filters, int $from = 0, int $size = 20): array
    {
        try {
            $must = [];
            $filter = [];

            // Text search
            if (!empty($filters['query'])) {
                $must[] = [
                    'multi_match' => [
                        'query' => $filters['query'],
                        'fields' => ['business_name^3', 'description', 'services.name'],
                        'type' => 'best_fields',
                        'fuzziness' => 'AUTO',
                    ],
                ];
            }

            // Category filter
            if (!empty($filters['category'])) {
                $filter[] = ['term' => ['category' => $filters['category']]];
            }

            // City filter
            if (!empty($filters['city'])) {
                $filter[] = ['term' => ['city' => $filters['city']]];
            }

            // Rating filter
            if (!empty($filters['min_rating'])) {
                $filter[] = ['range' => ['rating_average' => ['gte' => $filters['min_rating']]]];
            }

            // Price range filter
            if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
                $priceFilter = ['nested' => [
                    'path' => 'services',
                    'query' => ['bool' => ['must' => []]],
                ]];

                if (!empty($filters['price_min'])) {
                    $priceFilter['nested']['query']['bool']['must'][] = [
                        'range' => ['services.price_from' => ['gte' => $filters['price_min']]],
                    ];
                }

                if (!empty($filters['price_max'])) {
                    $priceFilter['nested']['query']['bool']['must'][] = [
                        'range' => ['services.price_to' => ['lte' => $filters['price_max']]],
                    ];
                }

                $filter[] = $priceFilter;
            }

            // Geo location search
            if (!empty($filters['latitude']) && !empty($filters['longitude'])) {
                $filter[] = [
                    'geo_distance' => [
                        'distance' => $filters['radius'] ?? '50km',
                        'location' => [
                            'lat' => $filters['latitude'],
                            'lon' => $filters['longitude'],
                        ],
                    ],
                ];
            }

            // Sort
            $sort = [];
            if (!empty($filters['sort_by'])) {
                switch ($filters['sort_by']) {
                    case 'rating':
                        $sort[] = ['rating_average' => 'desc'];
                        $sort[] = ['rating_count' => 'desc'];
                        break;
                    case 'price':
                        $sort[] = ['services.price_from' => 'asc'];
                        break;
                }
            }

            // Featured first
            $sort[] = ['featured' => 'desc'];
            $sort[] = ['_score' => 'desc'];

            $params = [
                'index' => $this->indexName,
                'body' => [
                    'from' => $from,
                    'size' => $size,
                    'query' => [
                        'bool' => [
                            'must' => $must ?: ['match_all' => (object)[]],
                            'filter' => $filter,
                        ],
                    ],
                    'sort' => $sort,
                ],
            ];

            $response = $this->client->search($params);

            return [
                'total' => $response['hits']['total']['value'],
                'hits' => array_map(function ($hit) {
                    return [
                        'id' => $hit['_id'],
                        'score' => $hit['_score'],
                        'source' => $hit['_source'],
                    ];
                }, $response['hits']['hits']),
            ];
        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed: ' . $e->getMessage());
            return ['total' => 0, 'hits' => []];
        }
    }

    /**
     * Get autocomplete suggestions
     */
    public function autocomplete(string $query, int $size = 5): array
    {
        try {
            $params = [
                'index' => $this->indexName,
                'body' => [
                    'size' => $size,
                    '_source' => ['business_name', 'category'],
                    'query' => [
                        'match' => [
                            'business_name' => [
                                'query' => $query,
                                'operator' => 'and',
                            ],
                        ],
                    ],
                ],
            ];

            $response = $this->client->search($params);

            return array_map(function ($hit) {
                return $hit['_source']['business_name'];
            }, $response['hits']['hits']);
        } catch (\Exception $e) {
            Log::error('Autocomplete failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Reindex all vendors
     */
    public function reindexAll(): void
    {
        try {
            // Delete existing index
            if ($this->client->indices()->exists(['index' => $this->indexName])) {
                $this->client->indices()->delete(['index' => $this->indexName]);
            }

            // Create new index
            $this->createIndex();

            // Index all approved vendors
            Vendor::where('status', 'approved')
                ->with(['category', 'services'])
                ->chunk(100, function ($vendors) {
                    $this->bulkIndexVendors($vendors->toArray());
                });
        } catch (\Exception $e) {
            Log::error('Failed to reindex all vendors: ' . $e->getMessage());
        }
    }
}
