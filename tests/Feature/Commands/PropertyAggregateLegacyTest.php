<?php

use App\Testing\Models\Bill;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Set up config
    Config::set('eloquentize.api_url', 'https://api.eloquentize.com');
    Config::set('eloquentize.ELOQUENTIZE_API_TOKEN', 'test-token');
    Config::set('app.url', 'https://example.com');
    Config::set('app.env', 'testing');
    Config::set('app.timezone', 'UTC');

    // Fake HTTP responses
    Http::fake([
        'https://api.eloquentize.com/api/metrics/models' => Http::response(['status' => 'ok'], 200),
    ]);

    // "Now" is 2023-01-15, so "yesterday" is 2023-01-14
    Carbon::setTestNow(Carbon::parse('2023-01-15 12:00:00'));
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * The (unique) days, as Y-m-d, for which a metrics payload was sent to the API.
 *
 * @return array<int, string>
 */
function legacySentDays(): array
{
    return collect(Http::recorded())
        ->map(fn (array $pair) => Carbon::parse($pair[0]->data()['start'])->format('Y-m-d'))
        ->unique()
        ->sort()
        ->values()
        ->all();
}

$expectedDays = ['2023-01-10', '2023-01-11', '2023-01-12', '2023-01-13', '2023-01-14'];

test('property-aggregate-legacy command executes successfully', function () use ($expectedDays) {
    // The range starts at the oldest record and goes up to yesterday
    Bill::factory()->create(['amount' => 100, 'created_at' => '2023-01-10 10:00:00']);
    Bill::factory()->create(['amount' => 200, 'created_at' => '2023-01-13 10:00:00']);

    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    expect(legacySentDays())->toBe($expectedDays);
});

test('property-aggregate-legacy command with specific date', function () use ($expectedDays) {
    Bill::factory()->create(['amount' => 100, 'created_at' => '2023-01-12 10:00:00']);

    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum 10/01/2023 --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    expect(legacySentDays())->toBe($expectedDays);
});

test('property-aggregate-legacy command with custom date format', function () use ($expectedDays) {
    Bill::factory()->create(['amount' => 100, 'created_at' => '2023-01-12 10:00:00']);

    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum 2023-01-10 --dateFormat=Y-m-d --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    expect(legacySentDays())->toBe($expectedDays);
});

test('property-aggregate-legacy command with custom event', function () {
    Bill::factory()->create(['amount' => 100, 'created_at' => '2023-01-12 10:00:00', 'updated_at' => '2023-01-12 10:00:00']);

    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum 12/01/2023 updated_at --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    $payloads = collect(Http::recorded())->map(fn (array $pair) => $pair[0]->data());

    expect($payloads)->not->toBeEmpty()
        ->and($payloads->pluck('event_type')->unique()->all())->toBe(['updated_at']);
});

test('property-aggregate-legacy command with scope and scopeValue', function () {
    Bill::factory()->create(['amount' => 100, 'price' => 1000, 'created_at' => '2023-01-12 10:00:00']);
    Bill::factory()->create(['amount' => 50, 'price' => 10, 'created_at' => '2023-01-12 10:00:00']);

    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum 12/01/2023 --scope=priceOver --scopeValue=500 --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Only the bill with price > 500 is aggregated on 2023-01-12
    $payload = collect(Http::recorded())
        ->map(fn (array $pair) => $pair[0]->data())
        ->first(fn (array $data) => Carbon::parse($data['start'])->format('Y-m-d') === '2023-01-12');

    $metric = $payload['metrics'][0];

    expect($payload['metrics'])->toHaveCount(1)
        ->and($metric->label)->toBe('Bill::priceOver(500)::amount->sum()')
        ->and($metric->count)->toBe(100);
});

test('property-aggregate-legacy command with invalid date format', function () {
    // Execute command with invalid date
    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum invalid-date --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('Invalid date format. The date should be formatted according to the provided date format: d/m/Y');
});

test('property-aggregate-legacy command with invalid aggregation type', function () {
    // Execute command with invalid aggregation
    $this->artisan('eloquentize:property-aggregate-legacy Bill amount invalid --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('Eloquentize error occurred: invalid is not a valid aggregation type ( min, max, avg, sum )');
});
