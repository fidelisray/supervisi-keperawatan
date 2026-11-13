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
];

