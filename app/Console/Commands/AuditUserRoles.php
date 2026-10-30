<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditUserRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:audit-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit database users for out-of-domain or invalid role values';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $validRoles = array_map(fn (UserRole $role) => $role->value, UserRole::cases());

        $users = DB::table('users')->select(['id', 'name', 'email', 'role'])->get();

        $invalidCount = 0;
        $headers = ['ID', 'Name', 'Email', 'Raw Role Value'];
        $rows = [];

        foreach ($users as $user) {
            if ($user->role === null || ! in_array($user->role, $validRoles, true)) {
                $invalidCount++;
                $rows[] = [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role ?? 'NULL',
                ];
            }
        }

        if ($invalidCount === 0) {
            $this->info('Audit clean: 0 users carry invalid or out-of-domain role values.');
            return Command::SUCCESS;
        }

        $this->warn("Audit found {$invalidCount} user(s) with invalid role values:");
        $this->table($headers, $rows);

        return Command::FAILURE;
    }
}
