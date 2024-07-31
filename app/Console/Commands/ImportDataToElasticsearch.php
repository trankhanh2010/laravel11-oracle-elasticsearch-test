<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;

class ImportDataToElasticsearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-data-to-elasticsearch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = ClientBuilder::create()
        ->setHosts(config('database.connections.elasticsearch.hosts'))
        ->build();

        // Thực hiện query để lấy dữ liệu từ Oracle
        $results = DB::connection('oracle_his')->table('his_area')->get();

        foreach ($results as $result) {
            $params = [
                'index' => 'area',
                'id'    => $result->id, // Sử dụng id từ dữ liệu của bạn
                'body'  => [
                    'area_code' => $result->area_code,
                    'area_name' => $result->area_name,
                ]
            ];

        $client->index($params);
        }

        $results = DB::connection('oracle_his')->table('his_service')->get();

        foreach ($results as $result) {
            $params = [
                'index' => 'service',
                'id'    => $result->id, // Sử dụng id từ dữ liệu của bạn
                'body'  => [
                    'service_code' => $result->service_code,
                    'service_name' => $result->service_name,
                ]
            ];

        $client->index($params);
        }

        $this->info('Data imported to Elasticsearch successfully.');
    }
}
