<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->swapRoleCheckConstraint("check (\"role\" in ('admin', 'manager', 'customer'))");
    }

    public function down(): void
    {
        $this->swapRoleCheckConstraint("check (\"role\" in ('admin', 'customer'))");
    }

    /**
     * Postgres rejects Schema::table()->enum()->change() — Laravel emits an inline
     * "alter column ... type varchar(255) check (...)" clause, which is invalid Postgres
     * syntax (a CHECK constraint can't ride inside ALTER COLUMN TYPE; it needs its own
     * ADD CONSTRAINT). Drop the existing check constraint and add the new one directly.
     */
    private function swapRoleCheckConstraint(string $checkClause): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $constraint = DB::selectOne(<<<'SQL'
            select conname from pg_constraint
            where conrelid = 'users'::regclass
              and contype = 'c'
              and pg_get_constraintdef(oid) like '%role%'
            SQL);

        if ($constraint) {
            DB::statement('alter table "users" drop constraint "'.$constraint->conname.'"');
        }

        DB::statement('alter table "users" add constraint "users_role_check" '.$checkClause);
    }
};
