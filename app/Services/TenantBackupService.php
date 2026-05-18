<?php

namespace App\Services;

use App\Models\TenantBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;
use ZipArchive;

class TenantBackupService
{
    public function process(TenantBackup $backup): TenantBackup
    {
        $backup->update([
            'status' => TenantBackup::STATUS_RUNNING,
            'started_at' => $backup->started_at ?? now(),
            'error_message' => null,
        ]);

        try {
            $archivePath = $this->buildArchive($backup);

            $backup->update([
                'status' => TenantBackup::STATUS_COMPLETED,
                'disk' => 'local',
                'path' => $this->relativePath($archivePath),
                'size_bytes' => File::size($archivePath),
                'completed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $backup->update([
                'status' => TenantBackup::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $backup->refresh();
    }

    private function buildArchive(TenantBackup $backup): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension is required to create tenant backups.');
        }

        $root = storage_path('app/tenant-backups');
        $workDir = $root . DIRECTORY_SEPARATOR . 'work-' . $backup->id;
        $archivePath = $root . DIRECTORY_SEPARATOR . $backup->tenant_id . '-' . now()->format('YmdHis') . '-' . $backup->id . '.zip';

        File::ensureDirectoryExists($workDir);
        File::ensureDirectoryExists(dirname($archivePath));

        $this->writeManifest($backup, $workDir);

        if (in_array($backup->backup_type, ['full', 'database'], true)) {
            $this->exportTenantTables($backup->tenant_id, $workDir);
        }

        if (in_array($backup->backup_type, ['full', 'files'], true)) {
            $this->writeFileManifest($backup->tenant_id, $workDir);
        }

        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create tenant backup archive.');
        }

        foreach (File::allFiles($workDir) as $file) {
            $zip->addFile($file->getPathname(), $file->getRelativePathname());
        }

        $zip->close();
        File::deleteDirectory($workDir);

        return $archivePath;
    }

    private function writeManifest(TenantBackup $backup, string $workDir): void
    {
        File::put($workDir . DIRECTORY_SEPARATOR . 'manifest.json', json_encode([
            'tenant_id' => $backup->tenant_id,
            'backup_id' => $backup->id,
            'backup_type' => $backup->backup_type,
            'created_at' => now()->toISOString(),
            'format' => 'central-tenant-json-v1',
        ], JSON_PRETTY_PRINT));
    }

    private function exportTenantTables(string $tenantId, string $workDir): void
    {
        $databaseDir = $workDir . DIRECTORY_SEPARATOR . 'database';
        File::ensureDirectoryExists($databaseDir);

        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn($row) => array_values((array) $row)[0])
            ->filter(fn($table) => $this->shouldExportTable((string) $table))
            ->values();

        foreach ($tables as $table) {
            $query = DB::table($table);
            $columns = Schema::getColumnListing($table);

            if (in_array('tenant_id', $columns, true)) {
                $query->where('tenant_id', $tenantId);
            } elseif ($table === 'tenants') {
                $query->where('id', $tenantId);
            } else {
                continue;
            }

            $rows = $query->get();
            File::put(
                $databaseDir . DIRECTORY_SEPARATOR . $table . '.json',
                json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }
    }

    private function shouldExportTable(string $table): bool
    {
        return !str_starts_with($table, 'migrations')
            && !str_starts_with($table, 'password_reset')
            && !str_starts_with($table, 'personal_access_tokens');
    }

    private function writeFileManifest(string $tenantId, string $workDir): void
    {
        $uploadsPath = public_path('uploads');
        $files = [];

        if (File::isDirectory($uploadsPath)) {
            foreach (File::allFiles($uploadsPath) as $file) {
                $path = $file->getRelativePathname();
                if (str_contains($path, $tenantId)) {
                    $files[] = [
                        'path' => $path,
                        'size_bytes' => $file->getSize(),
                        'modified_at' => date(DATE_ATOM, $file->getMTime()),
                    ];
                }
            }
        }

        File::put(
            $workDir . DIRECTORY_SEPARATOR . 'files-manifest.json',
            json_encode($files, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function relativePath(string $path): string
    {
        return str_replace(storage_path('app') . DIRECTORY_SEPARATOR, '', $path);
    }
}
