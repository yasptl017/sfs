<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrationFoundation extends Command
{
    protected $signature = 'migration:foundation';

    protected $description = 'Step 5a: create financial years and one login per range + Division (random temporary passwords)';

    private const RANGES = [
        'Bayad', 'Bhiloda', 'Dhansura', 'Himatnagar', 'Idar', 'Khedbrahma', 'Malpur',
        'Meghraj', 'Modasa', 'Prantij', 'Talod', 'Vadali', 'Vijaynagar',
    ];

    private const TEST_USERNAMES = ['hmt', 'hmtdiv', 'hmtrg'];

    public function handle(): int
    {
        DB::transaction(function () {
            foreach ([['2025-26', '2025-04-01', '2026-03-31', false], ['2026-27', '2026-04-01', '2027-03-31', true]] as [$code, $from, $to, $cur]) {
                DB::table('financial_years')->updateOrInsert(
                    ['code' => $code],
                    ['starts_on' => $from, 'ends_on' => $to, 'is_current' => $cur, 'created_at' => now(), 'updated_at' => now()]
                );
            }

            // old test logins: deactivate (not delete) and rename so they can never be confused with real offices
            foreach (User::whereIn('username', self::TEST_USERNAMES)->get() as $u) {
                $u->forceFill(['is_active' => false, 'name' => 'TEST '.$u->username])->save();
            }

            $created = [];
            $division = $this->office('DIV', 'division', 'SF Sabarkantha Division', 'division', null, $created);
            foreach (self::RANGES as $range) {
                $this->office(strtoupper($range), strtolower($range), $range, 'range', $division->id, $created);
            }

            $this->report($created);
        });

        return self::SUCCESS;
    }

    /** @param array<int,array<string,string>> $created */
    private function office(string $code, string $username, string $name, string $role, ?int $divisionId, array &$created): User
    {
        $existing = User::where('office_code', $code)->first();
        if ($existing) {
            return $existing;
        }

        $password = $this->randomPassword();
        $user = new User([
            'name' => $name, 'email' => $username.'@sfs.local', 'username' => $username,
            'role' => $role, 'division_id' => $divisionId, 'password' => $password,
        ]);
        $user->forceFill(['office_code' => $code, 'is_active' => true])->save();
        $created[] = ['office' => $name, 'role' => $role, 'username' => $username, 'password' => $password];

        return $user;
    }

    private function randomPassword(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < 12; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }

    /** @param array<int,array<string,string>> $created */
    private function report(array $created): void
    {
        if ($created) {
            $dir = dirname(base_path()).DIRECTORY_SEPARATOR.'_backups'; // outside the git repo
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $file = $dir.DIRECTORY_SEPARATOR.'initial_logins_'.date('Ymd_His').'.csv';
            $fh = fopen($file, 'w');
            fputcsv($fh, ['office', 'role', 'username', 'temporary_password']);
            foreach ($created as $c) {
                fputcsv($fh, $c);
            }
            fclose($fh);
            $this->info('Created '.count($created)." logins. Temporary passwords saved to:\n  ".$file);
        } else {
            $this->info('All office logins already exist; nothing created.');
        }
        $this->table(['id', 'office_code', 'username', 'role', 'name', 'active'],
            User::orderBy('id')->get()->map(fn ($u) => [$u->id, $u->office_code, $u->username, $u->role, $u->name, $u->is_active ? 'yes' : 'no'])->all());
    }
}
