<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Cita;

class CalendarWidget extends FullCalendarWidget
{
    /**
     * FullCalendar will call this function whenever it needs new event data.
     * This is triggered when the user clicks prev/next or switches views on the calendar.
     */
    protected function getColumns(): int | array
    {
        return 1;
    }
    protected static ?int $sort = 2;

    public function fetchEvents(array $fetchInfo): array
    {
        $citas = Cita::where('user_id', auth()->id())
            ->whereBetween('fecha_hora', [
                $fetchInfo['start'],
                $fetchInfo['end']
            ])
            ->get();

        return $citas->map(function (Cita $cita) {
            return [
                'title' => $cita->titulo,
                'start' => $cita->fecha_hora,
                'end' => $cita->fecha_hora,
                'description' => $cita->descripcion,
                'backgroundColor' => $cita->confirmada ? '#22c55e' : '#ef4444',
                'borderColor' => $cita->confirmada ? '#22c55e' : '#ef4444',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'confirmada' => $cita->confirmada,
                ],
            ];
        })->toArray();
    }
}