<x-layouts.admin title="Tender Entries | Forest Inventory" heading="Tender Entry" subheading="All voucher tender entries for this range">
    <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <input class="form-input min-h-9 py-1.5 sm:max-w-sm" placeholder="Search any entry detail..." data-tender-entry-search>
            <a class="primary-button whitespace-nowrap sm:ml-auto" href="{{ route('finance.tender-entries.create') }}">Add Tender Entry</a>
        </div>
        <div class="overflow-x-auto rounded-lg border border-emerald-100 bg-white">
            <table class="data-table compact-data-table min-w-[140rem]">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        @foreach(['entry_type'=>'Entry Type','tender_code'=>'Tender Code','budget_code'=>'Budget Code','scheme'=>'Scheme','scheme_year'=>'Scheme Year','data_entry_month'=>'Data Entry Month','docket_no'=>'Docket No.','round'=>'Round','beat'=>'Beat','place'=>'Place','bill_no'=>'Bill No.','bill_date'=>'Bill Date','party_code'=>'Party Code','party_name'=>'Party Name','total_amount'=>'Total Amount','net_amount'=>'Net Amount'] as $k=>$l)
                            <th>{{ $l }}</th>
                        @endforeach
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr data-tender-entry-row>
                            <td>{{ $entry->serial_number }}</td>
                            @foreach(['entry_type','tender_code','budget_code','scheme','scheme_year','data_entry_month','docket_no','round','beat','place','bill_no','bill_date','party_code','party_name','total_amount','net_amount'] as $k)
                                <td>{{ $entry->data[$k] ?? '-' }}</td>
                            @endforeach
                            <td>
                                <div class="flex flex-nowrap gap-1">
                                    <a class="secondary-button min-h-7 px-1.5 py-0.5 text-[10px]" href="{{ route('finance.tender-entries.edit', $entry) }}">Edit</a>
                                    <form method="POST" action="{{ route('finance.tender-entries.destroy', $entry) }}" onsubmit="return confirm('Delete this tender entry?')">
                                        @csrf @method('DELETE')
                                        <button class="secondary-button min-h-7 border-red-200 bg-red-50 px-1.5 py-0.5 text-[10px] text-red-700 hover:border-red-300 hover:bg-white">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="18" class="text-center">No tender entries available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="border-t border-emerald-100 px-5 py-4">{{ $entries->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
