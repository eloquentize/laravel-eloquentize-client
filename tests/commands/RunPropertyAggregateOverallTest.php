<?php

use App\Testing\Models\Bill;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

it('ensure property-aggregate-overall sum is callable', function () {
    Http::fake([
        config('eloquentize.api_url').'/api/metrics/models' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->artisan('eloquentize:property-aggregate-overall Bill price sum --modelsPath=Testing/Models -v ')
        ->assertExitCode(Command::SUCCESS);

})->with([
    fn () => Bill::factory()->create(['ref' => 'BILL_0000001', 'price' => 1000]),
    fn () => Bill::factory()->create(['ref' => 'BILL_0000001', 'price' => 500]),
]);
