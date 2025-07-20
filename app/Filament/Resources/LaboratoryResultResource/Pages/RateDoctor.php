<?php

namespace App\Filament\Resources\LaboratoryResultResource\Pages;

use App\Filament\Resources\LaboratoryResultResource;
use App\Models\DoctorRating;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Forms\Components;

class RateDoctor extends ViewRecord
{
    protected static string $resource = LaboratoryResultResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.hms.resources.laboratory-results.index'))
                ->color('gray'),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Components\Section::make('Calificación del Doctor')
                ->schema([
                    Components\Grid::make(2)
                        ->schema([
                            Components\Select::make('rating')
                                ->label('Calificación (1-5 estrellas)')
                                ->options([
                                    1 => '⭐',
                                    2 => '⭐⭐',
                                    3 => '⭐⭐⭐',
                                    4 => '⭐⭐⭐⭐',
                                    5 => '⭐⭐⭐⭐⭐',
                                ])
                                ->required(),
                            Components\Textarea::make('comment')
                                ->label('Comentario')
                                ->maxLength(65535)
                                ->columnSpanFull(),
                        ])
                ])
                ->columns(2),
        ];
    }

    protected function handleRecordUpdate($data): void
    {
        DoctorRating::create([
            'user_id' => auth()->id(),
            'doctor_id' => $this->record->assigned_doctor_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $this->notify('success', 'Calificación guardada exitosamente');
        $this->redirect(route('filament.hms.resources.laboratory-results.index'));
    }
}
