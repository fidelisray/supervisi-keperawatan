<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nursing Supervision App Role Tables
    |--------------------------------------------------------------------------
    |
    | Read operations for application user access pull from the SQL Server view
    | `vAppRoles` by default. Write operations target the temporary master
    | tables during development/testing and can be switched to `tb_approles`
    | and `ref_approles` in production via environment variables.
    |
    */

    'app_roles_read_table' => env('SUPERVISI_APP_ROLES_READ_TABLE', 'vAppRoles'),

    'app_roles_write_table' => env('SUPERVISI_APP_ROLES_WRITE_TABLE', 'tb_master_nurse_supervision_approles'),

    'ref_app_roles_table' => env('SUPERVISI_REF_APP_ROLES_TABLE', 'ref_master_nurse_supervision_approles'),

    /*
    |--------------------------------------------------------------------------
    | Reverse Proxy & HTTPS Scheme Configuration
    |--------------------------------------------------------------------------
    |
    | Stored in config so TrustProxies and Host-Aware HTTPS resolution remain
    | deterministic when config:cache is enabled in production containers.
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES'),

    'force_https' => filter_var(env('APP_FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN),
];
