<?php

use App\Models\AppRole;
use App\Models\Employee;
use App\Models\GlobalAccount;
use App\Models\RefAppRole;
use App\Models\Room;
use Illuminate\Database\Eloquent\MassAssignmentException;

test('employee, room, and global account models throw exception when attempting to create, update, or delete', function () {
    foreach ([Employee::class, Room::class, GlobalAccount::class] as $modelClass) {
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

test('AppRole and RefAppRole block delete operations and block writes for app_id other than 6', function () {
    foreach ([AppRole::class, RefAppRole::class] as $modelClass) {
        $instance = $modelClass::where('app_id', 6)->first();
        expect($instance)->not->toBeNull();

        expect(function () use ($instance) {
            $instance->delete();
        })->toThrow(BadMethodCallException::class);
    }

    expect(function () {
        AppRole::create([
            'nip' => '12345',
            'app_id' => 1,
            'app_roles' => 1,
            'description' => 'Wrong App',
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
