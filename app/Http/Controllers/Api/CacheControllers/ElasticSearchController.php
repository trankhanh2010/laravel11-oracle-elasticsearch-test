<?php

namespace App\Http\Controllers\Api\CacheControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Elasticsearch\ClientBuilder;

class ElasticSearchController extends Controller
{
    public function index()
    {
        $client = ClientBuilder::create()
            ->setHosts(config('database.connections.elasticsearch.hosts'))
            ->build();

        // Example search query
        $params = [
            'index' => 'my-index',

        ];

        $response = $client->search($params);
        return $response;
    }

    public function area()
    {
        $client = ClientBuilder::create()
            ->setHosts(config('database.connections.elasticsearch.hosts'))
            ->build();

        // Example search query
        $params = [
            'index' => 'area',

        ];

        $response = $client->search($params);
        return $response;
    }

    public function service($key)
    {
        $client = ClientBuilder::create()
            ->setHosts(config('database.connections.elasticsearch.hosts'))
            ->build();

        // Example search query
        $params = [
            'index' => 'service',
            'body' => [
                'query' => [
                    'match' => [
                        'service_name' => $key
                    ]
                ]
            ]
        ];

        $response = $client->search($params);
        return $response;
    }
}
