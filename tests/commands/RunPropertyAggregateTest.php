<?php

use App\Testing\Models\Bill;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

it('ensure property-aggregate sum is callable', function () {
    Http::fake([
        config('eloquentize.api_url').'/api/metrics/models' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->artisan('eloquentize:property-aggregate Bill sum price --modelsPath=Testing/Models -v ')
        ->assertExitCode(Command::SUCCESS);

})->with([
    fn() => Bill::factory()->create(['ref' => 'BILL_0000001', 'price' => 1000]),
    fn() => Bill::factory()->create(['ref' => 'BILL_0000001', 'price' => 500]),
]);

it('ensure property-aggregate-legacy sum is callable', function () {
    Http::fake([
        config('eloquentize.api_url').'/api/metrics/models' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->artisan('eloquentize:property-aggregate-legacy Bill sum price 01/02/2024 --modelsPath=Testing/Models -v ')
        ->assertExitCode(Command::SUCCESS);

})->with([
    fn() => Bill::factory()->create(['ref' => 'BILL_0000001', 'price' => 1000]),
    fn() => Bill::factory()->create(['ref' => 'BILL_0000002', 'price' => 500]),
]);

// it('ensure property-aggregate sum return an error if Model do not exists', function () {
//     Http::fake([
//         config('eloquentize.api_url').'/api/metrics/models' => Http::response(['status' => 'ok'], 200),
//     ]);

//     $this->artisan('eloquentize:property-aggregate BillyBu sum price 01/02/2024 --modelsPath=Testing/Models -v ')
//         ->assertExitCode(Command::SUCCESS);

// })->with([
//     fn() => Bill::factory()->create(['ref' => 'BILL_0000001', 'price' => 1000]),
// ])->only();