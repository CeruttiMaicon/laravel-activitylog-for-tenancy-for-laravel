<?php

namespace CeruttiMaicon\Commands;

use Illuminate\Console\Command;

class MultiTenantsListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multi_tenants:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List tenants.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenants = \App\Models\Tenant::whereNot('id', 'like', '%_logs')->get();

        $database = env('DB_DATABASE');

        $this->table(["{$database}.tenants.id", 'Tenant Main', 'Tenant Logs'], $tenants->map(function ($tenant) use ($database) {
            return [
                "{$database}.tenants.id" => $tenant->id,
                'Tenant Main' => $tenant->id,
                'Tenant Logs' => $tenant->id . '_logs',
            ];
        })->toArray());

        return 0;
    }
}
