<x-app-layout>
    <section class=" bg-neutral-50">
        <div class="container mx-auto max-w-7xl px-4 py-8 min-h-screen">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-primary font-tertiary">My Drafts</h1>
                    <p class="text-tertiary mt-2 font-main">Resume your incomplete applications</p>
                </div>
                <div>
                    <a href="{{ route('jobs.browse') }}"
                        class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors font-main text-sm font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Browse Jobs
                    </a>
                </div>
            </div>

            <!-- Drafts Container -->
            @if ($drafts->isEmpty())
                <div class="bg-white p-8 rounded-xl shadow-lg border border-neutral-200">
                    <div class="text-center py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-neutral-300 mb-4"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <h3 class="text-xl font-semibold text-neutral-800 mb-2 font-secondary">No Drafts Found</h3>
                        <p class="text-neutral-500 mb-6 max-w-md mx-auto font-secondary">You don't have any saved drafts
                            yet. Start
                            applying for jobs and save your progress along the way.</p>
                        <a href="{{ route('jobs.browse') }}"
                            class="inline-flex items-center px-5 py-3 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors font-main font-medium">
                            Explore Opportunities
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-1">
                    @foreach ($drafts as $draft)
                        <div
                            class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow border border-neutral-200 overflow-hidden group">
                            <div class="p-6 flex flex-col md:flex-row md:items-center justify-between">
                                <!-- Job Title and Date -->
                                <div class="flex items-center mb-4 md:mb-0">
                                    <div
                                        class="h-12 w-12 rounded-lg bg-neutral-100 flex items-center justify-center overflow-hidden mr-4 border border-neutral-200">
                                        @if (isset($draft->job->images))
                                            <img src="{{ asset('storage/' . $draft->job->images) }}"
                                                alt="{{ $draft->job->slug }}" class="h-full w-full object-cover">
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-neutral-400"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <h2
                                            class="text-lg font-semibold text-neutral-800 font-secondary group-hover:text-primary transition-colors">
                                            {{ $draft->job->title }}
                                        </h2>
                                        <p class="text-sm text-neutral-500 font-main mt-1">
                                            Started on <span
                                                class="font-medium">{{ $draft->created_at->format('M d, Y') }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center space-x-3">
                                    <a href="{{ route('applications.continue', ['slug' => $draft->job->slug]) }}"
                                        class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-main text-sm font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        Continue
                                    </a>
                                    <form action="{{ route('applications.destroy', ['id' => $draft->id]) }}"
                                        method="POST" class="inline-block" id="delete-form-{{ $draft->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-white border border-neutral-300 text-neutral-700 rounded-lg hover:bg-neutral-50 transition-colors font-main text-sm font-medium"
                                            onclick="confirmDelete(event, {{ $draft->id }})">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-red-500"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <!-- Subtle indicator line -->
                            <div class="h-1 bg-accent/20">
                                <div class="h-full bg-accent" style="width: 35%"></div>
                            </div>
                        </div>
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

        <!-- Footer -->
        @include('partials\footer-secondary')

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

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                @if (session('success'))
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-right',
                        iconColor: 'white',
                        customClass: {
                            popup: 'colored-toast',
                        },
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    })
                    Toast.fire({
                        icon: 'success',
                        title: '{{ session('success') }}'
                    });
                @endif
            });
        </script>

    </section>
</x-app-layout>
