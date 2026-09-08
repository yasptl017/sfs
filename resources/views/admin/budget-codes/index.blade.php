<x-layouts.admin title="Budget Codes | Forest Inventory" heading="Budget Code Management" subheading="All configured budget codes">
    <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
            <div><h2 class="font-semibold">Budget codes</h2></div>
            <div class="flex gap-2">
                <input class="form-input min-h-9 py-1.5" placeholder="Search any budget code detail..." data-budget-code-search>
                <a class="primary-button whitespace-nowrap" href="{{ route('admin.budget-codes.create') }}">Add Budget Code</a>
            </div>
        </div>
        <div class="overflow-x-auto rounded-lg border border-emerald-100 bg-white">
            <table class="data-table compact-data-table min-w-[100rem]">
                <thead>
                    <tr>
                        @foreach(['Budget Code','Operating Head','Scheme','Object Class','Object Code','Description','Item','Model','Scheme Year'] as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budgetCodes as $budgetCode)
                        <tr data-budget-code-row>
                            <td>{{ $budgetCode->budget_code }}</td>
                            <td>{{ $budgetCode->operating_head ?? '-' }}</td>
                            <td>{{ $budgetCode->scheme ?? '-' }}</td>
                            <td>{{ $budgetCode->object_class ?? '-' }}</td>
                            <td>{{ $budgetCode->object_code ?? '-' }}</td>
                            <td>{{ $budgetCode->description ?? '-' }}</td>
                            <td>{{ $budgetCode->item ?? '-' }}</td>
                            <td>{{ $budgetCode->model ?? '-' }}</td>
                            <td>{{ $budgetCode->scheme_year ?? '-' }}</td>
                            <td>
                                <div class="flex flex-nowrap gap-1">
                                    <a class="secondary-button min-h-7 px-1.5 py-0.5 text-[10px]" href="{{ route('admin.budget-codes.edit', $budgetCode) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.budget-codes.destroy', $budgetCode) }}" onsubmit="return confirm('Delete this budget code?')">
                                        @csrf @method('DELETE')
                                        <button class="secondary-button min-h-7 border-red-200 bg-red-50 px-1.5 py-0.5 text-[10px] text-red-700 hover:border-red-300 hover:bg-white">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center">No budget codes available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($budgetCodes->hasPages())
            <div class="border-t border-emerald-100 px-5 py-4">{{ $budgetCodes->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
