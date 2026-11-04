<?php

namespace Eloquentize\LaravelClient\Commands\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

trait SendMetricsData
{
    protected function sendMetricsData($data, $token)
    {
        $url = Config::get('eloquentize.api_url').'/api/metrics/models';
        try {
            $response = Http::acceptJson()->withToken($token)->post($url, $data);

            if ($response->successful()) {
                $this->info('Data successfully sent to Eloquentize!');
            } else {
                $this->error('Data sending failed');
                $this->error($response->body());
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
