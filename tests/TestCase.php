<?php


namespace Apsonex\LaravelDocument\Tests;


use Apsonex\LaravelDocument\DocumentServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use DatabaseUtils;

    protected $loadEnvironmentVariables = true;

    protected array $validIdxCredentials = [
        'username' => null,
        'password' => null,
        'loginUrl' => null,
    ];

    protected array $validVowCredentials = [
        'username' => null,
        'password' => null,
        'loginUrl' => null,
    ];

    public function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'Apsonex\\Document\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );

        config([
            'queue.default' => 'database',
        ]);


        $this->createBatchJobSchema();
    }

    protected function getPackageProviders($app): array
    {
        return [
            DocumentServiceProvider::class
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // make sure, our .env file is loaded
        $app->useEnvironmentPath(__DIR__ . '/..');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);
        parent::getEnvironmentSetUp($app);
        config()->set('database.default', 'testing');
    }

    protected function processAllJobs($callback = null)
    {
        $jobs = $this->allJobs();

        if ($callback) {
            $jobs->each(fn($job) => $callback($job));
        } else {
            $jobs->each(function ($job) {
                $jobClass = unserialize(json_decode($job->payload, true)['data']['command']);
                $jobClass->handle();
            });
        }
    }

    protected function allJobs(): Collection
    {
        return DB::table('jobs')->get();
    }

    protected function cleanStorage()
    {
        \Illuminate\Support\Facades\File::deleteDirectory(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/storage/app/public');
    }

    protected function testFile($name): UploadedFile
    {
        return (new UploadedFile(__DIR__ . '/fixtures/' . $name,$name, null, true));
    }

}
