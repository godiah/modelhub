<x-app-layout>
    <div class="min-h-screen bg-neutral-50">
        <div class="max-w-5xl mx-auto p-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold font-main text-neutral-800 mb-1">Staff Roles</h1>
                    <p class="text-neutral-600 font-secondary">Assign the Support or Dispute Manager role to a
                        user so they can review and resolve payment disputes.</p>
                </div>
                @can('view disputes')
                    <a href="{{ route('admin.disputes.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-white border border-neutral-200 text-neutral-700 text-sm font-medium font-main rounded-lg shadow-sm hover:bg-neutral-50 transition-colors duration-200">
                        Disputed Engagements
                    </a>
                @endcan
            </div>

            <div class="bg-white rounded-xl shadow-lg border border-neutral-200/50 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-neutral-50 border-b border-neutral-200">
                        <tr>
                            <th class="px-6 py-3 text-xs font-semibold font-tertiary text-neutral-500 uppercase tracking-wide">User</th>
                            <th class="px-6 py-3 text-xs font-semibold font-tertiary text-neutral-500 uppercase tracking-wide">Current Role</th>
                            <th class="px-6 py-3 text-xs font-semibold font-tertiary text-neutral-500 uppercase tracking-wide">Assign Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($users as $user)
                            @php
                                $currentRole = $user->roles->first()?->name;
                            @endphp
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-neutral-800 font-secondary">{{ $user->name }}</p>
                                    <p class="text-sm text-neutral-500 font-secondary">{{ $user->email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($currentRole)
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-secondary/10 text-secondary border border-secondary/20 capitalize">
                                            {{ str_replace('_', ' ', $currentRole) }}
                                        </span>
                                    @else
                                        <span class="text-sm text-neutral-400 font-secondary">None</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <form action="{{ route('admin.staff.update-role', $user) }}" method="POST"
                                        class="flex items-center space-x-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role"
                                            class="text-sm border-neutral-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                            <option value="" @selected(! $currentRole)>None</option>
                                            <option value="support" @selected($currentRole === 'support')>Support</option>
                                            <option value="dispute_manager" @selected($currentRole === 'dispute_manager')>
                                                Dispute Manager</option>
                                        </select>
                                        <button type="submit"
                                            class="px-3 py-1.5 bg-primary text-white text-xs font-medium font-main rounded-lg hover:bg-primary/90 transition-colors duration-200">
                                            Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-neutral-500 font-secondary">
                                    No users available for staff-role assignment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-8">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
