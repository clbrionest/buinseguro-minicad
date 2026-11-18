<?php

namespace App\Console\Commands;

use App\Services\VersionService;
use Illuminate\Console\Command;

class SyncVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:sync
                            {--bump= : Forzar incremento SemVer específico (major, minor, patch)}
                            {--title= : Título descriptivo para la versión generada}
                            {--dry-run : Mostrar versión resultante sin modificar archivos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza la versión SemVer y genera automáticamente el CHANGELOG.md en base a las últimas PR y commits';

    /**
     * Execute the console command.
     */
    public function handle(VersionService $versionService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $forcedBump = $this->option('bump');
        $customTitle = $this->option('title');

        $data = $versionService->getData(forceRefresh: true);

        if ($forcedBump && in_array(strtolower((string) $forcedBump), ['major', 'minor', 'patch'], true)) {
            $newVer = $versionService->bumpSemVer($data['version'], (string) $forcedBump);
            $data['version'] = $newVer;
            $data['released_at'] = date('Y-m-d');

            if (! empty($data['releases'][0])) {
                $data['releases'][0]['version'] = $newVer;
                $data['releases'][0]['type'] = strtolower((string) $forcedBump);
                if ($customTitle) {
                    $data['releases'][0]['title'] = (string) $customTitle;
                }
            }
        }

        if (! $dryRun) {
            $data = $versionService->syncWithGit(writeFiles: true);
            $this->info("Versión SemVer sincronizada exitosamente: v{$data['version']}");
            $this->line('Archivos actualizados: version.json, CHANGELOG.md, package.json');
        } else {
            $this->info("[Dry Run] Versión SemVer calculada: v{$data['version']}");
        }

        return self::SUCCESS;
    }
}
