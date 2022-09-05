<?php

namespace Spatie\Activitylog;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanActivitylogCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'activitylog:clean
                            {--table= : (optional) The name of the table to clean}
                            {--tenants= : (optional) The tenants to be cleaned}
                            {log? : (optional) The log name that will be cleaned}
                            {--days= : (optional) Records older than this number of days will be cleaned}
                            {--force : (optional) Force the operation to run when in production}';

    protected $description = 'Clean up old records from the activity log';

    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $tenantsAll = \App\Models\Tenant::whereNot('id', 'like', '%_logs')->get();
        $tenantOption = \App\Models\Tenant::where('id', $this->option('tenants'))->get();

        $tenants = $this->option('tenants') === null
            ? $tenantsAll
            : $tenantOption;

        if ($tenants->count() == 0) {
            $this->error('Invalid tenant name. Tenant name does not exist.');
        }

        $verifyTable = true;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            $tables = [$this->option('table')];

            if (! $this->option('table')) {
                $tables = DB::select('SHOW TABLES');

                // NOTE - Return $tables in array
                $tables = array_map(function ($table) {
                    return array_values((array) $table)[0];
                }, $tables);

                $tables = array_diff($tables, ['migrations']);

                $tables[] = 'default';

                $verifyTable = false;
            }

            if ($verifyTable) {
                if (! Schema::hasTable($this->option('table'))) {
                    $this->error('The table '.$this->option('table').' does not exist.');
                    return 1;
                }
            }

            $this->comment("Cleaning activity log for tenant_id: '{$tenant->id}'");

            $this->line('');

            $log = $this->argument('log');

            $maxAgeInDays = $this->option('days') ?? config('activitylog.delete_records_older_than_days');

            $cutOffDate = Carbon::now()->subDays($maxAgeInDays)->format('Y-m-d H:i:s');

            $activity = ActivitylogServiceProvider::getActivityModelInstance();

            $activity->setTenantLog();

            foreach ($tables as $table) {
                $activity->setTable($table);

                $amountDeleted = $activity->where('created_at', '<', $cutOffDate)
                    ->when($log !== null, function (Builder $query) use ($log) {
                        $query->inLog($log);
                    })
                    ->delete();

                $this->info("Deleted {$amountDeleted} record(s) from the table: {$table}");

                $this->comment('All done!');
            }
        }
    }
}
