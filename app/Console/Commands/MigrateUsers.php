<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate users from old database to new database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::table('users')->truncate();

        DB::connection('mysqlold')->table('users')->orderBy('id')->chunk(100, function ($users) {
            DB::table('users')->insert(
                $users->map(function ($user) {
                    return (array) $user;
                })->toArray()
            );
        });
    }
}
