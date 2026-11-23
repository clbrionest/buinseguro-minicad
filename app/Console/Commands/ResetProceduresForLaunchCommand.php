<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Evidence;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ResetProceduresForLaunchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'minicad:reset-procedures-for-launch
                            {--confirm-reset : Confirmar explícitamente el reinicio único de procedimientos}
                            {--dry-run : Simular sin modificar la base de datos ni crear el bloqueo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deja en 0 únicamente los procedimientos (y sus bitácoras/evidencias) por una única vez para el lanzamiento a producción, conservando todos los mantenedores intactos.';

    /**
     * Ruta del archivo de bloqueo de uso único.
     */
    public static function lockFilePath(): string
    {
        return storage_path('app/procedures_launch_reset.lock');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // 1. Verificar bloqueo de uso único (en base de datos y en archivo de bloqueo)
        $alreadyExecutedInAudit = AuditLog::where('action', 'system.procedures_launch_reset')->exists();
        $alreadyExecutedLockFile = File::exists(self::lockFilePath());

        if ($alreadyExecutedInAudit || $alreadyExecutedLockFile) {
            $this->error('BLOQUEADO: El reinicio de procedimientos para lanzamiento a producción ya fue ejecutado anteriormente (uso único).');

            return self::FAILURE;
        }

        // 2. Contabilizar los registros que serán afectados
        $evidenceTable = (new Evidence)->getTable();
        $hasEvidenceTable = Schema::hasTable($evidenceTable);

        $proceduresCount = DB::table('procedures')->count();
        $logsCount = DB::table('procedure_logs')->count();
        $evidencesCount = $hasEvidenceTable ? DB::table($evidenceTable)->count() : 0;
        $linkedCallsCount = Schema::hasTable('calls')
            ? DB::table('calls')->whereNotNull('procedure_id')->count()
            : 0;

        $this->info('Resumen del reinicio único de lanzamiento a producción:');
        $this->line(" - Procedimientos a eliminar: {$proceduresCount}");
        $this->line(" - Bitácoras de procedimientos (procedure_logs) a eliminar: {$logsCount}");
        $this->line(" - Registros de evidencias ({$evidenceTable}) a eliminar: {$evidencesCount}");
        $this->line(" - Llamadas a desvincular (calls.procedure_id -> NULL, conservando historial): {$linkedCallsCount}");
        $this->line(' - Mantenedores conservados intactos: usuarios, roles, sectores, móviles, hitos, orígenes, tipos, catálogos, turnos y patrullajes.');

        if ($dryRun) {
            $this->warn('[DRY RUN] No se realizaron modificaciones en la base de datos.');

            return self::SUCCESS;
        }

        // 3. Requerir confirmación explícita
        if (! $this->option('confirm-reset')) {
            if (! $this->confirm('¿Confirmas ejecutar este reinicio ÚNICO para dejar los procedimientos en 0? Esta acción no se podrá repetir.')) {
                $this->warn('Operación cancelada por el usuario.');

                return self::FAILURE;
            }
        }

        // 4. Ejecutar limpieza transaccional de SOLO procedimientos y sus tablas hijas directas
        DB::transaction(function () use ($evidenceTable, $hasEvidenceTable) {
            if (Schema::hasTable('calls') && Schema::hasColumn('calls', 'procedure_id')) {
                DB::table('calls')->whereNotNull('procedure_id')->update(['procedure_id' => null]);
            }

            if (Schema::hasColumn('procedures', 'parent_id')) {
                DB::table('procedures')->whereNotNull('parent_id')->update(['parent_id' => null]);
            }

            DB::table('procedure_logs')->delete();

            if ($hasEvidenceTable) {
                DB::table($evidenceTable)->delete();
            }

            DB::table('procedures')->delete();
        });

        // 5. Reiniciar secuencias autoincrementales para comenzar en Folio #1
        $this->resetAutoIncrement('procedure_logs');
        if ($hasEvidenceTable) {
            $this->resetAutoIncrement($evidenceTable);
        }
        $this->resetAutoIncrement('procedures');

        // 6. Registrar auditoría y bloquear futuras ejecuciones
        AuditLog::record(
            'system.procedures_launch_reset',
            'Sistema',
            "Reinicio único de lanzamiento ejecutado: {$proceduresCount} procedimientos, {$logsCount} bitácoras y {$evidencesCount} evidencias eliminados; {$linkedCallsCount} llamadas desvinculadas. Mantenedores conservados intactos.",
            null,
            [
                'procedures_deleted' => $proceduresCount,
                'logs_deleted' => $logsCount,
                'evidences_deleted' => $evidencesCount,
                'calls_unlinked' => $linkedCallsCount,
            ]
        );

        File::ensureDirectoryExists(dirname(self::lockFilePath()));
        File::put(self::lockFilePath(), json_encode([
            'executed_at' => now()->toIso8601String(),
            'procedures_deleted' => $proceduresCount,
            'logs_deleted' => $logsCount,
            'evidences_deleted' => $evidencesCount,
            'calls_unlinked' => $linkedCallsCount,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");

        $this->info('Reinicio completado exitosamente. El sistema quedó en 0 procedimientos (próximo Folio #1) y el comando ha sido bloqueado para uso único.');

        return self::SUCCESS;
    }

    protected function resetAutoIncrement(string $table): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
        } elseif ($driver === 'sqlite') {
            $hasSeq = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='sqlite_sequence'");
            if (! empty($hasSeq)) {
                DB::table('sqlite_sequence')->where('name', $table)->delete();
            }
        }
    }
}
