<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class VersionService
{
    protected ?array $resolvedData = null;

    /**
     * Obtiene la estructura completa de versión y releases (incluyendo detección dinámica desde Git).
     *
     * @return array{version: string, released_at: string, branch: string, last_commit: string, releases: array<int, array<string, mixed>>}
     */
    public function getData(bool $forceRefresh = false): array
    {
        if ($this->resolvedData !== null && ! $forceRefresh) {
            return $this->resolvedData;
        }

        $baseData = $this->readVersionFile();
        $this->resolvedData = $this->applyPendingGitChanges($baseData);

        return $this->resolvedData;
    }

    /**
     * Devuelve el número de versión actual bajo formato SemVer (MAJOR.MINOR.PATCH).
     */
    public function getVersion(): string
    {
        return $this->getData()['version'] ?? '1.0.0';
    }

    /**
     * Devuelve la etiqueta de versión con prefijo 'v' (ej: v1.3.0).
     */
    public function getVersionTag(): string
    {
        return 'v'.ltrim($this->getVersion(), 'v');
    }

    /**
     * Devuelve la información de la versión más reciente.
     *
     * @return array<string, mixed>
     */
    public function getLatestRelease(): array
    {
        $releases = $this->getData()['releases'] ?? [];

        return $releases[0] ?? [
            'version' => $this->getVersion(),
            'date' => date('Y-m-d'),
            'type' => 'minor',
            'title' => 'Versión Operativa Actual',
            'pr_refs' => [],
            'changes' => [],
        ];
    }

    /**
     * Devuelve el listado histórico de versiones.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getReleases(int $limit = 10): array
    {
        $releases = $this->getData()['releases'] ?? [];

        return array_slice($releases, 0, $limit);
    }

    /**
     * Devuelve un resumen compacto de los últimos cambios para mostrar en Login y Menú de Perfil.
     *
     * @return array<int, array{version: string, date: string, type: string, scope: string, description: string}>
     */
    public function getRecentChangesSummary(int $limit = 5): array
    {
        $summary = [];
        foreach ($this->getReleases(4) as $release) {
            foreach ($release['changes'] ?? [] as $change) {
                $summary[] = [
                    'version' => $release['version'],
                    'date' => $release['date'],
                    'type' => $change['type'] ?? 'feat',
                    'scope' => $change['scope'] ?? 'general',
                    'description' => $change['description'] ?? '',
                ];

                if (count($summary) >= $limit) {
                    return $summary;
                }
            }
        }

        return $summary;
    }

    /**
     * Descompone una versión SemVer en sus componentes major, minor y patch.
     *
     * @return array{major: int, minor: int, patch: int}
     */
    public function parseSemVer(string $version): array
    {
        $clean = ltrim(trim($version), 'vV');
        if (preg_match('/^(\d+)\.(\d+)\.(\d+)/', $clean, $matches)) {
            return [
                'major' => (int) $matches[1],
                'minor' => (int) $matches[2],
                'patch' => (int) $matches[3],
            ];
        }

        return ['major' => 1, 'minor' => 0, 'patch' => 0];
    }

    /**
     * Incrementa una versión siguiendo las reglas de Semantic Versioning 2.0.0.
     */
    public function bumpSemVer(string $currentVersion, string $bumpType): string
    {
        $parts = $this->parseSemVer($currentVersion);

        return match (strtolower($bumpType)) {
            'major' => ($parts['major'] + 1).'.0.0',
            'minor' => $parts['major'].'.'.($parts['minor'] + 1).'.0',
            default => $parts['major'].'.'.$parts['minor'].'.'.($parts['patch'] + 1),
        };
    }

    /**
     * Determina el tipo de salto SemVer ('major', 'minor', 'patch') a partir de una lista de cambios.
     *
     * @param  array<int, array<string, mixed>>  $changes
     */
    public function determineBumpType(array $changes): string
    {
        $hasMinor = false;

        foreach ($changes as $change) {
            if (! empty($change['breaking'])) {
                return 'major';
            }

            if (($change['type'] ?? '') === 'feat') {
                $hasMinor = true;
            }
        }

        return $hasMinor ? 'minor' : 'patch';
    }

    /**
     * Analiza una línea de commit o merge de PR y la transforma en un ítem estructurado de Changelog.
     *
     * @return array{type: string, scope: string, description: string, breaking: bool, pr_ref: ?string, date: string, hash: string}|null
     */
    public function parseCommitEntry(string $hash, string $date, string $subject, string $body = ''): ?array
    {
        $subject = trim($subject);
        $body = trim($body);

        if ($subject === '' || str_starts_with(strtolower($subject), 'chore(release):')) {
            return null;
        }

        $breaking = str_contains($body, 'BREAKING CHANGE') || str_contains($subject, '!:');

        // 1. Detectar Merge de Pull Request: "Merge pull request #7 from user/type/slug"
        if (preg_match('/^Merge pull request #(\d+) from [^\s\/]+\/([^\s]+)/i', $subject, $matches)) {
            $prNumber = $matches[1];
            $branchName = $matches[2];
            $branchType = 'chore';
            $scope = $branchName;

            if (str_contains($branchName, '/')) {
                [$prefix, $scope] = explode('/', $branchName, 2);
                $branchType = $this->normalizeChangeType($prefix);
            }

            $desc = $body !== '' ? strtok($body, "\n") : "Integración de rama {$branchName}";

            return [
                'type' => $branchType,
                'scope' => $scope,
                'description' => "PR #{$prNumber} ({$branchName}): {$desc}",
                'breaking' => $breaking,
                'pr_ref' => "PR #{$prNumber}",
                'date' => $date,
                'hash' => $hash,
            ];
        }

        // 2. Detectar Conventional Commits: "feat(scope): description" o "fix: description"
        if (preg_match('/^(feat|fix|perf|refactor|chore|ci|docs|style|test)(?:\(([^)]+)\))?(!)?:\s*(.+)$/i', $subject, $matches)) {
            $type = $this->normalizeChangeType($matches[1]);
            $scope = ! empty($matches[2]) ? strtolower($matches[2]) : $type;
            $isBang = ! empty($matches[3]);
            $desc = ucfirst(trim($matches[4]));

            return [
                'type' => $type,
                'scope' => $scope,
                'description' => $desc,
                'breaking' => $breaking || $isBang,
                'pr_ref' => null,
                'date' => $date,
                'hash' => $hash,
            ];
        }

        // 3. Commits descriptivos libres
        $lower = mb_strtolower($subject);
        $inferredType = 'chore';
        if (str_contains($lower, 'feat') || str_contains($lower, 'agrega') || str_contains($lower, 'implementa') || str_contains($lower, 'patrullaje') || str_contains($lower, 'mapa')) {
            $inferredType = 'feat';
        } elseif (str_contains($lower, 'fix') || str_contains($lower, 'corrig') || str_contains($lower, 'correccion')) {
            $inferredType = 'fix';
        }

        return [
            'type' => $inferredType,
            'scope' => 'sistema',
            'description' => ucfirst($subject),
            'breaking' => $breaking,
            'pr_ref' => null,
            'date' => $date,
            'hash' => $hash,
        ];
    }

    /**
     * Sincroniza los cambios pendientes desde Git y opcionalmente persiste version.json y CHANGELOG.md.
     *
     * @return array<string, mixed>
     */
    public function syncWithGit(bool $writeFiles = true): array
    {
        $data = $this->getData(forceRefresh: true);

        if ($writeFiles) {
            File::put(
                base_path('version.json'),
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
            );

            File::put(
                base_path('CHANGELOG.md'),
                $this->generateChangelogMarkdown($data)
            );

            $this->updatePackageJsonVersion($data['version']);
        }

        return $data;
    }

    /**
     * Genera el contenido Markdown para CHANGELOG.md basado en Keep a Changelog y SemVer.
     *
     * @param  array<string, mixed>|null  $data
     */
    public function generateChangelogMarkdown(?array $data = null): string
    {
        $data = $data ?? $this->getData();
        $lines = [
            '# Changelog — Buin Seguro MiniCAD',
            '',
            'Todos los cambios relevantes de este proyecto se documentan automáticamente en este archivo.',
            'El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y el proyecto adhiere a [Semantic Versioning (SemVer 2.0.0)](https://semver.org/lang/es/).',
            '',
        ];

        $typeLabels = [
            'feat' => '### ✨ Nuevas Funcionalidades (Features)',
            'fix' => '### 🐛 Correcciones (Bug Fixes)',
            'ci' => '### ⚙️ Integración Continua (CI/CD)',
            'chore' => '### 🔧 Mantenimiento y Arquitectura (Chores)',
        ];

        foreach ($data['releases'] ?? [] as $release) {
            $ver = $release['version'];
            $date = $release['date'];
            $title = $release['title'] ?? '';
            $refs = ! empty($release['pr_refs']) ? ' — `'.implode('`, `', $release['pr_refs']).'`' : '';

            $lines[] = "## [v{$ver}] - {$date}{$refs}";
            if ($title !== '') {
                $lines[] = "**{$title}**";
            }
            $lines[] = '';

            $grouped = [];
            foreach ($release['changes'] ?? [] as $change) {
                $t = $change['type'] ?? 'chore';
                if (! isset($typeLabels[$t])) {
                    $t = 'chore';
                }
                $grouped[$t][] = $change;
            }

            foreach ($typeLabels as $typeKey => $heading) {
                if (! empty($grouped[$typeKey])) {
                    $lines[] = $heading;
                    foreach ($grouped[$typeKey] as $item) {
                        $scope = ! empty($item['scope']) ? "**[{$item['scope']}]** " : '';
                        $lines[] = "- {$scope}{$item['description']}";
                    }
                    $lines[] = '';
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Lee el archivo base version.json del proyecto.
     *
     * @return array<string, mixed>
     */
    protected function readVersionFile(): array
    {
        $path = base_path('version.json');
        if (File::exists($path)) {
            $decoded = json_decode(File::get($path), true);
            if (is_array($decoded) && isset($decoded['version'])) {
                return $decoded;
            }
        }

        return [
            'version' => '1.0.0',
            'released_at' => date('Y-m-d'),
            'branch' => 'main',
            'last_commit' => '',
            'releases' => [],
        ];
    }

    /**
     * Detecta nuevos commits/PRs en Git posteriores a last_commit y actualiza dinámicamente la versión SemVer.
     *
     * @param  array<string, mixed>  $baseData
     * @return array<string, mixed>
     */
    protected function applyPendingGitChanges(array $baseData): array
    {
        if (! File::isDirectory(base_path('.git'))) {
            return $baseData;
        }

        $lastCommit = trim((string) ($baseData['last_commit'] ?? ''));
        if ($lastCommit === '') {
            return $baseData;
        }

        try {
            $headResult = Process::path(base_path())->run(['git', 'rev-parse', '--short', 'HEAD']);
            if (! $headResult->successful()) {
                return $baseData;
            }

            $currentHead = trim($headResult->output());
            if ($currentHead === '' || $currentHead === $lastCommit) {
                return $baseData;
            }

            // Verificar si last_commit existe en la historia actual
            $verifyResult = Process::path(base_path())->run(['git', 'cat-file', '-t', $lastCommit]);
            if (! $verifyResult->successful()) {
                return $baseData;
            }

            $logResult = Process::path(base_path())->run([
                'git',
                'log',
                "{$lastCommit}..HEAD",
                '--pretty=format:%h|%ad|%s|%b<<END>>',
                '--date=short',
            ]);

            if (! $logResult->successful() || trim($logResult->output()) === '') {
                return $baseData;
            }

            $rawBlocks = explode('<<END>>', $logResult->output());
            $parsedChanges = [];
            $prRefs = [];
            $latestDate = date('Y-m-d');

            foreach ($rawBlocks as $block) {
                $block = trim($block);
                if ($block === '') {
                    continue;
                }

                $parts = explode('|', $block, 4);
                if (count($parts) < 3) {
                    continue;
                }

                [$hash, $date, $subject] = $parts;
                $body = $parts[3] ?? '';

                $entry = $this->parseCommitEntry($hash, $date, $subject, $body);
                if ($entry !== null) {
                    $parsedChanges[] = [
                        'type' => $entry['type'],
                        'scope' => $entry['scope'],
                        'description' => $entry['description'],
                        'breaking' => $entry['breaking'],
                    ];
                    if (! empty($entry['pr_ref'])) {
                        $prRefs[] = $entry['pr_ref'];
                    }
                    if (! empty($entry['date'])) {
                        $latestDate = $entry['date'];
                    }
                }
            }

            if (empty($parsedChanges)) {
                return $baseData;
            }

            $bumpType = $this->determineBumpType($parsedChanges);
            $newVersion = $this->bumpSemVer($baseData['version'], $bumpType);

            $cleanChanges = array_map(fn (array $c) => [
                'type' => $c['type'],
                'scope' => $c['scope'],
                'description' => $c['description'],
            ], $parsedChanges);

            $dynamicRelease = [
                'version' => $newVersion,
                'date' => $latestDate,
                'type' => $bumpType,
                'title' => 'Actualización Automática desde rama principal',
                'pr_refs' => array_values(array_unique($prRefs)),
                'changes' => $cleanChanges,
            ];

            $baseData['version'] = $newVersion;
            $baseData['released_at'] = $latestDate;
            $baseData['last_commit'] = $currentHead;
            array_unshift($baseData['releases'], $dynamicRelease);
        } catch (\Throwable) {
            return $baseData;
        }

        return $baseData;
    }

    /**
     * Normaliza el prefijo de commit/rama a una categoría estándar.
     */
    protected function normalizeChangeType(string $rawType): string
    {
        return match (strtolower(trim($rawType))) {
            'feat', 'feature' => 'feat',
            'fix', 'bugfix', 'hotfix' => 'fix',
            'ci', 'build' => 'ci',
            default => 'chore',
        };
    }

    /**
     * Sincroniza la propiedad "version" en package.json si el archivo existe.
     */
    protected function updatePackageJsonVersion(string $version): void
    {
        $pkgPath = base_path('package.json');
        if (! File::exists($pkgPath)) {
            return;
        }

        $pkg = json_decode(File::get($pkgPath), true);
        if (is_array($pkg)) {
            $pkg['version'] = $version;
            File::put(
                $pkgPath,
                json_encode($pkg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
            );
        }
    }
}
