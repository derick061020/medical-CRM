<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaboratoryResultResource\Pages;
use App\Filament\Resources\LaboratoryResultResource\RelationManagers;
use App\Models\LaboratoryResult;
use App\Models\User;
use App\Models\DoctorRating;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action as TableAction;

class LaboratoryResultResource extends Resource
{
    protected static ?string $model = LaboratoryResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Exámenes';
    protected static ?string $navigationBadgeColor = 'danger';

    public static function getNavigationBadge(): ?string
    { 
        if (auth()->user()->type === 'professional') {
            return LaboratoryResult::where('status', 'pending')
                ->where('assigned_doctor_id', auth()->id())
                ->count();
        } elseif (auth()->user()->type === 'patient') {
            return LaboratoryResult::where('status', 'pending')
                ->where('user_id', auth()->id())
                ->count();
        }
        return null;
    }

    public static function getNavigationLabel(): string
    {
        if (auth()->user()->type === 'group') {
            return 'Casos';
        }
        return auth()->user()->type === 'professional' 
            ? 'Exámenes Asignados' 
            : 'Mis Exámenes';
    }

    
    public static function canCreate(): bool
    {
        return auth()->user()->type !== 'patient' && auth()->user()->type !== 'professional';
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->type !== 'patient' ;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->type !== 'patient' && auth()->user()->type !== 'professional';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Examen')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Card::make()
                                    ->columnSpan(2)
                                    ->schema([
                                        Forms\Components\Select::make('user_id')
                                            ->label('Paciente')
                                            ->relationship('user', 'name')
                                            ->required(),
                                        Forms\Components\TextInput::make('test_name')
                                            ->label('Nombre del Examen')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('test_type')
                                            ->label('Tipo de Examen')
                                            ->options([
                                                'blood' => 'Sangre',
                                                'urine' => 'Orina',
                                                'biochemistry' => 'Bioquímica',
                                                'hematology' => 'Hematología',
                                                'immunology' => 'Inmunología',
                                                'microbiology' => 'Microbiología',
                                            ])
                                            ->required(),
                                        Forms\Components\DatePicker::make('test_date')
                                            ->label('Fecha del Examen')
                                            ->required(),
                                        Forms\Components\Select::make('assigned_doctor_id')
                                            ->label('Doctor Asignado')
                                            ->options(User::all()->pluck('name', 'id'))
                                            ->required(),
                                        Forms\Components\TextInput::make('price')
                                            ->label('Precio')
                                            ->numeric()
                                            ->required()
                                            ->prefix('$')
                                            ->minValue(0),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripción')
                                            ->maxLength(65535)
                                            ->columnSpanFull(),
                                    ]),
                                Forms\Components\Card::make()
                                    ->columnSpan(1)
                                    ->schema([
                                        Forms\Components\FileUpload::make('file_path')
                                            ->image()
                                            ->imageEditor()
                                            ->columnSpanFull()
                                            ->maxFiles(5)
                                            ->maxSize(5120)
                                            ->label('Adjuntar Documentos'),
                                        Forms\Components\Actions::make([
                                            Action::make('dicom')
                                                ->label('Ver DICOM')
                                                ->icon('heroicon-o-document-text')
                                                ->modalHeading('')
                                                ->modalActions([])
                                                ->modalSubmitAction(false)
                                                ->modalCancelAction(false)
                                                ->modalContent(function () {
                                                    return view('filament.resources.dicom_viewer');
                                                })
                                                ->modalWidth('6xl')
                                        ])->hiddenOn('create')
                                    ])
                            ])
                    ]),
                Forms\Components\Textarea::make('results')
                    ->label('Resultados del Examen')
                    ->columnSpanFull()
                    ->helperText('Ingrese los resultados del examen en formato JSON')
                    ->hiddenOn('create'),
                Forms\Components\Textarea::make('notes')
                ->columnSpanFull()
                    ->maxLength(65535),
            ]);
    }

    public static function afterSave(Record $record, Form $form): void
    {
        if ($form->isInEditMode()) {
            $record->status = 'completed';
            $record->save();
        }
    }

    public static function table(Table $table): Table
    {
        // Obtener el tipo de usuario actual
        $userType = auth()->user()->type;

        // Configurar la consulta según el tipo de usuario
        $query = LaboratoryResult::query();
        
        if ($userType === 'professional') {
            // Para doctores: mostrar solo los exámenes asignados a ellos
            $query->where('assigned_doctor_id', auth()->id());
        } elseif ($userType === 'patient') {
            // Para pacientes: mostrar solo sus propios exámenes
            $query->where('user_id', auth()->id());
        }elseif ($userType === 'group') {
            // Para grupos: mostrar todos los exámenes
            $query->where('clinic_id', auth()->user()->clinic_id);
        }

        return $table
            ->query($query)
            ->columns([
                // Columnas comunes para todos
                Tables\Columns\TextColumn::make('test_name')
                    ->label('Nombre del Examen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('test_type')
                    ->label('Tipo de Examen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money('USD')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('test_date')
                    ->label('Fecha del Examen')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'pending' => 'warning',
                        'completed' => 'success',
                        'reviewed' => 'info',
                    ])
                    ->sortable(),

                // Columnas específicas según el tipo de usuario
                ...($userType === 'professional' || $userType === 'admin'
                    ? [
                        Tables\Columns\TextColumn::make('user.name')
                            ->label('Paciente')
                            ->searchable()
                            ->sortable(),
                        Tables\Columns\TextColumn::make('assigned_doctor_id')
                            ->label('Doctor Asignado')
                            ->searchable()
                            ->sortable()
                            ->formatStateUsing(function ($state, $record) {
                                if (!$state) return '-';
                                
                                $doctor = User::find($state);
                                return $doctor ? $doctor->name : '-';
                            }),
                    ]
                    : [
                        Tables\Columns\TextColumn::make('assigned_doctor_id')
                            ->label('Doctor Asignado')
                            ->searchable()
                            ->sortable()
                            ->formatStateUsing(function ($state, $record) {
                                if (!$state) return '-';
                                
                                $doctor = User::find($state);
                                return $doctor ? $doctor->name : '-';
                            }),
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('test_type')
                    ->label('Tipo de Examen')
                    ->options([
                        'blood' => 'Sangre',
                        'urine' => 'Orina',
                        'biochemistry' => 'Bioquímica',
                        'hematology' => 'Hematología',
                        'immunology' => 'Inmunología',
                        'microbiology' => 'Microbiología',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'completed' => 'Completado',
                        'reviewed' => 'Revisado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (LaboratoryResult $record) {
                        return $record->file_path ? redirect("/storage/$record->file_path") : null;
                    }),
                TableAction::make('rate')
                    ->label('Calificar Doctor')
                    ->icon('heroicon-o-star')
                    ->modalHeading('Calificar Doctor')
                    ->modalIcon('heroicon-o-star')
                    ->modalWidth('lg')
                    ->visible(function (LaboratoryResult $record) {
                        return !DoctorRating::where('user_id', auth()->id())
                            ->where('laboratory_result_id', $record->id)
                            ->exists();
                    })
                    ->form([
                        Select::make('rating')
                            ->options([
                                1 => '⭐',
                                2 => '⭐⭐',
                                3 => '⭐⭐⭐',
                                4 => '⭐⭐⭐⭐',
                                5 => '⭐⭐⭐⭐⭐',
                            ])
                            ->required()
                            ->label('Calificación'),
                        Forms\Components\Textarea::make('comment')
                            ->label('Comentario')
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->action(function (LaboratoryResult $record, array $data) {
                        DoctorRating::create([
                            'user_id' => auth()->id(),
                            'doctor_id' => $record->assigned_doctor_id,
                            'laboratory_result_id' => $record->id,
                            'rating' => $data['rating'],
                            'comment' => $data['comment'] ?? null,
                        ]);
                        session()->flash('success', 'Calificación agregada exitosamente.');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaboratoryResults::route('/'),
            'create' => Pages\CreateLaboratoryResult::route('/create'),
            'edit' => Pages\EditLaboratoryResult::route('/{record}/edit'),
        ];
    }
}
