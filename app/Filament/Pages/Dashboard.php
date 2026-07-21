<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewApiDocs')
                ->label('Xem API Docs')
                ->icon('heroicon-o-code-bracket-square')
                ->color('gray')
                ->url('/docs')
                ->openUrlInNewTab()
                // /docs itself 404s for non-admin on production (routes/web.php) —
                // hide the button too so it isn't a dead link for other roles.
                ->visible(fn (): bool => auth()->user()?->role === UserRole::Admin),
        ];
    }
}
