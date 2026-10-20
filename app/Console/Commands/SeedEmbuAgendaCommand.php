<?php

namespace App\Console\Commands;

use App\Models\BaptismListReport;
use App\Models\Event;
use App\Models\Regional;
use App\Models\User;
use App\Support\PasswordStrength;
use Carbon\Carbon;
use Database\Seeders\CcbReferenceDataSeeder;
use Database\Seeders\EventTypeAudiencePresetSeeder;
use Database\Seeders\RegionalBaptismListNoticesSeeder;
use Database\Seeders\RegionalEmbuArtesAgenda2026Seeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SeedEmbuAgendaCommand extends Command
{
    protected $signature = 'nexus:seed-embu-agenda
                            {--keep-demo : Mantém a regional demonstração ativa}
                            {--deactivate-demo : Alias compatível (demo desativada por defeito)}
                            {--no-reset-admin-password : Não regrava a senha do admin a partir do .env}';

    protected $description = 'Semeia tipos + Agenda Excel 2026 Embu, aponta admins e desativa a demo.';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => CcbReferenceDataSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => EventTypeAudiencePresetSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => RegionalEmbuArtesAgenda2026Seeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => RegionalBaptismListNoticesSeeder::class, '--force' => true]);

        $embu = Regional::query()->where('slug', 'embu-das-artes')->first();
        if (! $embu) {
            $this->error('Regional embu-das-artes não encontrada.');

            return self::FAILURE;
        }

        // update() em query builder — evita eventos Eloquent / activity log no User.
        Regional::query()->whereKey($embu->id)->update([
            'is_active' => true,
            'name' => 'Regional Embu das Artes',
        ]);

        $legacy = Regional::query()->where('slug', 'regional-embu-das-artes')->first();
        if ($legacy && $legacy->id !== $embu->id) {
            Event::query()->where('regional_id', $legacy->id)->update(['regional_id' => $embu->id]);
            BaptismListReport::query()->where('regional_id', $legacy->id)->update(['regional_id' => $embu->id]);
            User::query()->where('regional_id', $legacy->id)->update(['regional_id' => $embu->id]);
            Regional::query()->whereKey($legacy->id)->update([
                'is_active' => false,
                'name' => $legacy->name.' (legado)',
            ]);
            $this->warn('Regional legado regional-embu-das-artes desativada; dados migrados para embu-das-artes.');
        }

        $deactivateDemo = ! $this->option('keep-demo');
        if ($deactivateDemo) {
            $demo = Regional::query()->where('slug', 'ccb-demo')->first();
            if ($demo) {
                User::query()->where('regional_id', $demo->id)->update(['regional_id' => $embu->id]);
                Regional::query()->whereKey($demo->id)->update(['is_active' => false]);
                $this->info('Regional demonstração desativada; utilizadores apontados para Embu.');
            }
        }

        User::query()
            ->where(function ($q) {
                $q->where('is_super_admin', true)
                    ->orWhere('email', 'thomasgoncalves@yahoo.com.br');
            })
            ->where(function ($q) use ($embu) {
                $q->whereNull('regional_id')->orWhere('regional_id', '!=', $embu->id);
            })
            ->update(['regional_id' => $embu->id]);

        if (! $this->ensureAdminLogin($embu->id)) {
            return self::FAILURE;
        }

        $parents = Event::query()
            ->where('regional_id', $embu->id)
            ->where('is_occurrence', false)
            ->whereNull('parent_event_id')
            ->where('status', '!=', 'cancelled')
            ->count();

        $from = Carbon::parse('2026-10-15')->startOfDay();
        $to = Carbon::parse('2026-12-31')->endOfDay();
        $bulletinInWindow = Event::query()
            ->where('regional_id', $embu->id)
            ->where('include_on_baptism_list', true)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereBetween('starts_at', [$from, $to])
            ->count();

        $this->info("OK: {$parents} séries ativas na regional {$embu->name} (id {$embu->id}).");
        $this->info("Eventos no boletim 15/10–31/12/2026: {$bulletinInWindow}.");

        if ($bulletinInWindow === 0) {
            $this->warn('Atenção: zero eventos no boletim na janela — a lista PDF ficará vazia.');
        }

        return self::SUCCESS;
    }

    /**
     * Garante admin com a senha de INITIAL_ADMIN_PASSWORD (sem disparar model events).
     */
    protected function ensureAdminLogin(int $embuRegionalId): bool
    {
        $email = 'thomasgoncalves@yahoo.com.br';
        $password = (string) config('nexus.initial_admin_password', '');
        if ($password === '') {
            $this->error('INITIAL_ADMIN_PASSWORD vazio no .env — defina a senha padrão antes do seed.');

            return false;
        }

        $user = User::query()->where('email', $email)->first();
        $resetPassword = ! $this->option('no-reset-admin-password');

        if ($user === null) {
            $user = User::query()->create([
                'name' => 'Thomas Gonçalves',
                'email' => $email,
                ...PasswordStrength::stored($password),
                'email_verified_at' => now(),
                'regional_id' => $embuRegionalId,
            ]);
            User::query()->whereKey($user->id)->update(['is_super_admin' => true]);
            $this->info("Admin criado: {$email} (senha = INITIAL_ADMIN_PASSWORD do .env).");
        } else {
            $payload = [
                'regional_id' => $embuRegionalId,
                'email_verified_at' => $user->email_verified_at ?? now(),
                'is_super_admin' => true,
                'updated_at' => now(),
            ];

            if ($resetPassword) {
                // Só regrava se a senha atual NÃO for a do .env (evita logout desnecessário).
                if (! Hash::check($password, (string) $user->password)) {
                    $payload = array_merge($payload, PasswordStrength::stored($password));
                    $payload['remember_token'] = Str::random(60);
                    $this->warn('Senha do admin reaplicada a partir de INITIAL_ADMIN_PASSWORD (estava diferente).');
                    $this->warn('Sessões antigas deste utilizador ficam inválidas — entre de novo com a senha do .env.');
                } else {
                    $payload['password_strength'] = PasswordStrength::level($password);
                    $this->info('Admin: senha já confere com INITIAL_ADMIN_PASSWORD (não alterada).');
                }
            } elseif (Hash::check($password, (string) $user->password)) {
                $payload['password_strength'] = PasswordStrength::level($password);
                $this->info('Admin: --no-reset-admin-password (senha intacta).');
            } else {
                $this->info('Admin: --no-reset-admin-password (senha intacta).');
            }

            User::query()->whereKey($user->id)->update($payload);
        }

        $role = Role::query()->where('name', 'Administrador')->where('guard_name', 'web')->first();
        if ($role) {
            $user->refresh();
            $user->syncRoles([$role->name]);
            $this->info('Admin: papel Administrador sincronizado.');
        } else {
            $this->warn('Papel Administrador não encontrado — rode RolesAndPermissionsSeeder.');
        }

        $fresh = User::query()->where('email', $email)->first();
        if (! $fresh || ! Hash::check($password, (string) $fresh->password)) {
            $this->error('Falha ao validar senha do admin após o seed.');

            return false;
        }

        $this->info("Login: {$email} · senha = valor de INITIAL_ADMIN_PASSWORD no .env do servidor.");

        return true;
    }
}
