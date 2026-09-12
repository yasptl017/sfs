<x-layouts.admin title="Cash Account Details | Forest Inventory" heading="Cash Account Details" subheading="Division recovery and bank deposit entries">
    <div class="space-y-5">
        @if(session('status'))<div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>@endif
        <div class="flex justify-end"><a class="primary-button" href="{{ route('division.cash-accounts.create') }}">Add Cash Account</a></div>
        <div class="overflow-x-auto rounded-lg border border-emerald-100 bg-white"><table class="data-table compact-data-table min-w-[120rem]"><thead><tr>@foreach(['Cash Acc. of Month','Designation','Round / range','Details of Recovery','Designation of Recoverer','Recovery Officer Name','Recovery Type','Recovery Date','Bank Deposite Date','Bank Name & Branch','Challan No.','Amount'] as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead><tbody>@forelse($entries as $entry)<tr>@foreach(['cash_account_month','designation','round_range','recovery_details','recoverer_designation','recovery_officer_name','recovery_type','recovery_date','bank_deposit_date','bank_name_branch','challan_no','amount'] as $field)<td>{{ $field === 'amount' ? number_format((float) ($entry->data[$field] ?? 0), 2) : ($entry->data[$field] ?? '-') }}</td>@endforeach</tr>@empty<tr><td colspan="12" class="text-center">No cash account entries available.</td></tr>@endforelse</tbody></table></div>
        {{ $entries->links() }}
    </div>
</x-layouts.admin>
