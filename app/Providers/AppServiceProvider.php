<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
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

        $this->configureTrustedProxies();
        $this->configureRequestSchemeResolution();

        $this->loadMigrationsFrom(database_path('migrations/pgsql'));

        // 2. Pasang Anti-Disaster Safeguard multi-lapis untuk database SQL Server RSSES
        $this->registerDatabaseSafeguards();

        // 3. Daftarkan template pagination kustom
        Paginator::defaultView('vendor.pagination.custom');
    }

    /**
     * Mengonfigurasi daftar reverse proxy tepercaya dari config agar kompatibel dengan config:cache.
     */
    public function configureTrustedProxies(): void
    {
        $trustedProxies = config('supervisi.trusted_proxies');
        if (! is_string($trustedProxies) || trim($trustedProxies) === '') {
            return;
        }

        $trimmed = trim($trustedProxies);
        $proxies = ($trimmed === '*' || $trimmed === '**')
            ? $trimmed
            : array_values(array_filter(array_map('trim', explode(',', $trimmed))));

        if ($proxies === []) {
            return;
        }

        TrustProxies::at($proxies);
        TrustProxies::withHeaders(
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_PREFIX
        );
    }

    /**
     * Menyinkronkan status HTTPS pada Request dan UrlGenerator setelah TrustProxies berjalan.
     */
    public function configureRequestSchemeResolution(): void
    {
        $this->app->rebinding('request', function ($app, Request $request): void {
            $this->synchronizeRequestScheme($request);
        });

        if ($this->app->bound('request') && $this->app['request'] instanceof Request) {
            $this->synchronizeRequestScheme($this->app['request']);
        }
    }

    /**
     * Menentukan apakah request harus diperlakukan sebagai HTTPS (via header proxy atau Host-Aware APP_URL).
     */
    public function synchronizeRequestScheme(Request $request): void
    {
        $forwardedScheme = strtolower(trim((string) $request->headers->get('X-Forwarded-Scheme', '')));
        if ($request->isFromTrustedProxy() && in_array($forwardedScheme, ['https', 'on', 'ssl', '1'], true)) {
            $request->server->set('HTTPS', 'on');
        }

        $shouldForceHttps = $request->isSecure();

        if (! $shouldForceHttps && (bool) config('supervisi.force_https', false)) {
            $requestHost = strtolower($request->getHost());
            $appUrl = (string) config('app.url', '');
            $appUrlScheme = strtolower((string) parse_url($appUrl, PHP_URL_SCHEME));
            $appUrlHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));

            $isConfiguredHttpsHost = $appUrlScheme === 'https' && $appUrlHost !== '' && $requestHost === $appUrlHost;
            $isNonLocalDomainHost = $requestHost !== ''
                && $requestHost !== 'localhost'
                && filter_var($requestHost, FILTER_VALIDATE_IP) === false;

            if ($isConfiguredHttpsHost || $isNonLocalDomainHost) {
                $request->server->set('HTTPS', 'on');
                $shouldForceHttps = true;
            }
        }

        URL::forceScheme($shouldForceHttps ? 'https' : null);

        if ($this->app->resolved('url')) {
            $this->app['url']->setRequest($request);
        }
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
