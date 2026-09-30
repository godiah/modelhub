<x-app-layout>
    <section>
        <div class="container mx-auto max-w-7xl px-4 py-8">
            <!-- Drafts Container -->
            @if ($drafts->isEmpty())
                <x-card shadow="lg" class="p-8">
                    <div class="text-center py-12">
                        <x-icon name="pencil-square" class="h-16 w-16 mx-auto text-neutral-300 mb-4" />
                        <h3 class="text-xl font-semibold text-neutral-800 mb-2 font-secondary">No Drafts Found</h3>
                        <p class="text-neutral-500 mb-6 max-w-md mx-auto font-secondary">You don't have any saved drafts
                            yet. Start
                            applying for jobs and save your progress along the way.</p>
                        <a href="{{ route('jobs.browse') }}"
                            class="inline-flex items-center px-5 py-3 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors font-main font-medium">
                            Explore Opportunities
                            <x-icon name="arrow-right" class="h-5 w-5 ml-2" />
                        </a>
                    </div>
                </x-card>
            @else
                <div class="grid gap-6 md:grid-cols-1">
                    @foreach ($drafts as $draft)
                        <x-card shadow="md" clip class="hover:shadow-lg transition-shadow group">
                            <div class="p-6 flex flex-col md:flex-row md:items-center justify-between">
                                <!-- Job Title and Date -->
                                <div class="flex items-center mb-4 md:mb-0">
                                    <div
                                        class="h-12 w-12 rounded-lg bg-neutral-100 flex items-center justify-center overflow-hidden mr-4 border border-neutral-200">
                                        @if (isset($draft->job->images))
                                            <img src="{{ asset('storage/' . $draft->job->images) }}"
                                                alt="{{ $draft->job->slug }}" class="h-full w-full object-cover">
                                        @else
                                            <x-icon name="pencil-square" class="h-6 w-6 text-neutral-400" />
                                        @endif
                                    </div>
                                    <div>
                                        <h2
                                            class="text-lg font-semibold text-neutral-800 font-secondary group-hover:text-primary transition-colors">
                                            {{ $draft->job->title }}
                                        </h2>
                                        <p class="text-sm text-neutral-500 font-main mt-1">
                                            Started on <span
                                                class="font-medium"><x-date :date="$draft->created_at" format="M d, Y" /></span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center space-x-3">
                                    <x-btn class="text-sm" href="{{ route('applications.continue', ['slug' => $draft->job->slug]) }}">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                        Continue
                                    </x-btn>
                                    <form action="{{ route('destroy.drafts', ['application' => $draft->id]) }}"
                                        method="POST" class="inline-block" id="delete-form-{{ $draft->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-white border border-neutral-300 text-neutral-700 rounded-lg hover:bg-neutral-50 transition-colors font-main text-sm font-medium"
                                            onclick="confirmDelete(event, {{ $draft->id }})">
                                            <x-icon name="trash" class="h-4 w-4 mr-2 text-red-500" />
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <!-- Subtle indicator line -->
                            <div class="h-1 bg-accent/20">
                                <div class="h-full bg-accent" style="width: 35%"></div>
                            </div>
                        </x-card>
                    @endforeach
                </div>

                <!-- Pagination - If you have pagination -->
                @if (method_exists($drafts, 'links'))
                    <div class="mt-8">
                        {{ $drafts->links() }}
                    </div>
                @endif
            @endif
        </div>

        <script>
            function confirmDelete(event, id) {
                // Prevent the default form submission
                event.preventDefault();

                // Get the form element
                const form = document.getElementById(`delete-form-${id}`);

                // Show SweetAlert confirmation
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit the form if confirmed
                        form.submit();
                    }
                });
            }
        </script>

    </section>
</x-app-layout>
