<?php

use App\Testing\Models\Bill;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Output\BufferedOutput;

it('ensure models-count is callable', function () {
    // Http::fake([
    //     config('eloquentize.api_url').'/api/metrics/models' => Http::response(['status' => 'ok'], 200),
    // ]);

    $this->artisan('eloquentize:models-count --modelsPath=Testing/Models -v --models=Bill --scope=PriceOver --scopeValue=1 ')
        ->assertExitCode(Command::SUCCESS);

    // $this->output = new BufferedOutput();
    // Artisan::call('eloquentize:models-count --modelsPath=Testing/Models -v --models=Bill --scope=PriceOver --scopeValue=1 -v',[], $this->output);
    // $outputContent = $this->output->fetch();
    // echo "out :".$outputContent;
})->with([
    fn () => Bill::factory()->create(['ref' => 'BILL_0000001', 'price' => 1000]),
    fn () => Bill::factory()->create(['ref' => 'BILL_0000002', 'price' => 500]),
]);
