<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4" style="gap: 1rem;">
                    <div style="width: 6rem; height: 6rem; border-radius: 50%; background: linear-gradient(135deg, #4F46E5, #3B82F6); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ auth()->user()->name }}</h1>
                        <p class="text-gray-600 dark:text-gray-400">{{ auth()->user()->email }}</p>
                       
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="javascript:history.back()" style="padding: 0.5rem 1rem; background-color: #4F46E5; color: white; border-radius: 0.375rem; font-weight: 600; gap: 0.25rem; display: flex; align-items: center; transition: background-color 0.2s;">
                        <i class="fas fa-edit" style="margin-right: 0.25rem;"></i> Volver
                    </a>
                </div>
            </div>
        </div>

        <!-- Events Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-700">
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Doctor/Examen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($events as $event)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $event['date']->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center">
                                            <i class="fas fa-{{ $event['icon'] }} mr-2 text-{{ $event['icon_color'] }}-600 dark:text-{{ $event['icon_color'] }}-400"></i>
                                            {{ ucfirst($event['type']) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $event['doctor'] ?? $event['test_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                               @if($event['status'] === 'completed') bg-green-100 text-green-800
                                               @elseif($event['status'] === 'pending') bg-yellow-100 text-yellow-800
                                               @else bg-red-100 text-red-800 @endif">
                                            {{ ucfirst($event['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ $event['view_route'] }}" 
                                           class="text-{{ $event['icon_color'] }}-600 hover:text-{{ $event['icon_color'] }}-900 dark:text-{{ $event['icon_color'] }}-400 dark:hover:text-{{ $event['icon_color'] }}-300">
                                            {{ $event['view_text'] }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
