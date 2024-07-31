<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\DB;

class ImportDataSeriveReqToElasticsearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-data-serive-req-to-elasticsearch';

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

        $batchSize = 1; // Kích thước của mỗi lô dữ liệu
        $lastId = 0; // ID của bản ghi cuối cùng đã xử lý
        $index = 'service_req'; // Tên chỉ mục

        while (true) {
            // Lấy lô dữ liệu từ Oracle
            $results = DB::connection('oracle_his')->table('his_service_req')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($results->isEmpty()) {
                break; // Không còn dữ liệu để xử lý
            }

            $bulkParams = ['body' => []];

            foreach ($results as $result) {
                $bulkParams['body'][] = [
                    'index' => [
                        '_index' => $index,
                        '_id' => $result->id,
                    ]
                ];

                $bulkParams['body'][] = [
                    'service_req_code' => $result->service_req_code,
                    'intruction_time' => $result->intruction_time,
                    'request_loginname' => $result->request_loginname,
                    'request_username' => $result->request_username,
                ];

                $lastId = $result->id; // Cập nhật ID của bản ghi cuối cùng đã xử lý
            }

            // Gửi yêu cầu bulk đến Elasticsearch
            
            $client->bulk($bulkParams);

            // Giải phóng bộ nhớ
            unset($bulkParams);
            $this->info('import '.$batchSize. ' ban ghi vao ElasticSearch');

        }

        $this->info('Data imported to Elasticsearch successfully.');
    }
}
