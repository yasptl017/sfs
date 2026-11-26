<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StageSheets extends Command
{
    protected $signature = 'sheets:stage {--validate-only : Skip loading, only re-verify what is in the database} {--folders : Use the export of the three 25-26 sub-folders (staging_folders)}';

    protected $description = 'Load exported spreadsheet rows into staging tables and verify them by reading them back';

    public function handle(): int
    {
        $dir = storage_path('app/private/migration/'.($this->option('folders') ? 'staging_folders' : 'staging'));
        $manifestPath = $dir.'/manifest.json';
        if (! is_file($manifestPath)) {
            $this->error('manifest.json not found. Run database/tools/export_sheets.py first.');

            return self::FAILURE;
        }
        $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        if (! $this->option('validate-only')) {
            foreach ($manifest as $m) {
                $this->load($dir, $m);
            }
        }

        return $this->validate($manifest) ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<string,mixed> $m */
    private function load(string $dir, array $m): void
    {
        $existing = DB::table('staging_sheets')
            ->where('source_file', $m['source_file'])->where('sheet_name', $m['sheet_name'])->first();
        if ($existing) {
            // idempotent: re-load this sheet from scratch
            DB::table('staging_sheets')->where('id', $existing->id)->delete();
        }

        DB::transaction(function () use ($dir, $m) {
            $sheetId = DB::table('staging_sheets')->insertGetId([
                'source_file' => $m['source_file'],
                'source_sha256' => $m['source_sha256'],
                'sheet_order' => $m['sheet_order'],
                'sheet_name' => $m['sheet_name'],
                'sheet_state' => $m['sheet_state'],
                'expected_rows' => $m['rows'],
                'expected_cells' => $m['cells'],
                'max_col' => $m['max_col'],
                'expected_rows_sha256' => $m['rows_sha256'],
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $fh = fopen($dir.'/'.$m['file'], 'rb');
            $batch = [];
            $bytes = 0;
            while (($line = fgets($fh)) !== false) {
                $line = rtrim($line, "\n");
                if ($line === '') {
                    continue;
                }
                // line = {"r":N,"c":[...]} -> keep the exact text of the cells array
                $pos = strpos($line, ',"c":');
                $rowNo = (int) substr($line, 5, $pos - 5);
                $cells = substr($line, $pos + 5, -1);
                $batch[] = ['staging_sheet_id' => $sheetId, 'row_no' => $rowNo, 'cells' => $cells];
                $bytes += strlen($cells);
                // stay well under MariaDB's default 1 MB max_allowed_packet
                if (count($batch) >= 500 || $bytes > 400_000) {
                    DB::table('staging_rows')->insert($batch);
                    $batch = [];
                    $bytes = 0;
                }
            }
            if ($batch) {
                DB::table('staging_rows')->insert($batch);
            }
            fclose($fh);
        });

        $this->line(sprintf('loaded  %-45s %-32s rows=%d', $m['source_file'], $m['sheet_name'], $m['rows']));
    }

    /** @param array<int,array<string,mixed>> $manifest */
    private function validate(array $manifest): bool
    {
        $this->newLine();
        $this->info('Validating (read back from MySQL, recompute SHA-256)...');
        $ok = true;
        $totRows = 0;
        $rows = [];

        foreach ($manifest as $m) {
            $sheet = DB::table('staging_sheets')
                ->where('source_file', $m['source_file'])->where('sheet_name', $m['sheet_name'])->first();
            if (! $sheet) {
                $rows[] = [$m['source_file'], $m['sheet_name'], $m['rows'], 'MISSING', 'FAIL'];
                $ok = false;

                continue;
            }
            $h = hash_init('sha256');
            $count = 0;
            foreach (DB::table('staging_rows')->where('staging_sheet_id', $sheet->id)->orderBy('row_no')
                ->select('row_no', 'cells')->cursor() as $r) {
                hash_update($h, $r->row_no."\t".$r->cells."\n");
                $count++;
            }
            $sha = hash_final($h);
            $pass = $count === (int) $m['rows'] && $sha === $m['rows_sha256'];
            DB::table('staging_sheets')->where('id', $sheet->id)->update([
                'loaded_rows' => $count, 'loaded_rows_sha256' => $sha,
                'validated_at' => $pass ? now() : null, 'updated_at' => now(),
            ]);
            $ok = $ok && $pass;
            $totRows += $count;
            $rows[] = [mb_strimwidth($m['source_file'], 0, 30, '..'), $m['sheet_name'], $m['rows'], $count, $pass ? 'OK' : 'FAIL'];
        }

        $this->table(['Workbook', 'Sheet', 'Expected rows', 'In DB', 'Hash'], $rows);
        $this->line("Total rows verified: $totRows across ".count($manifest).' sheets');
        $ok ? $this->info('ALL SHEETS VERIFIED') : $this->error('VALIDATION FAILED');

        return $ok;
    }
}
