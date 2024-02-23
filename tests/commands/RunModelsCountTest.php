<?php

use App\Testing\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;


it('ensure models-count is callable', function () {
    Http::fake([
        config('eloquentize.api_url').'/api/metrics/models' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->artisan('eloquentize:models-count --modelsPath=Testing/Models -v ')
        ->assertExitCode(Command::SUCCESS);

    // $this->output = new BufferedOutput();
    // Artisan::call('eloquentize:models-count -v',[], $this->output);
    // $outputContent = $this->output->fetch();
    // echo "out :".$outputContent;
})->with([
    fn() => User::factory()->create(['name' => 'Alexis Desmarais']),
]);

it('ensure models-count-legacy is callable and return ', function () {
    Http::fake([
        config('eloquentize.api_url').'/api/metrics/models' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->artisan('eloquentize:models-count-legacy --modelsPath=Testing/Models -v')
    ->assertExitCode(Command::SUCCESS);

    // $this->output = new BufferedOutput();
    // Artisan::call('eloquentize:models-count-legacy --modelsPath=Testing/Models -v',[], $this->output);
    // $outputContent = $this->output->fetch();
    // echo "out :".$outputContent;

})->with([
    fn() => User::factory()->create(['name' => 'Alexis Desmarais', 'created_at' => now()->subDays(1)]),
    fn() => User::factory()->create(['name' => 'Alexis Test', 'created_at' => now()->subDays(2)]),
]);

