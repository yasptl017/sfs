<x-layouts.admin title="Delete Advice | Forest Inventory" heading="Delete Advice" subheading="Division Finance System - Bill / Advice Deletion &amp; Batch Management">
    <div class="space-y-5" id="deleteAdviceModule"
        data-details-url="{{ route('division.delete-advice.details') }}"
        data-destroy-url="{{ route('division.delete-advice.destroy') }}">

        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 text-lg font-bold">&times;</button>
            </div>
        @endif

        <div id="deleteAlertBox" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Delete Advice Form Card (Themed matching Party Registration) -->
        <section class="rounded-lg border border-red-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-red-100 px-5 py-4 flex flex-wrap items-center justify-between gap-3 bg-red-50/30">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Delete Advice</h2>
                    <p class="mt-1 text-sm text-slate-500">Select an Advice No. to review its batch summary and permanently delete it.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Action:</span>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-red-50 px-2.5 py-1 text-xs font-bold text-red-800 border border-red-200">
                        <span class="h-2 w-2 rounded-full bg-red-600"></span>
                        <span>Danger Zone</span>
                    </span>
                </div>
            </div>

            <form id="deleteAdviceForm" method="POST" action="{{ route('division.delete-advice.destroy') }}" class="p-6 space-y-6"
                onsubmit="return confirm('Are you sure you want to permanently delete this Advice? This action cannot be undone.');">
                @csrf
                @method('DELETE')

                <div class="max-w-md mx-auto space-y-4 text-center">
                    <!-- Advice No Dropdown (Matching Reference Image) -->
                    <div class="space-y-2">
                        <label for="advice_no" class="block text-sm font-bold text-blue-700">
                            Advice. No. <span class="text-red-500">*</span>
                        </label>
                        <select id="advice_no" name="advice_no" required
                            class="form-select font-bold text-slate-900 text-center py-2.5 px-4 text-base border-2 border-sky-300 ring-2 ring-sky-50 focus:border-blue-500 bg-white cursor-pointer">
                            <option value="" disabled selected>Choose Advice No...</option>
                            @foreach($adviceNumbers as $aNo)
                                <option value="{{ $aNo }}" @selected(old('advice_no') == $aNo)>{{ $aNo }}</option>
                            @endforeach
                        </select>
                        @error('advice_no')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Live Advice Details Preview Card -->
                    <div id="adviceDetailsPreview" class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4 text-left space-y-2 text-xs">
                        <div class="flex justify-between border-b border-slate-200 pb-1.5 font-bold text-slate-900">
                            <span>Advice Batch:</span>
                            <span id="prevAdviceNo" class="text-blue-700 font-extrabold">-</span>
                        </div>
                        <div class="flex justify-between text-slate-700">
                            <span>Advice Date:</span>
                            <span id="prevAdviceDate" class="font-medium">-</span>
                        </div>
                        <div class="flex justify-between text-slate-700">
                            <span>Bill Type:</span>
                            <span id="prevBillType" class="font-medium">-</span>
                        </div>
                        <div class="flex justify-between text-slate-700">
                            <span>Bills in Batch:</span>
                            <span id="prevBillCount" class="font-semibold text-slate-900">-</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-1.5 font-bold text-slate-950 text-sm">
                            <span>Total Batch Amount:</span>
                            <span id="prevTotalAmount" class="text-emerald-800 font-extrabold">₹ 0.00</span>
                        </div>
                    </div>

                    <!-- Confirmation Notice -->
                    <div class="p-3 bg-red-50/80 rounded-lg border border-red-200 text-xs text-red-800 text-left leading-relaxed">
                        <p class="font-bold flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>Warning:</span>
                        </p>
                        <p class="mt-1">
                            Deleting this Advice will remove the processed advice batch and unbind its associated voucher bills so they can be re-processed if needed.
                        </p>
                    </div>

                    <!-- Action Button -->
                    <div class="pt-2 flex justify-center gap-3">
                        <button type="submit" id="btnDeleteAdvice"
                            class="primary-button bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md shadow-sm active:scale-95 transition-all">
                            Delete Advice
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <!-- Section 2: Active Advices Management Table (Matching Party Registration Theme) -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-100 px-5 py-4 gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Active Advice Batches</h2>
                    <p class="mt-1 text-sm text-slate-500">Review all generated advice batches and manage deletions</p>
                </div>
                <div class="w-full sm:w-72">
                    <input type="search" id="adviceSearchInput" placeholder="Search advice batches..." class="form-input min-h-9 text-xs py-1.5">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="w-14 text-center">Sr. No.</th>
                            <th>Advice No.</th>
                            <th>Advice Date</th>
                            <th>Bill Type</th>
                            <th class="text-center">Bills Count</th>
                            <th class="text-right">Total Amount (₹)</th>
                            <th>Status</th>
                            <th>Generated At</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="adviceTableBody" class="divide-y divide-slate-100">
                        @forelse($advices as $index => $adv)
                            <tr data-advice-row class="transition-colors hover:bg-emerald-50/40">
                                <td class="text-center font-medium text-slate-600">{{ $advices->firstItem() + $index }}</td>
                                <td class="font-bold text-blue-700">{{ $adv->advice_no }}</td>
                                <td class="text-slate-800">{{ $adv->advice_date->format('d M Y') }}</td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-800">
                                        {{ $adv->bill_type }}
                                    </span>
                                </td>
                                <td class="text-center font-bold text-slate-900">{{ $adv->total_bills_count }} bills</td>
                                <td class="font-bold text-slate-950 text-right">₹ {{ number_format($adv->total_amount, 2) }}</td>
                                <td>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        {{ $adv->status }}
                                    </span>
                                </td>
                                <td class="text-xs text-slate-500">{{ $adv->created_at->format('d M Y, h:i A') }}</td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('division.delete-advice.destroy-model', $adv) }}" onsubmit="return confirm('Are you sure you want to permanently delete Advice {{ $adv->advice_no }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="secondary-button min-h-7 border-red-200 bg-red-50 px-2.5 py-0.5 text-xs text-red-700 hover:border-red-300 hover:bg-white">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr id="noAdvicesRow">
                                <td colspan="9" class="text-center py-10 text-slate-500">
                                    No active advice batches found in this division.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($advices->hasPages())
                <div class="border-t border-emerald-100 px-5 py-4">
                    {{ $advices->links() }}
                </div>
            @endif
        </section>
    </div>

    <!-- Dynamic JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('deleteAdviceForm');
            const adviceSelect = document.getElementById('advice_no');
            const previewCard = document.getElementById('adviceDetailsPreview');
            const prevAdviceNo = document.getElementById('prevAdviceNo');
            const prevAdviceDate = document.getElementById('prevAdviceDate');
            const prevBillType = document.getElementById('prevBillType');
            const prevBillCount = document.getElementById('prevBillCount');
            const prevTotalAmount = document.getElementById('prevTotalAmount');
            const searchInput = document.getElementById('adviceSearchInput');

            const formatMoney = (amount) => {
                return '₹ ' + Number(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            const fetchAdviceDetails = async () => {
                const adviceNo = adviceSelect.value;
                if (!adviceNo) {
                    previewCard.classList.add('hidden');
                    return;
                }

                try {
                    const url = new URL(document.getElementById('deleteAdviceModule').dataset.detailsUrl, window.location.origin);
                    url.searchParams.set('advice_no', adviceNo);

                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        prevAdviceNo.textContent = data.advice_no;
                        prevAdviceDate.textContent = data.advice_date;
                        prevBillType.textContent = data.bill_type;
                        prevBillCount.textContent = `${data.total_bills_count} bills`;
                        prevTotalAmount.textContent = formatMoney(data.total_amount);
                        previewCard.classList.remove('hidden');
                    }
                } catch (e) {
                    console.error('Error loading advice details:', e);
                }
            };

            adviceSelect.addEventListener('change', fetchAdviceDetails);

            if (adviceSelect.value) {
                fetchAdviceDetails();
            }

            // Live Search Filter
            searchInput?.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('[data-advice-row]');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</x-layouts.admin>
