<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearAllData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:clear {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all data from business tables (keeps system tables like users, migrations, cache)';

    /**
     * Tables to exclude from clearing (system tables)
     *
     * @var array
     */
    protected $excludedTables = [
        'migrations',
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Ви впевнені, що хочете очистити всі дані? Це незворотня операція!', false)) {
                $this->info('Операцію скасовано.');
                return Command::FAILURE;
            }
        }

        $this->info('Початок очищення даних...');

        // Disable foreign key checks for SQLite
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        try {
            // Get all tables
            $tables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();

            $clearedTables = [];

            foreach ($tables as $table) {
                // Skip system tables
                if (in_array($table, $this->excludedTables)) {
                    continue;
                }

                // Clear table data
                DB::table($table)->truncate();
                $clearedTables[] = $table;
                $this->line("  ✓ Очищено таблицю: {$table}");
            }

            // Re-enable foreign key checks for SQLite
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }

            $this->newLine();
            $this->info('✓ Очищення завершено успішно!');
            $this->info('Очищено таблиць: ' . count($clearedTables));
            $this->newLine();
            $this->warn('Системні таблиці (users, migrations, cache) залишились без змін.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Re-enable foreign key checks for SQLite in case of error
            if (DB::getDriverName() === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }

            $this->error('Помилка при очищенні даних: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
