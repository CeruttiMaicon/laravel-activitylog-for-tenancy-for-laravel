<?php

namespace Spatie\Activitylog\Models;

use Spatie\Activitylog\Models\Activity;

class MultiActivity extends Activity
{
    private $tenant_main;
    private $tenant_log;

    public function __construct()
    {
        $this->tenant_main = tenant('id');
        $this->tenant_log = $this->tenant_main . '_logs';

        tenancy()->initialize($this->tenant_log);
    }

    public function __destruct()
    {
        tenancy()->initialize($this->tenant_main);
    }
}
