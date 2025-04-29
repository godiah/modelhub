<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Archived Engagements') }}
            </h2>
            <a href="{{ route('engagements.index') }}"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                </svg>
                Back to Active Engagements
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    @if ($archivedEngagements->isEmpty())
                        <div class="text-center py-8">
                            <div
                                class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No archived engagements</h3>
                            <p class="text-gray-500">You don't have any archived engagements yet.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Job
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Amount
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Archived Date
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($archivedEngagements as $engagement)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $engagement->application->job->title }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    ID: #{{ $engagement->id }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        @if (Auth::user()->id === $engagement->application->poster_id)
                                                            <img class="h-10 w-10 rounded-full"
                                                                src="{{ $engagement->application->applicant->profile_photo_url }}"
                                                                alt="{{ $engagement->application->applicant->name }}">
                                                        @else
                                                            <img class="h-10 w-10 rounded-full"
                                                                src="{{ $engagement->application->poster->profile_photo_url }}"
                                                                alt="{{ $engagement->application->poster->name }}">
                                                        @endif
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            @if (Auth::user()->id === $engagement->application->poster_id)
                                                                {{ $engagement->application->applicant->name }}
                                                            @else
                                                                {{ $engagement->application->poster->name }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    @if ($engagement->status === 'completed') bg-green-100 text-green-800 
                                                    @elseif($engagement->status === 'cancelled') bg-red-100 text-red-800
                                                    @elseif($engagement->status === 'in_progress') bg-blue-100 text-blue-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    {{ ucfirst(str_replace('_', ' ', $engagement->status)) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                ${{ number_format($engagement->agreed_amount, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $engagement->updated_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="{{ route('engagements.index', $engagement->id) }}"
                                                        class="text-indigo-600 hover:text-indigo-900">
                                                        View
                                                    </a>
                                                    <form action="{{ route('engagements.restore') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="engagement_id"
                                                            value="{{ $engagement->id }}">
                                                        <button type="submit"
                                                            class="text-green-600 hover:text-green-900">
                                                            Restore
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $archivedEngagements->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div id="restore-modal" x-data="{ open: false, engagementId: null }" x-cloak>
        <!-- Modal backdrop -->
        <div x-show="open" class="fixed inset-0 z-40 bg-black bg-opacity-25" @click="open = false"></div>

        <!-- Modal content -->
        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Restore Engagement</h3>
                    <p class="text-gray-600 mb-4">
                        Are you sure you want to restore this engagement? It will be moved back to your active
                        engagements list.
                    </p>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button @click="open = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <form id="restore-form" action="{{ route('engagements.restore') }}" method="POST">
                            @csrf
                            <input type="hidden" name="engagement_id" x-bind:value="engagementId">
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 border border-transparent rounded-md text-white hover:bg-green-700">
                                Restore
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
