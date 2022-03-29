<?php


namespace Apsonex\LaravelDocument\Tests;


use Apsonex\LaravelDocument\DocumentServiceProvider;
use Apsonex\LaravelDocument\Models\Document;
use Apsonex\Mls\MlsServiceProvider;
use Apsonex\SaasUtils\SaasUtilsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'queue.default'             => 'database',
            'filesystems.disks.private' => [
                'driver'     => 'local',
                'root'       => storage_path('app/private'),
                'url'        => env('APP_URL') . '/storage',
                'visibility' => 'private',
            ]
        ]);


        $this->createBatchJobSchema();
    }

    protected function getPackageProviders($app): array
    {
        return [
            DocumentServiceProvider::class,
            SaasUtilsServiceProvider::class,
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
        \Illuminate\Support\Facades\File::deleteDirectory(
            $this->getprivateStoragePath()
        );

        \Illuminate\Support\Facades\File::deleteDirectory(
            $this->getPublicStoragePath()
        );
    }


    protected function getPublicStoragePath(): string
    {
        return __DIR__ . '/../vendor/orchestra/testbench-core/laravel/storage/app/public';
    }

    protected function getPrivateStoragePath(): string
    {
        return __DIR__ . '/../vendor/orchestra/testbench-core/laravel/storage/app/private';
    }

    /**
     * @param string $name
     * @return UploadedFile
     */
    protected function testFile(string $name): UploadedFile
    {
        return (new UploadedFile(__DIR__ . '/fixtures/' . $name, $name, null, true));
    }


    protected function seedDummyDocument($path, UploadedFile $file)
    {
        Storage::disk('public')->put($path, $file, 'public');

        return Document::create([
            'documentable_id'   => null,
            'documentable_type' => null,
            'status'            => null,
            'group'             => 'default',
            'media_path'        => Str::uuid(),
            'order'             => 0,
            'type'              => str($file->getMimeType())->startsWith('image/') ? 'image' : 'document',
            'mime'              => $file->getMimeType(),
            'path'              => $path,
            'disk'              => 'public',
            'visibility'        => 'public',
            'size'              => Storage::disk('public')->size($path),
            'variations'        => []
        ]);
    }
}
