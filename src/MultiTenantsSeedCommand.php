<?php

namespace Spatie\Activitylog;

use Illuminate\Console\Command;

class MultiTenantsSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multi_tenants:seed {--tenants= : The tenants to be migrated} {--class= : The class name of the root seeder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed tenant database(s) main(s)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenants = \App\Models\Tenant::whereNot('id', 'like', '%_logs')->get();

        if ($this->option('tenants')) {
            // NOTE - If the --tenants parameter was passed, there should be no option with _logs suffix
            if (strpos($this->option('tenants'), '_logs') !== false) {
                $this->error('Invalid tenant name. Tenant name cannot have _logs suffix.');
                return 1;
            }

            $tenants = $tenants->whereIn('id', explode(',', $this->option('tenants')));
        }

        $class = $this->option('class') == null ? 'Database\\Seeders\\Tenants\\DatabaseTenantSeeder' : 'Database\\Seeders\\Tenants\\' . $this->option('class');

        foreach ($tenants as $tenant) {
            $this->call('tenants:seed', [
                '--tenants' => $tenant->id,
                '--class' => $class,
            ]);
        }
    }
}
