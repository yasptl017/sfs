<x-layouts.admin title="Update GST Challan No. | Forest Inventory" heading="Update GST Challan No." subheading="Division Finance System - Bill &amp; Advice GST Challan Management">
    <div class="space-y-5">
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

        <div id="gstChallanAlert" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Update GST Challan Form (Matching Party Registration Theme) -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Update GST Challan Details</h2>
                    <p class="mt-1 text-sm text-slate-500">Select Bill Register No. and Advice No. to update GST Challan number and GST amount.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Module:</span>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 border border-emerald-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                        <span>GST Challan</span>
                    </span>
                </div>
            </div>

            <form id="gstChallanForm" method="POST" action="{{ route('division.gst-challan.store') }}" class="p-5 space-y-5"
                data-details-url="{{ route('division.gst-challan.details') }}"
                data-bill-advice-map="{{ json_encode($billAdviceMap) }}">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Bill Register No -->
                    <div>
                        <label for="bill_register_no" class="form-label">
                            Bill Register No. <span class="text-red-500">*</span>
                        </label>
                        <select id="bill_register_no" name="bill_register_no" required class="form-select font-medium text-slate-900">
                            <option value="" disabled selected>Choose Bill Register No...</option>
                            @foreach($billRegisterNumbers as $bNo)
                                <option value="{{ $bNo }}" @selected(old('bill_register_no') == $bNo)>{{ $bNo }}</option>
                            @endforeach
                        </select>
                        @error('bill_register_no')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Advice No -->
                    <div>
                        <label for="advice_no" class="form-label">
                            Advice No. <span class="text-red-500">*</span>
                        </label>
                        <select id="advice_no" name="advice_no" required class="form-select font-medium text-slate-900">
                            <option value="" disabled selected>Choose Advice No...</option>
                            @foreach($adviceNumbers as $aNo)
                                <option value="{{ $aNo }}" @selected(old('advice_no') == $aNo)>{{ $aNo }}</option>
                            @endforeach
                        </select>
                        @error('advice_no')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- GST Amount -->
                    <div>
                        <label for="gst_amount" class="form-label">
                            GST Amount (₹) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" id="gst_amount" name="gst_amount" required
                                value="{{ old('gst_amount') }}" placeholder="0.00"
                                class="form-input font-bold text-slate-900">
                        </div>
                        @error('gst_amount')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- GST Challan No -->
                    <div>
                        <label for="gst_challan_no" class="form-label">
                            GST Challan No. <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="gst_challan_no" name="gst_challan_no" required
                            value="{{ old('gst_challan_no') }}" placeholder="Enter Challan No."
                            class="form-input font-medium text-slate-900">
                        @error('gst_challan_no')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex flex-wrap items-center justify-end gap-3 pt-3 border-t border-emerald-50">
                    <button type="button" id="resetFormBtn" class="secondary-button">
                        Reset Form
                    </button>
                    <button type="submit" id="submitBtn" class="primary-button flex items-center gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Update GST Challan</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- Section 2: Recent Records Table (Matching Party Registration Table Theme) -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-100 px-5 py-4 gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Updated GST Challan Records</h2>
                    <p class="mt-1 text-sm text-slate-500">History of Bill Register and Advice GST Challan Numbers</p>
                </div>
                <div class="w-full sm:w-72">
                    <input type="search" id="recordSearch" placeholder="Search records..." class="form-input min-h-9 text-xs py-1.5">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">Sr. No.</th>
                            <th>Bill Register No.</th>
                            <th>Advice No.</th>
                            <th class="text-right">GST Amount (₹)</th>
                            <th>GST Challan No.</th>
                            <th>Updated At</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="recordsTableBody" class="divide-y divide-slate-100">
                        @forelse($records as $index => $record)
                            <tr data-record-row class="transition-colors hover:bg-emerald-50/40">
                                <td class="text-center font-medium text-slate-600">{{ $records->firstItem() + $index }}</td>
                                <td class="font-semibold text-slate-900">{{ $record->bill_register_no }}</td>
                                <td class="font-semibold text-slate-800">{{ $record->advice_no }}</td>
                                <td class="font-bold text-slate-950 text-right">₹ {{ number_format($record->gst_amount, 2) }}</td>
                                <td>
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 border border-emerald-200">
                                        {{ $record->gst_challan_no }}
                                    </span>
                                </td>
                                <td class="text-xs text-slate-500">{{ $record->updated_at->format('d M Y, h:i A') }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" 
                                            class="secondary-button min-h-7 px-2.5 py-0.5 text-xs"
                                            onclick="populateForm('{{ $record->bill_register_no }}', '{{ $record->advice_no }}', '{{ $record->gst_amount }}', '{{ $record->gst_challan_no }}')">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('division.gst-challan.destroy', $record) }}" onsubmit="return confirm('Are you sure you want to delete this GST Challan record?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="secondary-button min-h-7 border-red-200 bg-red-50 px-2.5 py-0.5 text-xs text-red-700 hover:border-red-300 hover:bg-white">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="noRecordsRow">
                                <td colspan="7" class="text-center py-10 text-slate-500">
                                    No GST Challan records updated yet. Use the form above to update challan details.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($records->hasPages())
                <div class="border-t border-emerald-100 px-5 py-4">
                    {{ $records->links() }}
                </div>
            @endif
        </section>
    </div>

    <!-- Dynamic Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('gstChallanForm');
            const alertBox = document.getElementById('gstChallanAlert');
            const billSelect = document.getElementById('bill_register_no');
            const adviceSelect = document.getElementById('advice_no');
            const gstAmountInput = document.getElementById('gst_amount');
            const gstChallanNoInput = document.getElementById('gst_challan_no');
            const resetFormBtn = document.getElementById('resetFormBtn');
            const searchInput = document.getElementById('recordSearch');
            
            let billAdviceMap = {};
            try {
                billAdviceMap = JSON.parse(form.dataset.billAdviceMap || '{}');
            } catch (e) {
                billAdviceMap = {};
            }

            const showAlert = (message, type = 'success') => {
                alertBox.textContent = message;
                alertBox.className = type === 'success'
                    ? 'rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 block shadow-sm'
                    : 'rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 block shadow-sm';
                
                alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            };

            const fetchGstDetails = async () => {
                const billNo = billSelect.value;
                const adviceNo = adviceSelect.value;

                if (!billNo && !adviceNo) return;

                // Check local map first
                if (billNo && billAdviceMap[billNo]) {
                    const match = billAdviceMap[billNo].find(item => !adviceNo || item.advice_no === adviceNo);
                    if (match) {
                        if (match.gst_amount !== undefined && match.gst_amount !== null) {
                            gstAmountInput.value = Number(match.gst_amount).toFixed(2);
                        }
                        if (match.gst_challan_no) {
                            gstChallanNoInput.value = match.gst_challan_no;
                        }
                    }
                }

                // Call details endpoint
                try {
                    const url = new URL(form.dataset.detailsUrl, window.location.origin);
                    if (billNo) url.searchParams.set('bill_register_no', billNo);
                    if (adviceNo) url.searchParams.set('advice_no', adviceNo);

                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.gst_amount !== undefined && data.gst_amount !== null) {
                            gstAmountInput.value = Number(data.gst_amount).toFixed(2);
                        }
                        if (data.gst_challan_no) {
                            gstChallanNoInput.value = data.gst_challan_no;
                        }
                    }
                } catch (error) {
                    console.error('Error fetching details:', error);
                }
            };

            billSelect.addEventListener('change', function () {
                const selectedBill = billSelect.value;
                if (selectedBill && billAdviceMap[selectedBill] && billAdviceMap[selectedBill].length > 0) {
                    const firstAdvice = billAdviceMap[selectedBill][0].advice_no;
                    if (firstAdvice) {
                        adviceSelect.value = firstAdvice;
                    }
                }
                fetchGstDetails();
            });

            adviceSelect.addEventListener('change', fetchGstDetails);

            resetFormBtn?.addEventListener('click', function () {
                form.reset();
                gstAmountInput.value = '';
                gstChallanNoInput.value = '';
                alertBox.className = 'hidden';
            });

            // Table Search Filter
            searchInput?.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('[data-record-row]');
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

            window.populateForm = function (billNo, adviceNo, amount, challanNo) {
                billSelect.value = billNo;
                adviceSelect.value = adviceNo;
                gstAmountInput.value = Number(amount || 0).toFixed(2);
                gstChallanNoInput.value = challanNo || '';
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };
        });
    </script>
</x-layouts.admin>
