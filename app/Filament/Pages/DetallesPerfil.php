<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DetallesPerfil extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static string $view = 'filament.pages.detalles-perfil';

    public ?object $userInfo;

    public function mount()
    {
        $this->userInfo = auth()->user();
    }
    

    public static function canAccess(): bool
    {
        return auth()->user()->type !== 'group';
    }

    public static function getNavigationLabel(): string
    {
        return auth()->user()->type === 'professional' 
            ? 'Perfil del Doctor' 
            : 'Perfil del Paciente';
    }
}
