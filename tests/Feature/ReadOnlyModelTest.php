<?php

use App\Models\AppRole;
use App\Models\AppRoleAssignment;
use App\Models\Employee;
use App\Models\GlobalAccount;
use App\Models\RefAppRole;
use App\Models\Room;
use Illuminate\Database\Eloquent\MassAssignmentException;

test('employee, room, global account, and app role models throw exception when attempting to create, update, or delete', function () {
    foreach ([Employee::class, Room::class, GlobalAccount::class, AppRole::class] as $modelClass) {
        expect(function () use ($modelClass) {
            $modelClass::create(['nip' => '9999999999']);
        })->toThrow(MassAssignmentException::class);

        $instance = $modelClass::first();
        expect($instance)->not->toBeNull();

        expect(function () use ($instance) {
            $instance->save();
        })->toThrow(BadMethodCallException::class);

        expect(function () use ($instance) {
            $instance->delete();
        })->toThrow(BadMethodCallException::class);
    }
});

test('AppRole reads from vAppRoles while AppRoleAssignment and RefAppRole use configured master tables and block writes for app_id other than 6', function () {
    expect((new AppRole)->getTable())->toBe('vAppRoles')
        ->and((new AppRoleAssignment)->getTable())->toBe('tb_master_nurse_supervision_approles')
        ->and((new RefAppRole)->getTable())->toBe('ref_master_nurse_supervision_approles');

    expect(function () {
        AppRoleAssignment::create([
            'nip' => '12345',
            'app_id' => 1,
            'app_roles' => 1,
        ]);
    })->toThrow(BadMethodCallException::class);

    expect(function () {
        RefAppRole::create([
            'role_id' => 2,
            'role_name' => 'WRONG APP ROLE',
            'app_id' => 5,
        ]);
    })->toThrow(BadMethodCallException::class);
});
