<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Clinic;
use Filament\Forms\Get;
use Filament\Forms;
use App\Models\Specialty;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canAccess(): bool
    {
        return auth()->user()->type !== 'patient' && auth()->user()->type !== 'professional';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at')->hidden(),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->columnSpanFull()
                    ->hiddenOn('edit')
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'admin' => 'Administrador',
                        'group' => 'Grupo',
                        'professional' => 'Profesional',
                        'patient' => 'Paciente'
                    ])
                    ->live()
                    ->required()
                    ->default('patient')
                    ->columnSpanFull(),
                Forms\Components\Section::make('Especialidades')
                    ->description('Especialidades del profesional')
                    ->schema([
                        Forms\Components\Select::make('specialties')
                            ->multiple()
                            ->relationship('specialties', 'name')
                            ->columnSpanFull()
                            ->preload()
                    ])
                    ->columns(1)
                    ->hidden(fn (Get $get): bool => 
                        !$get('type') || $get('type') !== 'professional'),

                Forms\Components\Section::make('Experiencia Laboral y Documentación')
                    ->description('Experiencia laboral y documentos requeridos para profesionales')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Repeater::make('doc_info.work_experience')
                            ->label('Experiencia Laboral')
                            ->schema([
                                    Forms\Components\TextInput::make('company')
                                        ->label('Empresa')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('position')
                                        ->label('Cargo')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->maxLength(255),
                                    Forms\Components\DatePicker::make('start_date')
                                        ->label('Fecha de Inicio')
                                        ->required()
                                        ->displayFormat('d/m/Y'),
                                    Forms\Components\DatePicker::make('end_date')
                                        ->label('Fecha de Fin')
                                        ->required()
                                        ->displayFormat('d/m/Y'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción de las Funciones')
                                    ->required()
                                    ->maxLength(65535),
                                Forms\Components\FileUpload::make('certificate')
                                    ->label('Certificado de Experiencia')
                                    ->directory('professional-documents')
                                    ->disk('public')
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['position'] ?? null)
                            ->reorderable()
                            ->required(fn (Get $get): bool => $get('type') === 'professional'),

                        Forms\Components\Card::make([
                            Forms\Components\FileUpload::make('doc_info.professional_degree')
                                ->label('Título Profesional')
                                ->directory('professional-documents')
                                ->disk('public')
                                ->required(fn (Get $get): bool => $get('type') === 'professional')
                                ->helperText('Constancia de haber finalizado la carrera de medicina y obtenido el grado correspondiente.'),
                            Forms\Components\FileUpload::make('doc_info.medical_college')
                                ->label('Colegiatura')
                                ->directory('professional-documents')
                                ->disk('public')
                                ->required(fn (Get $get): bool => $get('type') === 'professional')
                                ->helperText('Registro en el Colegio Médico del Perú, que valida la habilitación para ejercer la profesión.'),
                            Forms\Components\FileUpload::make('doc_info.professional_ability')
                                ->label('Constancia de Habilidad Profesional')
                                ->directory('professional-documents')
                                ->disk('public')
                                ->required(fn (Get $get): bool => $get('type') === 'professional')
                                ->helperText('Certifica la capacidad para ejercer la profesión médica.'),
                        ])
                    ])->live()

                    ->columns(1)
                    ->hidden(fn (Get $get): bool => $get('type') !== 'professional'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(auth()->user()->type === 'group' ? 
                User::where('clinic_id', auth()->user()->clinic_id) : 
                User::query())
            ->defaultSort('is_active', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'admin',
                        'success' => 'professional',
                        'warning' => 'patient',
                        'info' => 'group',
                    ]),
                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Estado')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->formatStateUsing(function (bool $state): string {
                        return $state ? 'Activo' : 'Deshabilitado';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'admin' => 'Administrador',
                        'group' => 'Grupo',
                        'professional' => 'Profesional',
                        'patient' => 'Paciente'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Perfil')
                    ->icon('heroicon-o-user-circle')
                    ->modalContent(function (User $record) {
                        return view('filament.pages.detalles-perfil', [
                            'userInfo' => $record
                        ]);
                    })
                    ->modalHeading('')
                    ->modalSubmitAction(false)            //Remove Submit Button
                    ->modalCancelAction(false)          //Remove Cancel Button
                    ->modalWidth('4xl')
                    ->modalButton('Ver Detalles')
                    ->modalFooterActions([]),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Model $record): string => $record->is_active ? 'Deshabilitar' : 'Habilitar')
                    ->color(fn (Model $record): string => $record->is_active ? 'danger' : 'success')
                    ->icon(fn (Model $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->action(function (Model $record): void {
                        $record->update(['is_active' => !$record->is_active]);
                        
                        Notification::make()
                            ->title($record->is_active ? 'Usuario Habilitado' : 'Usuario Deshabilitado')
                            ->body("El estado del usuario ha sido actualizado.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(
                        fn (Model $record): string => '¿Estás seguro de que deseas cambiar el estado de este usuario?',
                        fn (Model $record): string => $record->is_active ? 'Deshabilitar' : 'Habilitar'
                    ),
                Tables\Actions\Action::make('register_exam')
                    ->label('Registrar Examen Médico')
                    ->color('success')
                    ->icon('heroicon-o-document-duplicate')
                    ->modalHeading('Nuevo Examen Médico')
                    ->form([
                        Forms\Components\Section::make('Datos del Examen')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('patient_name')
                                            ->hidden()
                                            ->default(fn (Model $record) => $record->name),
                                        Forms\Components\TextInput::make('test_name')
                                            ->required()
                                            ->label('Nombre del Examen')
                                            ->maxLength(255),
                                        Forms\Components\Select::make('test_type')
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
                                            ->required()
                                            ->default(now()),
                                        Forms\Components\Select::make('status')
                                            ->hidden()
                                            ->default('pending'),
                                        Forms\Components\TextInput::make('price')
                                            ->label('Precio')
                                            ->numeric()
                                            ->required()
                                            ->prefix('$')
                                            ->minValue(0),
                                        Forms\Components\Select::make('assigned_doctor_id')
                                            ->label('Doctor Asignado')
                                            ->options(function () {
                                                $doctors = User::where('type', 'professional')
                                                    ->withCount('laboratoryResults')
                                                    ->get();
                                                
                                                return $doctors->mapWithKeys(function ($doctor) {
                                                    return [$doctor->id => $doctor->name . ' (' . $doctor->laboratory_results_count . ' exámenes)'];
                                                });
                                            })
                                            ->required()
                                            ->native(false)
                                            ->searchable()
                                            ->default(function () {
                                                // Obtener el doctor con menos exámenes
                                                return User::where('type', 'professional')
                                                    ->withCount('laboratoryResults')
                                                    ->orderBy('laboratory_results_count', 'asc')
                                                    ->first()?->id;
                                            }),
                                        Forms\Components\FileUpload::make('file_path')
                                            ->label('Adjuntar Documento')
                                            ->multiple()
                                            ->maxFiles(5)
                                            ->maxSize(5120)
                                            ->columnSpanFull(),
                                    ]),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->action(function (Model $record, array $data) {
                        \DB::table('laboratory_results')->insert([
                            'user_id' => $record->id,
                            'test_name' => $data['test_name'],
                            'test_type' => $data['test_type'],
                            'test_date' => $data['test_date'],
                            'status' => 'pending',
                            'assigned_doctor_id' => $data['assigned_doctor_id'],
                            'description' => $data['description'] ?? '',
                            'file_path' => $data['file_path'][0] ?? null,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        
                        return redirect()->back()->with('success', 'Examen registrado exitosamente');
                    })
                    ->visible(fn (Model $record) => $record->type === 'patient')
                    ->tooltip('Registrar nuevo examen médico'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
