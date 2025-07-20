
    @php
        if($this->userInfo !== null){
            $userInfo = $this->userInfo;
        }
    
    @endphp

    <div style="display: flex; flex-direction: column; gap: 1.5rem;margin-top: 2rem;">
        <!-- Header Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4" style="gap: 1rem;">
                    <div style="width: 6rem; height: 6rem; border-radius: 50%; background: linear-gradient(135deg, #4F46E5, #3B82F6); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $userInfo->name }}</h1>
                        <p class="text-gray-600 dark:text-gray-400">{{ $userInfo->email }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Última conexión: {{ $userInfo->last_login_at ? $userInfo->last_login_at->format('d/m/Y H:i') : 'Nunca' }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Tipo de usuario: 
                                @switch($userInfo->type)
                                    @case('admin')
                                        <span style="padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem;background-color: #E11D48; color: white;">Administrador</span>
                                        @break

                                    @case('patient')
                                        <span style="padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem;background-color: #FBBF24; color: #854D26;">Paciente</span>
                                        @break

                                    @case('professional')
                                        <span style="padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem;background-color: #38C172; color: white;">Profesional</span>
                                        @break
                                    @case('group')
                                        <span style="padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem;background-color: #38C172; color: white;">Grupo</span>
                                        @break
                                @endswitch
                        </p>
                    </div>
                </div>
                @if (auth()->user()->id === $userInfo->id)
                <div style="display: flex; gap: 0.5rem;">
                    <a href="profile" style="padding: 0.5rem 1rem; background-color: #4F46E5; color: white; border-radius: 0.375rem; font-weight: 600; gap: 0.25rem; display: flex; align-items: center; transition: background-color 0.2s;">
                        <i class="fas fa-edit" style="margin-right: 0.25rem;"></i> Editar Perfil
                    </a>
                    @if ($userInfo->type === 'patient' )
                    <a href="medic-history" style="padding: 0.5rem 1rem; background-color:rgb(170, 169, 182); color: white; border-radius: 0.375rem; font-weight: 600; gap: 0.25rem; display: flex; align-items: center; transition: background-color 0.2s;">
                        <i class="fas fa-edit" style="margin-right: 0.25rem;"></i> Ver historial Médico
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @if ($userInfo->type === 'patient')
        <!-- Información del Paciente -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Información del Paciente</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Información Básica -->
                <div class="rounded-lg p-4 bg-white dark:bg-gray-800 dark:shadow-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Información Básica</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-user text-indigo-600 dark:text-indigo-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Nombre completo</p>
                                <p class="text-gray-800 dark:text-white">{{ $userInfo->name }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-envelope text-green-600 dark:text-green-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Email</p>
                                <p class="text-gray-800 dark:text-white">{{ $userInfo->email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Fecha de registro</p>
                                <p class="text-gray-800 dark:text-white">{{ $userInfo->created_at->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Contacto -->
                <div class="rounded-lg p-4 bg-white dark:bg-gray-800 dark:shadow-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Información de Contacto</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-phone text-blue-600 dark:text-blue-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Teléfono</p>
                                <p class="text-gray-800 dark:text-white">+57 315 123 4567</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-map-marker-alt text-red-600 dark:text-red-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Dirección</p>
                                <p class="text-gray-800 dark:text-white">Calle 45 # 23-12, Bogotá</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-city text-orange-600 dark:text-orange-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Ciudad</p>
                                <p class="text-gray-800 dark:text-white">Bogotá</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Médica -->
                <div class="rounded-lg p-4 bg-white dark:bg-gray-800 col-span-2 dark:shadow-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Información Médica</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-hospital text-purple-600 dark:text-purple-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">EPS/IPS</p>
                                <p class="text-gray-800 dark:text-white">EPS Salud Total</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-book-medical text-pink-600 dark:text-pink-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Antecedentes</p>
                                <p class="text-gray-800 dark:text-white">- Hipertensión arterial controlada<br>
                                    - Diabetes tipo 2<br>
                                    - Alergias: Penicilina</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if ($userInfo->type === 'professional' || $userInfo->type === 'admin')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Perfil Médico</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Información Básica -->
                <div class="rounded-lg p-4 bg-white dark:bg-gray-800 dark:shadow-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Información Básica</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-user-md text-indigo-600 dark:text-indigo-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Nombre completo</p>
                                <p class="text-gray-800 dark:text-white">{{ $userInfo->name }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-envelope text-green-600 dark:text-green-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Email</p>
                                <p class="text-gray-800 dark:text-white">{{ $userInfo->email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Fecha de registro</p>
                                <p class="text-gray-800 dark:text-white">{{ $userInfo->created_at->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Contacto -->
                <div class="rounded-lg p-4 bg-white dark:bg-gray-800 dark:shadow-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">Información de Contacto</h3>
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-phone text-blue-600 dark:text-blue-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Email</p>
                                <p class="text-gray-800 dark:text-white">{{ $userInfo->email }}</p>
                            </div>
                        </div>
                        <!---<div class="flex items-center space-x-3">
                            <i class="fas fa-map-marker-alt text-red-600 dark:text-red-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Dirección</p>
                                <p class="text-gray-800 dark:text-white">Calle 45 # 23-12, Bogotá</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-city text-orange-600 dark:text-orange-400"></i>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Ciudad</p>
                                <p class="text-gray-800 dark:text-white">Bogotá</p>
                            </div>
                        </div>-->
                    </div>
                </div>

                <!-- Experiencia Laboral -->
                <div class="rounded-lg p-4 bg-white dark:bg-gray-800 dark:shadow-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Experiencia Laboral</h3>
                    <div class="space-y-4">
                        @foreach($userInfo->doc_info['work_experience'] ?? [] as $experience)
                            <div class="border-l-4 border-blue-600 pl-4">
                                <h4 class="font-medium mb-1">{{ $experience['position'] }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $experience['company'] }}
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        ({{ \Carbon\Carbon::parse($experience['start_date'])->format('d/m/Y') }} - 
                                        {{ \Carbon\Carbon::parse($experience['end_date'])->format('d/m/Y') }})
                                    </span>
                                </p>
                                <p class="text-gray-500 dark:text-gray-300">{{ $experience['description'] }}</p>
                                <div class="mt-2">
                                    <a href="{{ asset('storage/' . $experience['certificate']) }}" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm" 
                                       target="_blank">
                                        <i class="fas fa-download mr-1"></i> Descargar certificado
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Documentos -->
                <div class="rounded-lg p-4 bg-white dark:bg-gray-800 dark:shadow-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Documentos</h3>
                    <div class="space-y-4">
                        @if(isset($userInfo->doc_info['professional_degree']))
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-graduation-cap text-green-600 dark:text-green-400"></i>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Título Profesional</p>
                                    <a href="{{ asset('storage/' . $userInfo->doc_info['professional_degree']) }}" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm" 
                                       target="_blank">
                                        <i class="fas fa-download mr-1"></i> Descargar
                                    </a>
                                </div>
                            </div>
                        @endif
                        @if(isset($userInfo->doc_info['medical_college']))
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-id-card text-purple-600 dark:text-purple-400"></i>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Colegiatura</p>
                                    <a href="{{ asset('storage/' . $userInfo->doc_info['medical_college']) }}" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm" 
                                       target="_blank">
                                        <i class="fas fa-download mr-1"></i> Descargar
                                    </a>
                                </div>
                            </div>
                        @endif
                        @if(isset($userInfo->doc_info['professional_ability']))
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-certificate text-yellow-600 dark:text-yellow-400"></i>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Habilidad Profesional</p>
                                    <a href="{{ asset('storage/' . $userInfo->doc_info['professional_ability']) }}" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm" 
                                       target="_blank">
                                        <i class="fas fa-download mr-1"></i> Descargar
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>


