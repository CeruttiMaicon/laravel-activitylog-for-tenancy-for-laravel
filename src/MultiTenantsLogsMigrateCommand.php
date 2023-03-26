<?php

namespace Spatie\Activitylog;

use Illuminate\Console\Command;

class MultiTenantsLogsMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multi_tenants_logs:migrate {--tenants= : The tenant log to be migrated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations for tenant(s) logs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tenantModel = config('activitylog.tenant_model');

        $tenantsMain = $tenantModel::whereNot('id', 'like', '%_logs')->get();

        foreach ($tenantsMain as $tenantMain) {
            $tenantIdLogs = $tenantMain->id.'_logs';

            if (! $tenantModel::find($tenantIdLogs)) {
                $tenantModel::create(['id' => $tenantIdLogs]);
            }
        }

        $tenants = $tenantModel::where('id', 'like', '%_logs')->get();

        if ($this->option('tenants')) {
            // NOTE - If the --tenants parameter was passed, there should be no option with _logs suffix
            if (strpos($this->option('tenants'), '_logs') !== false) {
                $this->error('Invalid tenant log name. Tenant log name cannot have _logs suffix.');
            }

            $tenants = $tenantModel::where('id', $this->option('tenants'))->get();

            if ($tenants->count() == 0) {
                $this->error('Invalid tenant log name. Tenant log name does not exist.');
            }
        }

        foreach ($tenants as $tenant) {
            $this->call('tenants:migrate', [
                '--tenants' => $tenant->id,
                '--path' => 'database/migrations/tenant_logs',
            ]);
        }

        return 0;
    }
}
