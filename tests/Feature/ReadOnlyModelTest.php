<?php

use App\Models\AppRole;
use App\Models\Employee;
use App\Models\GlobalAccount;
use App\Models\RefAppRole;
use App\Models\Room;
use Illuminate\Database\Eloquent\MassAssignmentException;

test('employee model throws exception when attempting to create, update, or delete', function () {
    expect(function () {
        Employee::create([
            'nip' => '9999999999',
            'nama' => 'User Palsu',
        ]);
    })->toThrow(MassAssignmentException::class);

    $employee = Employee::first();
    expect($employee)->not->toBeNull();

    expect(function () use ($employee) {
        $employee->nama = 'Diubah';
        $employee->save();
    })->toThrow(BadMethodCallException::class);

    expect(function () use ($employee) {
        $employee->delete();
    })->toThrow(BadMethodCallException::class);
});

test('room model throws exception when attempting to create, update, or delete', function () {
    expect(function () {
        Room::create([
            'id_ruang' => 'TEST',
            'nama_ruangan' => 'Ruang Palsu',
        ]);
    })->toThrow(MassAssignmentException::class);

    $room = Room::first();
    expect($room)->not->toBeNull();

    expect(function () use ($room) {
        $room->nama_ruangan = 'Diubah';
        $room->save();
    })->toThrow(BadMethodCallException::class);

    expect(function () use ($room) {
        $room->delete();
    })->toThrow(BadMethodCallException::class);
});

test('sso models (AppRole, GlobalAccount, RefAppRole) block all write and delete operations', function () {
    foreach ([AppRole::class, GlobalAccount::class, RefAppRole::class] as $modelClass) {
        expect(function () use ($modelClass) {
            $modelClass::create(['nip' => '0000']);
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
