<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Dispute Payment</h1>
                <p class="text-gray-600 mt-2">
                    For payment amount: Ksh{{ number_format($payment->amount, 2) }} for job
                    "{{ $engagement->application->job->title }}"
                </p>
            </div>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Once submitted, this dispute will be reviewed by platform administrators. Please provide
                            accurate and detailed information.
                        </p>
                    </div>
                </div>
            </div>

            <form action="{{ route('engagements.process-dispute-partial-payment', $payment->id) }}" method="POST"
                enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700">Reason for Dispute</label>
                    <select id="reason" name="reason"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                        required>
                        <option value="">Select a reason</option>
                        <option value="incorrect_amount">Incorrect Amount</option>
                        <option value="work_not_considered">Work Completed Not Considered</option>
                        <option value="agreement_violated">Agreement Terms Violated</option>
                        <option value="cancellation_dispute">Cancellation Terms Dispute</option>
                        <option value="other">Other (please specify)</option>
                    </select>
                    @error('reason')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="details" class="block text-sm font-medium text-gray-700">Detailed Explanation</label>
                    <textarea id="details" name="details" rows="6"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Please provide a detailed explanation of your dispute, including any relevant timeline, agreements, or information that supports your case."
                        required>{{ old('details') }}</textarea>
                    @error('details')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="evidence" class="block text-sm font-medium text-gray-700">Supporting Evidence
                        (Optional)</label>
                    <div
                        class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                viewBox="0 0 48 48" aria-hidden="true">
                                <path
                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="file-upload"
                                    class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>Upload a file</span>
                                    <input id="file-upload" name="evidence" type="file" class="sr-only">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">
                                PNG, JPG, PDF, DOC up to 10MB
                            </p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Recommended: screenshots, communication logs, delivered work
                        samples, or any other relevant documentation.</p>
                    @error('evidence')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input id="acknowledgment" name="acknowledgment" type="checkbox"
                            class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" required>
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="acknowledgment" class="font-medium text-gray-700">I acknowledge that</label>
                        <p class="text-gray-500">I have provided accurate information, and I understand that filing
                            frivolous disputes may affect my account standing.</p>
                    </div>
                </div>

                <div class="flex justify-between">
                    <a href="{{ route('engagements.show-cancelled', $engagement->id) }}"
                        class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit"
                        class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Submit Dispute
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
