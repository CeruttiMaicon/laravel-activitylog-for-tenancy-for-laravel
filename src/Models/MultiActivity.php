<?php

namespace Spatie\Activitylog\Models;

use Spatie\Activitylog\Models\Activity;

class MultiActivity extends Activity
{
    private $tenant_main;
    private $tenant_log;

    public function __construct()
    {
        // se tenant('id') tiver o sufixo _logs, remover o sufixo
        $tenant = tenant('id');
        if (strpos($tenant, '_logs') !== false) {
            $tenant = str_replace('_logs', '', $tenant);
        }

        $this->tenant_main = $tenant;
        $this->tenant_log = $this->tenant_main . '_logs';

        tenancy()->initialize($this->tenant_log);
    }

    public function setTenantMain()
    {
        tenancy()->initialize($this->tenant_main);
    }

    public function setTenantLog()
    {
        tenancy()->initialize($this->tenant_log);
    }

    public function __destruct()
    {
        $this->setTenantMain();
    }

    /**
     * Add functionality to the model to allow it to be used as a tenant model.
     *
     * @param $table
     *
     * @return Model
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }
}
