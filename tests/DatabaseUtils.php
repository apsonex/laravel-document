<?php

namespace Apsonex\LaravelDocument\Tests;

use Illuminate\Database\Eloquent\Model;

trait DatabaseUtils
{

    protected function createBatchJobSchema()
    {
        $this->schema()->create('job_batches', function ($table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->text('failed_job_ids');
            $table->text('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        $this->schema()->create('jobs', function ($table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

    }

    /**
     * Get a database connection instance.
     */
    protected function connection(): \Illuminate\Database\ConnectionInterface
    {
        return Model::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     */
    protected function schema(): \Illuminate\Database\Schema\Builder
    {
        return $this->connection()->getSchemaBuilder();
    }



}