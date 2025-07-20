<?php

namespace App\Filament\Pages;

use App\Models\LaboratoryResult;
use App\Models\Cita;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MedicHistory extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.medic-history';
    protected static bool $shouldRegisterNavigation = false;

    public $events;

    public function mount()
    {
        $appointments = collect(Cita::where('user_id', Auth::id())
            ->get()
            ->map(function($appointment) {
                return [
                    'type' => 'appointment',
                    'date' => $appointment->fecha_hora,
                    'doctor' => $appointment->doctor->name,
                    'status' => $appointment->status,
                    'created_at' => $appointment->created_at,
                    'view_route' => '',
                    'view_text' => 'Ver Cita',
                    'icon' => 'calendar',
                    'icon_color' => 'indigo',
                ];
            })->toArray());

        $labResults = collect(LaboratoryResult::where('user_id', Auth::id())
            ->get()
            ->map(function($result) {
                return [
                    'type' => 'laboratory',
                    'date' => $result->created_at,
                    'doctor' => null,
                    'status' => $result->status,
                    'created_at' => $result->created_at,
                    'view_route' => '',
                    'view_text' => 'Ver Resultado',
                    'icon' => 'flask',
                    'icon_color' => 'purple',
                    'test_name' => $result->test_name,
                ];
            })->toArray());

        $this->events = $appointments
            ->merge($labResults)
            ->sortByDesc('date')
            ->values();
    }
}
