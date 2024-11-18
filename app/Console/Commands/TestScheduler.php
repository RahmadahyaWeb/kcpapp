<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-scheduler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'berhasil';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::table('scheduler')
            ->insert([
                'testing' => now()
            ]);
    }
}
