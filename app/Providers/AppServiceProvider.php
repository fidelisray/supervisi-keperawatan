<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Larang seluruh perintah destruktif di lingkungan Production
        DB::prohibitDestructiveCommands($this->app->isProduction());

        $this->loadMigrationsFrom(database_path('migrations/pgsql'));

        // 2. Pasang Anti-Disaster Safeguard multi-lapis untuk database SQL Server RSSES
        $this->registerDatabaseSafeguards();
    }

    /**
     * Mendaftarkan pengaman otomatis untuk melindungi database SQL Server RSSES dari perintah destruktif.
     */
    protected function registerDatabaseSafeguards(): void
    {
        // Lapisan 2: Console Guard terhadap perintah destruktif Artisan
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            $destructiveCommands = ['db:wipe', 'migrate:fresh', 'migrate:reset', 'migrate:refresh'];

            if (! in_array($event->command, $destructiveCommands, true)) {
                return;
            }

            $databaseOption = $event->input->hasOption('database') ? $event->input->getOption('database') : null;
            $defaultConnection = config('database.default');

            // Kondisi A: Secara eksplisit menargetkan koneksi sqlsrv -> HARD STOP
            if ($databaseOption === 'sqlsrv') {
                throw new \RuntimeException(
                    "\n\033[41;37m [CRITICAL SAFETY BLOCK] \033[0m\n".
                    "Perintah destruktif '{$event->command}' yang secara langsung menargetkan koneksi 'sqlsrv' (RSSES) ".
                    "DIBATALKAN MUTLAK untuk mencegah terhapusnya database Rumah Sakit!\n"
                );
            }

            // Kondisi B: Default connection di .env diubah menjadi sqlsrv -> HARD STOP
            if (empty($databaseOption) && $defaultConnection === 'sqlsrv') {
                throw new \RuntimeException(
                    "\n\033[41;37m [CRITICAL SAFETY BLOCK] \033[0m\n".
                    "Koneksi default saat ini diarahkan ke 'sqlsrv' (RSSES). ".
                    "Perintah destruktif '{$event->command}' DIBATALKAN MUTLAK!\n"
                );
            }

            // Kondisi C: Default pgsql (Safe Fallback) -> Tampilkan banner pengaman
            if (! $this->app->runningUnitTests()) {
                $event->output->writeln(
                    "<fg=yellow;options=bold>[SAFEGUARD]</> Database SQL Server SIMRS ('sqlsrv' / RSSES) diproteksi penuh dan dilewati dari proses wipe/fresh."
                );
            }
        });

        // Lapisan 3: Query Interception Guard pada koneksi sqlsrv
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            if ($event->connectionName !== 'sqlsrv' || $event->connection->getDriverName() !== 'sqlsrv') {
                return;
            }

            $event->connection->beforeExecuting(function ($query) {
                $trimmed = strtolower(trim((string) $query));

                // Blokir perintah wipe bawaan Laravel untuk SQL Server (sp_msforeachtable)
                if (str_contains($trimmed, 'sp_msforeachtable') && str_contains($trimmed, 'drop table')) {
                    throw new \RuntimeException(
                        "[CRITICAL SAFETY BLOCK] Eksekusi 'sp_msforeachtable DROP TABLE' pada database RSSES diblokir permanen oleh sistem pengaman!"
                    );
                }
            });
        });
    }
}
