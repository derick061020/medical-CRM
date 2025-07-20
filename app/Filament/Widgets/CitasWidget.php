<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Cita;

class CitasWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->type == 'patient';
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Cita::where('user_id', auth()->id())
            )
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_hora')
                    ->label('Fecha y Hora')
                    ->dateTime('d/m/Y H:i'),
                Tables\Columns\BadgeColumn::make('confirmada')
                    ->label('Estado')
                    ->colors([
                        false => 'danger',
                        true => 'success',
                    ]),
            ]);
    }
}
