<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Entry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:entries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate entries from the old system to the new system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('entries')->truncate();
        DB::table('categories')->truncate();
        Schema::enableForeignKeyConstraints();

        DB::connection('mysqlold')
            ->table('budget_entries')
            ->leftJoin('categories', 'budget_entries.category_id', '=', 'categories.id')
            ->leftJoin(
                'shared_users',
                fn($join) =>
                $join->on('budget_entries.budgetable_id', '=', 'shared_users.id')
                    ->where('budget_entries.budgetable_type', '=', 'App\Models\SharedUser')
            )
            ->select([
                'budget_entries.id',
                'budget_entries.created_at',
                'budget_entries.updated_at',
                'budget_entries.transaction_date AS date',
                'budget_entries.transaction_type AS type',
                'budget_entries.amount',
                'budget_entries.description',
                'categories.id AS category_id',
                'categories.created_at AS category_created_at',
                'categories.updated_at AS category_updated_at',
                'categories.title AS category_name',
                'categories.color AS category_color',
                DB::raw('CASE WHEN budget_entries.budgetable_type = "App\\\Models\\\User" THEN budget_entries.budgetable_id ELSE null END AS user_id'),
                DB::raw('CASE WHEN budget_entries.budgetable_type = "App\\\Models\\\SharedUser" THEN shared_users.shared_by_user_id ELSE null END AS shared_by_user_id'),
                DB::raw('CASE WHEN budget_entries.budgetable_type = "App\\\Models\\\SharedUser" THEN shared_users.shared_to_user_id ELSE null END AS shared_to_user_id'),
            ])
            ->orderBy('id')
            ->chunk(20, function ($entries) {
                foreach ($entries as $entry) {
                    if ($entry->user_id) {
                        $category = $entry->category_name ? Category::firstOrCreate(
                            ['name' => $entry->category_name, 'user_id' => $entry->user_id],
                            ['color' => $entry->category_color]
                        ) : null;

                        Entry::create([
                            'date'        => $entry->date,
                            'type'        => $entry->type,
                            'amount'      => $entry->amount,
                            'description' => $entry->description,
                            'category_id' => $category?->id,
                            'user_id'     => $entry->user_id,
                        ]);
                    } else {
                        $category = $entry->category_name ? Category::firstOrCreate(
                            ['name' => $entry->category_name, 'user_id' => $entry->shared_by_user_id],
                            ['color' => $entry->category_color]
                        ) : null;

                        Entry::create([
                            'date'        => $entry->date,
                            'type'        => $entry->type,
                            'amount'      => $entry->amount,
                            'description' => $entry->description,
                            'category_id' => $category?->id,
                            'user_id'     => $entry->shared_by_user_id,
                        ]);

                        $category = $entry->category_name ? Category::firstOrCreate(
                            ['name' => $entry->category_name, 'user_id' => $entry->shared_to_user_id],
                            ['color' => $entry->category_color]
                        ) : null;

                        Entry::create([
                            'date'        => $entry->date,
                            'type'        => $entry->type,
                            'amount'      => $entry->amount,
                            'description' => $entry->description,
                            'category_id' => $category?->id,
                            'user_id'     => $entry->shared_to_user_id,
                        ]);
                    }
                }
            });
    }
}
