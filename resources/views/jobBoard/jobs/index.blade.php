<x-app-layout>
    <x-slot name="header">
        <!-- Tab Navigation -->
        <div class="">
            <div class="flex justify-end space-x-5 md:justify-end">
                <!-- Post a Job -->
                <button id="post-job-btn" class="flex items-center gap-2 font-medium  border-secondary text-secondary"
                    onclick="showTab('post-job')">
                    <!-- SVG Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="flex-shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                    </svg>
                    Post a Project
                </button>

                <!-- Find a Job -->
                <button id="find-job-btn"
                    class="flex items-center gap-2  font-medium  border-transparent text-neutral-600 hover:text-secondary"
                    onclick="showTab('find-job')">
                    <!-- SVG Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" class="flex-shrink-0">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    Find a Project
                </button>
            </div>
        </div>
    </x-slot>
    <div class="font-main text-neutral-800 bg-white">

        <!-- Post a Job/Hire Freelancer Section  -->
        <div id="post-job-content" class="tab-content">
            <!-- Hero Section -->
            <div class="container max-w-7xl mx-auto px-4 py-16">
                <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16">
                    <div class="w-full md:w-1/2 space-y-6">
                        <h1 class="text-4xl md:text-5xl font-bold text-primary leading-tight">
                            Discover the <span class="text-accent">premier</span> freelance marketplace for <span
                                class="text-accent">3D modeling</span> jobs
                        </h1>
                        <p class="text-base md:text-lg text-neutral-600 max-w-xl font-secondary text-justify">
                            3D Projects connects you with skilled freelance 3D professionals, providing tailored
                            solutions
                            to your unique modeling challenges and ensuring your project's success.
                        </p>
                        <div class="pt-4">
                            <a href="{{ route('jobs.create') }}"
                                class="inline-block px-8 py-4 bg-accent text-white font-semibold rounded-lg shadow-lg hover:bg-amber-600 transition duration-300 ease-in-out">
                                Post Project
                            </a>
                        </div>
                    </div>
                    <div class="w-full md:w-1/2 relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-white via-transparent to-white rounded-lg">
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-b from-white via-transparent to-white rounded-lg">
                        </div>
                        <img src="{{ asset('images/jobs/hero-post-job.png') }}" alt="3D Modeling Platform"
                            class="w-full h-auto relative z-0">
                    </div>
                </div>
            </div>

            <!-- Benefits/Advantages/Features Section -->
            <div class="bg-neutral-50 py-16 md:py-24">
                <div class="container max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-4xl font-bold text-primary mb-4">Guarantee the <span
                                class="text-accent">success</span> of your next 3D
                            project</h2>
                    </div>

                    <div class="space-y-16 max-w-2xl mx-auto">
                        <!-- Benefit 1 -->
                        <x-jobs.benefit-card>
                            <x-jobs.benefit-card-image src="images/jobs/undraw_secure_payment.svg"
                                alt="Secure Payments" />
                            <div class="w-full md:w-3/5 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">Secure Payments</h3>
                                <p class="text-lg text-neutral-600 text-justify">
                                    Your funds are safeguarded through our escrow process, ensuring you pay exclusively
                                    for
                                    results that meet your approval.
                                </p>
                            </div>
                        </x-jobs.benefit-card>

                        <!-- Benefit 2 -->
                        <x-jobs.benefit-card>

                            <div class="w-full md:w-2/5 md:order-2">
                                <img src="{{ asset('images/jobs/undraw_quality_work.svg') }}" alt="Quality Work"
                                    class="w-full h-auto">
                            </div>
                            <div class="w-full md:w-3/5 md:order-1 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">Quality Guaranteed</h3>
                                <p class="text-lg text-neutral-600 text-justify">
                                    ModelHub guarantees that freelance services will align with your specifications and
                                    adhere to deadlines. Our Quality Assurance team is ready to assist you at any time.
                                </p>
                            </div>
                        </x-jobs.benefit-card>

                        <!-- Benefit 3 -->
                        <x-jobs.benefit-card>
                            <x-jobs.benefit-card-image src="images/jobs/undraw_people.svg"
                                alt="Wide Selection of Freelancers" />
                            <div class="w-full md:w-3/5 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">Wide Selection of Freelancers</h3>
                                <p class="text-lg text-neutral-600 text-justify">
                                    Access top-tier 3D artists with proven track records. Review portfolios, ratings,
                                    and
                                    previous client feedback to find the perfect match for your project needs.
                                </p>
                            </div>
                        </x-jobs.benefit-card>
                    </div>

                    <div class="text-center mt-16">
                        <a href="{{ route('jobs.create') }}"
                            class="inline-block px-8 py-4 bg-accent text-white font-semibold rounded-lg shadow-lg hover:bg-amber-600 transition duration-300 ease-in-out">
                            Post Project
                        </a>
                    </div>
                </div>
            </div>

            <!-- How it works Section -->
            <div class="py-16 md:py-24">
                <div class="container mx-auto px-4">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl md:text-4xl font-bold text-primary mb-4">Complete your project in <span
                                class="text-accent">3</span> easy
                            steps</h2>
                    </div>

                    <div class="space-y-16 mt-16 max-w-3xl mx-auto">
                        <!-- Step 1 -->
                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16 relative">
                            <!-- Timeline element -->
                            <div class="hidden md:block absolute h-full w-1 bg-secondary left-[19%] top-0 -z-10"></div>

                            <div class="w-full md:w-2/5 flex justify-center">
                                <div class="relative">
                                    <div
                                        class="absolute -left-4 top-1/2 transform -translate-y-1/2 w-8 h-8 rounded-full bg-secondary text-white flex items-center justify-center font-bold z-10">
                                        1</div>
                                    <img src="{{ asset('images/jobs/undraw_describe.svg') }}" alt="Describe Project"
                                        class="w-full h-auto">
                                </div>
                            </div>
                            <div class="w-full md:w-3/5 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">Describe Your Project</h3>
                                <p class="text-lg text-neutral-600 text-justify">
                                    Tell us what you need. Use our simple form, add images and references, and publish
                                    your
                                    freelance offer in minutes. Be as detailed as possible to attract the right talent.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16 relative">
                            <!-- Timeline element for connected steps -->
                            <div class="hidden md:block absolute h-full w-1 bg-secondary left-[19%] top-0 -z-10"></div>

                            <div class="w-full md:w-2/5 flex justify-center">
                                <div class="relative">
                                    <div
                                        class="absolute -left-4 top-1/2 transform -translate-y-1/2 w-8 h-8 rounded-full bg-secondary text-white flex items-center justify-center font-bold z-10">
                                        2</div>
                                    <img src="{{ asset('images/jobs/undraw_hiring.svg') }}" alt="Select Freelancer"
                                        class="w-full h-auto">
                                </div>
                            </div>
                            <div class="w-full md:w-3/5 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">Select the Best Freelancer</h3>
                                <ul class="text-lg text-neutral-600 list-disc pl-5 text-justify">
                                    <li>Get quotes from experienced designers directly in your inbox</li>
                                    <li>Chat with them in real time</li>
                                    <li>Review their previous projects</li>
                                    <li>Search, filter, and invite freelancers based on 3D expertise, ratings, and your
                                        preferred software or format</li>
                                </ul>

                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16">
                            <div class="w-full md:w-2/5 flex justify-center">
                                <div class="relative">
                                    <div
                                        class="absolute -left-4 top-1/2 transform -translate-y-1/2 w-8 h-8 rounded-full bg-secondary text-white flex items-center justify-center font-bold z-10">
                                        3</div>
                                    <img src="{{ asset('images/jobs/undraw_money.svg') }}"" alt="Pay Securely"
                                        class="w-full h-auto">
                                </div>
                            </div>
                            <div class="w-full md:w-3/5 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">Pay Securely</h3>
                                <p class="text-lg text-neutral-600 text-justify">
                                    Fund your project with our secure escrow system. Money is only released when you
                                    approve
                                    the final deliverables, giving you full control and peace of mind.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-16">
                        <a href="{{ route('jobs.create') }}"
                            class="inline-block px-8 py-4 bg-accent text-white font-semibold rounded-lg shadow-lg hover:bg-amber-600 transition duration-300 ease-in-out">
                            Post Project
                        </a>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="bg-primary text-white py-16">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to get started?</h2>
                    <p class="text-base md:text-lg max-w-7xl mx-auto mb-8">
                        Submit your project at no cost and discover how effortlessly you can solve your next modeling
                        challenge with 3D Projects
                    </p>
                    <a href="{{ route('jobs.create') }}"
                        class="inline-block px-8 py-4 bg-accent text-white font-semibold rounded-lg shadow-lg hover:bg-amber-600 transition duration-300 ease-in-out">
                        Post Project
                    </a>
                </div>
            </div>
        </div>

        <!-- Find a Job Section  -->
        <div id="find-job-content" class="tab-content hidden">
            <!-- Hero Section -->
            <div class="container max-w-7xl mx-auto px-4 py-16">
                <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16">
                    <div class="w-full md:w-1/2 space-y-6">
                        <h1 class="text-4xl md:text-5xl font-bold text-primary leading-tight">
                            Find <span class="text-accent">exciting</span> 3D modeling projects that match your <span
                                class="text-accent">skills</span>
                        </h1>
                        <p class="text-base md:text-lg text-neutral-600 max-w-xl font-secondary text-justify">
                            Showcase your portfolio, connect with clients, and grow your 3D modeling career with
                            projects that value your expertise.
                        </p>
                        <div class="pt-4">
                            <a href="{{ route('jobs.browse') }}"
                                class="inline-block px-8 py-4 bg-accent text-white font-semibold rounded-lg shadow-lg hover:bg-amber-600 transition duration-300 ease-in-out">
                                Find Projects
                            </a>
                        </div>
                    </div>
                    <div class="w-full md:w-1/2 relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-white via-transparent to-white rounded-lg">
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-b from-white via-transparent to-white rounded-lg">
                        </div>
                        <img src="{{ asset('images/jobs/hero-find-job.avif') }}" alt="3D Artist Working"
                            class="w-full h-auto relative z-0">
                    </div>
                </div>
            </div>

            <!-- Benefits/Advantages/Features Section -->
            <div class="bg-neutral-50 py-16 md:py-24">
                <div class="container max-w-7xl mx-auto px-4">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl md:text-4xl font-bold text-primary mb-4">Why <span
                                class="text-accent">freelancers</span> choose ModelHub
                        </h2>
                    </div>

                    <div class="space-y-16 max-w-3xl mx-auto">
                        <!-- Benefit 1 -->
                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16">
                            <div class="w-full md:w-2/5">
                                <img src="{{ asset('images/jobs/undraw_savings.svg') }}" alt="Higher Earnings"
                                    class="w-full h-auto">
                            </div>
                            <div class="w-full md:w-3/5 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">Higher Earnings</h3>
                                <p class="text-lg text-neutral-600 text-justify">
                                    Keep more of what you earn with our industry-leading low commission rates. Set your
                                    own rates and earn what you're worth for your specialized 3D modeling skills.
                                </p>
                            </div>
                        </div>

                        <!-- Benefit 2 -->
                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16">
                            <div class="w-full md:w-2/5 md:order-2">
                                <img src="{{ asset('images/jobs/undraw_slider.svg') }}" alt="Consistent Work"
                                    class="w-full h-auto">
                            </div>
                            <div class="w-full md:w-3/5 md:order-1 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">A single dashboard</h3>
                                <p class="text-lg text-neutral-600 text-justify">
                                    With 3D Projects, you can effortlessly manage your freelance business—sending
                                    proposals directly to your clients' inboxes, tracking each project's status, and
                                    more.
                                </p>
                            </div>
                        </div>

                        <!-- Benefit 3 -->
                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16">
                            <div class="w-full md:w-2/5">
                                <img src="{{ asset('images/jobs/undraw_payments.svg') }}" alt="Protected Payments"
                                    class="w-full h-auto">
                            </div>
                            <div class="w-full md:w-3/5 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">Protected Payments</h3>
                                <p class="text-lg text-neutral-600 text-justify">
                                    Our escrow system ensures you get paid for your work. Funds are secured before you
                                    start, and milestone payments help you maintain steady cash flow throughout
                                    projects.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-16">
                        <a href="#"
                            class="inline-block px-8 py-4 bg-accent text-white font-semibold rounded-lg shadow-lg hover:bg-amber-600 transition duration-300 ease-in-out">
                            Create Portfolio
                        </a>
                    </div>
                </div>
            </div>

            <!-- How it works Section -->
            <div class="py-16 md:py-24">
                <div class="container mx-auto px-4">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl md:text-4xl font-bold text-primary mb-4">How it <span
                                class="text-accent">works</span> for freelancers</h2>
                    </div>

                    <div class="space-y-16 mt-16 max-w-3xl mx-auto">
                        <!-- Step 1 -->
                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16 relative">
                            <!-- Timeline element -->
                            <div class="hidden md:block absolute h-full w-1 bg-secondary left-[19%] top-0 -z-10"></div>

                            <div class="w-full md:w-2/5 flex justify-center">
                                <div class="relative">
                                    <div
                                        class="absolute -left-4 top-1/2 transform -translate-y-1/2 w-8 h-8 rounded-full bg-secondary text-white flex items-center justify-center font-bold z-10">
                                        1</div>
                                    <img src="{{ asset('images/jobs/undraw_add-user.svg') }}" alt="Create Profile"
                                        class="w-full h-auto">
                                </div>
                            </div>
                            <div class="w-full md:w-3/5 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">Create Your Portfolio</h3>
                                <p class="text-lg text-neutral-600 text-justify">
                                    Build a stunning portfolio showcasing your best 3D modeling work. Add your skills,
                                    experience, and set your rates to help clients find you for their projects.
                                </p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16 relative">
                            <!-- Timeline element for connected steps -->
                            <div class="hidden md:block absolute h-full w-1 bg-secondary left-[19%] top-0 -z-10"></div>

                            <div class="w-full md:w-2/5 flex justify-center">
                                <div class="relative">
                                    <div
                                        class="absolute -left-4 top-1/2 transform -translate-y-1/2 w-8 h-8 rounded-full bg-secondary text-white flex items-center justify-center font-bold z-10">
                                        2</div>
                                    <img src="{{ asset('images/jobs/undraw_file-search.svg') }}"
                                        alt="Browse Projects" class="w-full h-auto">
                                </div>
                            </div>
                            <div class="w-full md:w-3/5 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">Browse & Submit Proposals</h3>
                                <p class="text-lg text-neutral-600 text-justify">
                                    Explore available projects that match your expertise or receive personalized project
                                    recommendations. Submit compelling proposals highlighting why you're the perfect
                                    fit.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex flex-col md:flex-row items-center gap-8 md:gap-16">
                            <div class="w-full md:w-2/5 flex justify-center">
                                <div class="relative">
                                    <div
                                        class="absolute -left-4 top-1/2 transform -translate-y-1/2 w-8 h-8 rounded-full bg-secondary text-white flex items-center justify-center font-bold z-10">
                                        3</div>
                                    <img src="{{ asset('images/jobs/undraw_completing.svg') }}" alt="Get Paid"
                                        class="w-full h-auto">
                                </div>
                            </div>
                            <div class="w-full md:w-3/5 space-y-4">
                                <h3 class="text-2xl font-bold text-secondary">Complete Work & Get Paid</h3>
                                <p class="text-lg text-neutral-600 text-justify">
                                    Deliver quality work that exceeds client expectations. Once approved, receive
                                    payment promptly through our secure payment system directly to your preferred
                                    account.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-16">
                        <a href=""
                            class="inline-block px-8 py-4 bg-accent text-white font-semibold rounded-lg shadow-lg hover:bg-amber-600 transition duration-300 ease-in-out">
                            Find Projects
                        </a>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="bg-primary text-white py-16">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to showcase your talent?</h2>
                    <p class="text-base md:text-lg max-w-7xl mx-auto mb-8">
                        Join our community of skilled 3D artists and start earning from projects that value your
                        expertise.
                    </p>
                    <a href="#"
                        class="inline-block px-8 py-4 bg-accent text-white font-semibold rounded-lg shadow-lg hover:bg-amber-600 transition duration-300 ease-in-out">
                        Create Your Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        @include('partials\footer-secondary')
    </div>
</x-app-layout>
