<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClinicResource\Pages;
use App\Filament\Resources\ClinicResource\RelationManagers;
use App\Models\Clinic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClinicCredentials;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClinicResource extends Resource
{
    protected static ?string $model = \App\Models\Clinic::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'System';
    public static function canAccess(): bool
    {
        return auth()->user()->type == 'admin';
    }

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            // General Information Section
            Forms\Components\Section::make('Información General')
                ->description('Datos básicos de la clínica')
                ->schema([
                    Forms\Components\Card::make([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Clínica')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        
                        Forms\Components\Select::make('clinic_type')
                            ->label('Tipo de Clínica')
                            ->required()
                            ->options([
                                'prestadora' => 'Prestadora de Servicios',
                                'proveedora' => 'Proveedor de Servicios'
                            ])
                            ->columnSpan(2),
                        
                        Forms\Components\TextInput::make('address')
                            ->label('Dirección')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->required()
                            ->tel()
                            ->maxLength(20),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->required()
                            ->email()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->maxLength(65535)
                            ->columnSpan(2),
                    ])
                ]),

            // Logo Section
            Forms\Components\Section::make('Logo y Identidad Visual')
                ->description('Imagen corporativa de la clínica')
                ->schema([
                    Forms\Components\Card::make([
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo de la Clínica')
                            ->image()
                            ->imageEditor()
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth(500)
                            ->imageResizeTargetHeight(500)
                            ->disk('public')
                            ->directory('clinic-logos'),
                        
                        Forms\Components\FileUpload::make('favicon')
                            ->label('Favicon')
                            ->image()
                            ->imageEditor()
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth(32)
                            ->imageResizeTargetHeight(32)
                            ->disk('public')
                            ->directory('clinic-favicons'),
                    ])
                ]),

            // Legal Documents Section
            Forms\Components\Section::make('Documentos Legales')
                ->description('Documentación requerida')
                ->schema([
                    Forms\Components\Card::make([
                        Forms\Components\FileUpload::make('registration_certificate')
                            ->label('Certificado de Registro')
                            ->required()
                            ->directory('clinic-documents')
                            ->disk('public'),
                        
                        Forms\Components\FileUpload::make('tax_certificate')
                            ->label('Certificado de RUT')
                            ->required()
                            ->directory('clinic-documents')
                            ->disk('public'),
                        
                        Forms\Components\FileUpload::make('sanitary_license')
                            ->label('Licencia Sanitaria')
                            ->required()
                            ->directory('clinic-documents')
                            ->disk('public'),
                        
                        Forms\Components\FileUpload::make('insurance_certificate')
                            ->label('Certificado de Seguro')
                            ->required()
                            ->directory('clinic-documents')
                            ->disk('public'),
                    ])
                ])->hiddenOn('edit'),

            // Additional Information Section
            Forms\Components\Section::make('Información Adicional')
                ->description('Datos complementarios')
                ->schema([
                    Forms\Components\Card::make([
                        Forms\Components\TextInput::make('website')
                            ->label('Sitio Web')
                            ->url()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('facebook')
                            ->label('Facebook')
                            ->url()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('instagram')
                            ->label('Instagram')
                            ->url()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('twitter')
                            ->label('Twitter')
                            ->url()
                            ->maxLength(255),
                    ])
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('clinic_type')
                    ->label('Tipo de Clínica')
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'prestadora' => 'Prestadora de Servicios',
                            'proveedora' => 'Proveedor de Servicios',
                            default => $state,
                        };
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('create_user')
                    ->label('Crear Usuario')
                    ->action(function (Clinic $record) {
                        // Check if user already exists for this clinic
                        $existingUser = User::where('clinic_id', $record->id)->first();
                        
                        if ($existingUser) {
                            Notification::make()
                                ->title('Usuario Existente')
                                ->body('Esta clínica ya tiene un usuario asociado.')
                                ->warning()
                                ->send();
                            return false;
                        }
                        
                        // Generate random password
                        $password = Str::random(12);
                        
                        // Create user
                        $user = User::create([
                            'name' => $record->name,
                            'email' => $record->email,
                            'password' => Hash::make($password),
                            'type' => 'group',
                            'clinic_id' => $record->id
                        ]);
                        
                        // Show success notification with password
                        Notification::make()
                        ->title('Usuario Creado')
                        ->body("Se ha creado el usuario exitosamente.\n\nNombre: {$record->name}\nEmail: {$record->email}\nContraseña: {$password}")
                        ->success()
                        ->send();
                        
                        return false;
                    })
                    ->color('success')
                    ->icon('heroicon-o-user')
                    ->hidden(function (Clinic $record) {
                        return User::where('clinic_id', $record->id)->exists();
                    })
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListClinics::route('/'),
            'create' => Pages\CreateClinic::route('/create'),
            'edit' => Pages\EditClinic::route('/{record}/edit'),
        ];
    }
}
