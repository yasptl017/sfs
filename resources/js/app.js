const shell = document.getElementById('adminShell');
const toggle = document.getElementById('sidebarToggle');
const shade = document.getElementById('mobileShade');

if (shell && toggle) {
    const desktopQuery = window.matchMedia('(min-width: 768px)');

    if (localStorage.getItem('forest-sidebar') === 'collapsed' && desktopQuery.matches) {
        shell.classList.add('is-collapsed');
    }

    const closeMobile = () => shell.classList.remove('mobile-open');

    toggle.addEventListener('click', () => {
        if (desktopQuery.matches) {
            shell.classList.toggle('is-collapsed');
            localStorage.setItem(
                'forest-sidebar',
                shell.classList.contains('is-collapsed') ? 'collapsed' : 'expanded',
            );
            return;
        }

        shell.classList.toggle('mobile-open');
    });

    shade?.addEventListener('click', closeMobile);

    desktopQuery.addEventListener('change', (event) => {
        shell.classList.remove('mobile-open');

        if (!event.matches) {
            shell.classList.remove('is-collapsed');
        } else if (localStorage.getItem('forest-sidebar') === 'collapsed') {
            shell.classList.add('is-collapsed');
        }
    });
}

const partyForm = document.querySelector('[data-party-registration-form]');

if (partyForm) {
    const alert = document.getElementById('partyFormAlert');
    const copyInput = document.getElementById('copy_party_sr_no');
    const copyButton = partyForm.querySelector('[data-copy-party-entry]');
    const editToggle = partyForm.querySelector('[data-edit-copied-entry]');
    const fields = [...partyForm.querySelectorAll('[data-party-field]')];
    const serialField = document.getElementById('party_sr_no');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let copiedMode = false;

    const showAlert = (message, type = 'success') => {
        alert.textContent = message;
        alert.className = type === 'success'
            ? 'rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800'
            : 'rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800';
    };

    const setValue = (field, value) => {
        if (field.type === 'radio') {
            field.checked = field.value === value;
            return;
        }

        field.value = value ?? '';
    };

    const setCopiedFieldState = () => {
        if (!copiedMode) {
            fields.forEach((field) => field.disabled = false);
            return;
        }

        fields.forEach((field) => {
            field.disabled = field !== serialField && !editToggle.checked;
        });
    };

    const clearForm = (nextSerial = serialField.value) => {
        fields.forEach((field) => {
            field.disabled = false;

            if (field.type === 'radio') {
                field.checked = field.value === 'Active';
                return;
            }

            field.value = '';
        });

        serialField.value = nextSerial;
        copiedMode = false;
        editToggle.checked = false;
    };

    editToggle.addEventListener('change', setCopiedFieldState);

    copyButton.addEventListener('click', async () => {
        const copyId = copyInput.value.trim();

        if (!copyId) {
            showAlert('Enter Sr. No. before copying entry.', 'error');
            return;
        }

        try {
            const response = await fetch(`${partyForm.dataset.copyUrl}/${encodeURIComponent(copyId)}`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                showAlert('No party found in database for this Sr. No.', 'error');
                return;
            }

            const { party } = await response.json();

            fields.forEach((field) => {
                setValue(field, party[field.name]);
            });

            copiedMode = true;
            setCopiedFieldState();
            showAlert('Copied entry loaded from database. Turn on Edit copied entry to modify fields.');
        } catch (error) {
            showAlert('Copy failed. Please try again.', 'error');
        }
    });

    partyForm.addEventListener('reset', (event) => {
        event.preventDefault();
        clearForm();
        alert.className = 'hidden rounded-lg border px-4 py-3 text-sm font-semibold';
        alert.textContent = '';
    });

    partyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        fields.forEach((field) => field.disabled = false);

        try {
            const response = await fetch(partyForm.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(partyForm),
            });

            const result = await response.json();

            if (!response.ok) {
                const message = result.message || Object.values(result.errors || {})[0]?.[0] || 'Please check the form and try again.';
                showAlert(message, 'error');
                return;
            }

            clearForm(result.next_serial);
            showAlert(`Success: Notification - ${result.message}`);
        } catch (error) {
            showAlert('Submit failed. Please try again.', 'error');
        }
    });
}

const partyTableSearch = document.querySelector('[data-party-table-search]');

partyTableSearch?.addEventListener('input', () => {
    const query = partyTableSearch.value.trim().toLocaleLowerCase();

    document.querySelectorAll('[data-party-table-row]').forEach((row) => {
        row.hidden = query !== '' && !row.textContent.toLocaleLowerCase().includes(query);
    });
});
const wlBeneficiaryForm = document.querySelector('[data-wl-beneficiary-form]');

if (wlBeneficiaryForm?.hasAttribute('data-wl-copy-mode')) {
    const editToggle = wlBeneficiaryForm.querySelector('[data-wl-edit-copied-entry]');
    const editableFields = [...wlBeneficiaryForm.querySelectorAll('input:not([type="hidden"]):not([type="file"]):not([readonly]), select, textarea')];

    const setWlCopiedFieldState = () => {
        editableFields.forEach((field) => field.disabled = !editToggle.checked);
    };

    setWlCopiedFieldState();
    editToggle.addEventListener('change', setWlCopiedFieldState);
    wlBeneficiaryForm.addEventListener('submit', () => editableFields.forEach((field) => field.disabled = false));
}
const wlBeneficiarySearch = document.querySelector('[data-wl-beneficiary-search]');

wlBeneficiarySearch?.addEventListener('input', () => {
    const query = wlBeneficiarySearch.value.trim().toLocaleLowerCase();

    document.querySelectorAll('[data-wl-beneficiary-row]').forEach((row) => {
        row.hidden = query !== '' && !row.textContent.toLocaleLowerCase().includes(query);
    });
});
const sfSearch = document.querySelector('[data-sf-search]'); sfSearch?.addEventListener('input', () => { const q = sfSearch.value.toLocaleLowerCase(); document.querySelectorAll('[data-sf-row]').forEach((row) => row.hidden = q !== '' && !row.textContent.toLocaleLowerCase().includes(q)); });

const dps=document.querySelector('[data-division-party-search]');dps?.addEventListener('input',()=>{const q=dps.value.toLowerCase();document.querySelectorAll('[data-division-party-row]').forEach(r=>r.hidden=q!==''&&!r.textContent.toLowerCase().includes(q));});

const budgetCodeSearch=document.querySelector('[data-budget-code-search]');budgetCodeSearch?.addEventListener('input',()=>{const q=budgetCodeSearch.value.toLowerCase();document.querySelectorAll('[data-budget-code-row]').forEach(r=>r.hidden=q!==''&&!r.textContent.toLowerCase().includes(q));});

const divisionPartyForm = document.querySelector('[data-division-party-form]');

if (divisionPartyForm) {
    const alert = document.getElementById('divisionPartyFormAlert');
    const copyInput = document.getElementById('copy_division_party_sr_no');
    const copyButton = divisionPartyForm.querySelector('[data-copy-party-entry]');
    const editToggle = divisionPartyForm.querySelector('[data-edit-copied-entry]');
    const fields = [...divisionPartyForm.querySelectorAll('[data-party-field]')];
    const serialField = document.getElementById('party_sr_no');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let copiedMode = false;

    const fieldKey = (field) => {
        const match = field.name.match(/^data\[(.+)\]$/);
        return match ? match[1] : field.name;
    };

    let roundsByRange = {};
    try {
        roundsByRange = JSON.parse(divisionPartyForm.dataset.roundsByRange || '{}');
    } catch (error) {
        roundsByRange = {};
    }

    const rangeSelect = divisionPartyForm.querySelector('[data-range-select]');
    const roundSelect = divisionPartyForm.querySelector('[data-round-select]');
    const tenderTypeField = divisionPartyForm.querySelector('[data-tender-type]');
    const tenderModeField = divisionPartyForm.querySelector('[data-tender-mode]');
    const tenderNoField = divisionPartyForm.querySelector('[data-tender-no]');
    const tenderCodeField = divisionPartyForm.querySelector('[data-tender-code]');
    const partyCodeField = divisionPartyForm.querySelector('[data-party-code]');

    const tenderTypeInitials = (type) => {
        if (!type) return '';
        if (type === 'SOR') return 'SOR';

        return type.split(' ').map((word) => word.charAt(0)).join('').toUpperCase();
    };

    const refreshRoundOptions = (selectedRound = '') => {
        if (!rangeSelect || !roundSelect) return;

        const rounds = roundsByRange[rangeSelect.value] || [];
        if (selectedRound && !rounds.includes(selectedRound)) {
            rounds.push(selectedRound);
        }

        roundSelect.innerHTML = '<option value="" disabled>Choose...</option>'
            + rounds.map((round) => `<option ${round === selectedRound ? 'selected' : ''}>${round}</option>`).join('');
    };

    const refreshTenderAndPartyCode = () => {
        if (!tenderCodeField) return;

        const prefix = tenderTypeInitials(tenderTypeField?.value) + (tenderModeField?.value?.charAt(0).toUpperCase() || '');
        const tenderCode = tenderTypeField?.value && tenderModeField?.value && tenderNoField?.value
            ? `${prefix}${tenderNoField.value}`
            : '';

        tenderCodeField.value = tenderCode;

        if (partyCodeField) {
            partyCodeField.value = tenderCode && serialField?.value ? `${tenderCode}/${serialField.value}` : '';
        }
    };

    rangeSelect?.addEventListener('change', () => refreshRoundOptions());
    [tenderTypeField, tenderModeField, tenderNoField].forEach((field) => {
        field?.addEventListener('input', refreshTenderAndPartyCode);
        field?.addEventListener('change', refreshTenderAndPartyCode);
    });

    refreshRoundOptions(roundSelect?.value);
    refreshTenderAndPartyCode();

    const showAlert = (message, type = 'success') => {
        alert.textContent = message;
        alert.className = type === 'success'
            ? 'rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800'
            : 'rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800';
    };

    const setValue = (field, value) => {
        if (field.type === 'radio') {
            field.checked = field.value === value;
            return;
        }

        field.value = value ?? '';
    };

    const setCopiedFieldState = () => {
        if (!copiedMode) {
            fields.forEach((field) => field.disabled = false);
            return;
        }

        fields.forEach((field) => {
            field.disabled = field !== serialField && !editToggle.checked;
        });
    };

    const clearForm = (nextSerial = serialField.value) => {
        fields.forEach((field) => {
            field.disabled = false;

            if (field.type === 'radio') {
                field.checked = field.value === 'Active';
                return;
            }

            field.value = '';
        });

        serialField.value = nextSerial;
        copiedMode = false;
        if (editToggle) editToggle.checked = false;
        refreshRoundOptions();
        refreshTenderAndPartyCode();
    };

    editToggle?.addEventListener('change', setCopiedFieldState);

    copyButton?.addEventListener('click', async () => {
        const copyId = copyInput.value.trim();

        if (!copyId) {
            showAlert('Enter Sr. No. before copying entry.', 'error');
            return;
        }

        try {
            const response = await fetch(`${divisionPartyForm.dataset.copyUrl}/${encodeURIComponent(copyId)}`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                showAlert('No party found in database for this Sr. No.', 'error');
                return;
            }

            const { party } = await response.json();

            fields.forEach((field) => {
                if (field === roundSelect) return;
                setValue(field, party[fieldKey(field)]);
            });

            refreshRoundOptions(party.round);
            refreshTenderAndPartyCode();

            copiedMode = true;
            setCopiedFieldState();
            showAlert('Copied entry loaded from database. Turn on Edit copied entry to modify fields.');
        } catch (error) {
            showAlert('Copy failed. Please try again.', 'error');
        }
    });

    divisionPartyForm.addEventListener('reset', (event) => {
        event.preventDefault();
        clearForm();
        alert.className = 'hidden rounded-lg border px-4 py-3 text-sm font-semibold';
        alert.textContent = '';
    });

    divisionPartyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        fields.forEach((field) => field.disabled = false);

        try {
            const response = await fetch(divisionPartyForm.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(divisionPartyForm),
            });

            const result = await response.json();

            if (!response.ok) {
                const message = result.message || Object.values(result.errors || {})[0]?.[0] || 'Please check the form and try again.';
                showAlert(message, 'error');
                return;
            }

            if (result.redirect_url) {
                window.location.href = result.redirect_url;
                return;
            }

            clearForm(result.next_serial);
            showAlert(`Success: Notification - ${result.message}`);
        } catch (error) {
            showAlert('Submit failed. Please try again.', 'error');
        }
    });
}

function initEntrySearch(searchSelector, rowSelector) {
    const search = document.querySelector(searchSelector);
    search?.addEventListener('input', () => {
        const query = search.value.toLowerCase();
        document.querySelectorAll(rowSelector).forEach((row) => {
            row.hidden = query !== '' && !row.textContent.toLowerCase().includes(query);
        });
    });
}

function initVoucherForm(form, options) {
    if (!form) return;

    const { alertId, serialFieldId, copyInputId, buildItemRow, deductionSelector } = options;
    const alert = document.getElementById(alertId);
    const copyInput = document.getElementById(copyInputId);
    const copyButton = form.querySelector('[data-copy-entry]');
    const editToggle = form.querySelector('[data-edit-copied-entry]');
    const fields = [...form.querySelectorAll('[data-entry-field]')];
    const serialField = document.getElementById(serialFieldId);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let copiedMode = false;

    let budgetCodes = {};
    let beatsByRound = {};
    let placesByBeat = {};
    try { budgetCodes = JSON.parse(form.dataset.budgetCodes || '{}'); } catch (e) { budgetCodes = {}; }
    try { beatsByRound = JSON.parse(form.dataset.beatsByRound || '{}'); } catch (e) { beatsByRound = {}; }
    try { placesByBeat = JSON.parse(form.dataset.placesByBeat || '{}'); } catch (e) { placesByBeat = {}; }

    const budgetCodeSelect = form.querySelector('[data-budget-code-select]');
    const schemeField = form.querySelector('[data-scheme-field]');
    const modelField = form.querySelector('[data-model-field]');
    const schemeYearField = form.querySelector('[data-scheme-year-field]');
    const roundSelect = form.querySelector('[data-round-select]');
    const beatSelect = form.querySelector('[data-beat-select]');
    const placeSelect = form.querySelector('[data-place-select]');

    const fieldKey = (field) => {
        const match = field.name.match(/^data\[(.+)\]$/);
        return match ? match[1] : field.name;
    };

    const showAlert = (message, type = 'success') => {
        alert.textContent = message;
        alert.className = type === 'success'
            ? 'rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800'
            : 'rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800';
    };

    const setValue = (field, value) => {
        if (field.type === 'radio') {
            field.checked = field.value === value;
            return;
        }

        field.value = value ?? '';
    };

    const applyBudgetCode = () => {
        const details = budgetCodes[budgetCodeSelect?.value];
        if (schemeField) schemeField.value = details?.scheme ?? '';
        if (modelField) modelField.value = details?.model ?? '';
        if (schemeYearField) schemeYearField.value = details?.scheme_year ?? '';
    };

    const refreshBeatOptions = (selectedBeat = '') => {
        if (!roundSelect || !beatSelect) return;

        const beats = [...(beatsByRound[roundSelect.value] || [])];
        if (selectedBeat && !beats.includes(selectedBeat)) beats.push(selectedBeat);

        beatSelect.innerHTML = '<option value="" disabled>Choose...</option>'
            + beats.map((beat) => `<option ${beat === selectedBeat ? 'selected' : ''}>${beat}</option>`).join('');
    };

    const refreshPlaceOptions = (selectedPlace = '') => {
        if (!beatSelect || !placeSelect) return;

        const places = [...(placesByBeat[beatSelect.value] || [])];
        if (selectedPlace && !places.includes(selectedPlace)) places.push(selectedPlace);

        placeSelect.innerHTML = '<option value="" disabled>Choose...</option>'
            + places.map((place) => `<option ${place === selectedPlace ? 'selected' : ''}>${place}</option>`).join('');
    };

    budgetCodeSelect?.addEventListener('change', applyBudgetCode);
    roundSelect?.addEventListener('change', () => { refreshBeatOptions(); refreshPlaceOptions(); });
    beatSelect?.addEventListener('change', () => refreshPlaceOptions());

    refreshBeatOptions(beatSelect?.value);
    refreshPlaceOptions(placeSelect?.value);

    const num = (value) => parseFloat(value) || 0;

    const recalculateTotals = () => {
        const itemRows = [...form.querySelectorAll('[data-item-row]')];
        let subTotal = 0;

        itemRows.forEach((row) => {
            const qty = num(row.querySelector('[data-item-qty]')?.value);
            const rate = num(row.querySelector('[data-item-rate]')?.value);
            const amount = qty * rate;
            const amountField = row.querySelector('[data-item-amount]');
            if (amountField) amountField.value = amount.toFixed(2);
            subTotal += amount;
        });

        const subTotalField = form.querySelector('[data-sub-total]');
        if (subTotalField) subTotalField.value = subTotal.toFixed(2);

        const approvedPercentField = form.querySelector('[data-approved-percent]');
        const approvedPercent = approvedPercentField ? num(approvedPercentField.value) : 100;
        const approvedAmount = subTotal * (approvedPercent / 100);
        const approvedAmountField = form.querySelector('[data-approved-amount]');
        if (approvedAmountField) approvedAmountField.value = approvedAmount.toFixed(2);

        const additions = [...form.querySelectorAll('[data-total-input]')]
            .reduce((sum, field) => sum + num(field.value), 0);
        const totalAmount = approvedAmount + additions;
        const totalAmountField = form.querySelector('[data-total-amount]');
        if (totalAmountField) totalAmountField.value = totalAmount.toFixed(2);

        const deductionFields = [...form.querySelectorAll(deductionSelector)];
        const totalDeduction = deductionFields.reduce((sum, field) => sum + num(field.value), 0);
        const totalDeductionField = form.querySelector('[data-total-deduction]');
        if (totalDeductionField) totalDeductionField.value = totalDeduction.toFixed(2);

        const netAmountField = form.querySelector('[data-net-amount]');
        if (netAmountField) netAmountField.value = (totalAmount - totalDeduction).toFixed(2);
    };

    form.addEventListener('input', (event) => {
        if (event.target.matches('[data-item-qty], [data-item-rate], [data-approved-percent], [data-total-input]') || event.target.matches(deductionSelector)) {
            recalculateTotals();
        }
    });

    let itemIndex = form.querySelectorAll('[data-item-row]').length;

    const renumberItems = () => {
        [...form.querySelectorAll('[data-item-row]')].forEach((row, index) => {
            row.setAttribute('data-item-index', String(index));
            const title = row.querySelector('[data-item-title]');
            if (title) title.textContent = `${index + 1}. Work item`;
            row.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/data\[items\]\[\d+\]/, `data[items][${index}]`);
            });
        });
    };

    form.querySelector('[data-add-item]')?.addEventListener('click', () => {
        const rowsContainer = form.querySelector('[data-item-rows]');
        rowsContainer?.appendChild(buildItemRow(itemIndex));
        itemIndex += 1;
        renumberItems();
    });

    form.addEventListener('click', (event) => {
        if (event.target.matches('[data-remove-item]')) {
            event.target.closest('[data-item-row]')?.remove();
            renumberItems();
            recalculateTotals();
        }
    });

    const salaryToggle = form.querySelector('[data-toggle-salary-deductions]');
    salaryToggle?.addEventListener('change', () => {
        form.querySelectorAll('[data-salary-deduction]').forEach((el) => {
            el.hidden = !salaryToggle.checked;
        });
        recalculateTotals();
    });

    const setCopiedFieldState = () => {
        if (!copiedMode) {
            fields.forEach((field) => field.disabled = false);
            return;
        }

        fields.forEach((field) => {
            field.disabled = field !== serialField && !editToggle.checked;
        });
    };

    const clearForm = (nextSerial = serialField.value) => {
        fields.forEach((field) => {
            field.disabled = false;
            field.value = '';
        });

        serialField.value = nextSerial;
        copiedMode = false;
        if (editToggle) editToggle.checked = false;
        refreshBeatOptions();
        refreshPlaceOptions();
        recalculateTotals();
    };

    editToggle?.addEventListener('change', setCopiedFieldState);

    copyButton?.addEventListener('click', async () => {
        const copyId = copyInput.value.trim();

        if (!copyId) {
            showAlert('Enter Sr. No. before copying entry.', 'error');
            return;
        }

        try {
            const response = await fetch(`${form.dataset.copyUrl}/${encodeURIComponent(copyId)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                showAlert('No entry found in database for this Sr. No.', 'error');
                return;
            }

            const { entry } = await response.json();

            fields.forEach((field) => {
                if (field === beatSelect || field === placeSelect) return;
                setValue(field, entry[fieldKey(field)]);
            });

            refreshBeatOptions(entry.beat);
            refreshPlaceOptions(entry.place);
            recalculateTotals();

            copiedMode = true;
            setCopiedFieldState();
            showAlert('Copied entry loaded from database. Turn on Edit copied entry to modify fields.');
        } catch (error) {
            showAlert('Copy failed. Please try again.', 'error');
        }
    });

    form.addEventListener('reset', (event) => {
        event.preventDefault();
        clearForm();
        alert.className = 'hidden rounded-lg border px-4 py-3 text-sm font-semibold';
        alert.textContent = '';
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        fields.forEach((field) => field.disabled = false);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(form),
            });

            const result = await response.json();

            if (!response.ok) {
                const message = result.message || Object.values(result.errors || {})[0]?.[0] || 'Please check the form and try again.';
                showAlert(message, 'error');
                return;
            }

            if (result.redirect_url) {
                window.location.href = result.redirect_url;
                return;
            }

            clearForm(result.next_serial);
            showAlert(`Success: Notification - ${result.message}`);
        } catch (error) {
            showAlert('Submit failed. Please try again.', 'error');
        }
    });

    recalculateTotals();
}

initEntrySearch('[data-tender-entry-search]', '[data-tender-entry-row]');

initVoucherForm(document.querySelector('[data-tender-entry-form]'), {
    alertId: 'tenderEntryFormAlert',
    serialFieldId: 'entry_sr_no',
    copyInputId: 'copy_entry_sr_no',
    deductionSelector: '[id^="deduction_"], #deposit_deduction_amount, #tds, #labour_cess',
    buildItemRow: (index) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'border-b border-emerald-100 p-5 last:border-b-0';
        wrapper.setAttribute('data-item-row', '');
        wrapper.setAttribute('data-item-index', String(index));
        wrapper.innerHTML = `
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-950" data-item-title>${index + 1}. Work item</h3>
                <button class="secondary-button min-h-7 border-red-200 bg-red-50 px-1.5 py-0.5 text-[10px] text-red-700" type="button" data-remove-item>Remove</button>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div><label class="form-label">SOR Code</label><input class="form-input" name="data[items][${index}][sor_code]" data-item-field></div>
                <div class="md:col-span-2 xl:col-span-3"><label class="form-label">Work Description</label><input class="form-input" name="data[items][${index}][work_description]" data-item-field></div>
                <div><label class="form-label">Work Order No.</label><input class="form-input" name="data[items][${index}][work_order_no]" data-item-field></div>
                <div><label class="form-label">Work Order Date</label><input class="form-input" name="data[items][${index}][work_order_date]" type="date" data-item-field></div>
                <div><label class="form-label">Work Start Date</label><input class="form-input" name="data[items][${index}][work_start_date]" type="date" data-item-field></div>
                <div><label class="form-label">Work End Date</label><input class="form-input" name="data[items][${index}][work_end_date]" type="date" data-item-field></div>
                <div><label class="form-label">No. of Unit</label><input class="form-input" name="data[items][${index}][no_of_unit]" value="0.00000" type="number" min="0" step="any" data-item-field data-item-qty></div>
                <div><label class="form-label">Unit</label><input class="form-input" name="data[items][${index}][unit]" data-item-field></div>
                <div><label class="form-label">SOR Rate</label><input class="form-input" name="data[items][${index}][sor_rate]" type="number" min="0" step="any" data-item-field data-item-rate></div>
                <div><label class="form-label">Amount</label><input class="form-input bg-stone-50" name="data[items][${index}][amount]" readonly data-item-field data-item-amount></div>
            </div>
            <p class="mt-2 text-xs text-slate-500" data-item-remaining-limit>Remaining Limit = -</p>
        `;

        return wrapper;
    },
});

initEntrySearch('[data-free-entry-search]', '[data-free-entry-row]');

initVoucherForm(document.querySelector('[data-free-entry-form]'), {
    alertId: 'freeEntryFormAlert',
    serialFieldId: 'entry_sr_no',
    copyInputId: 'copy_entry_sr_no',
    deductionSelector: '[id^="deduction_"], #tds, #labour_cess',
    buildItemRow: (index) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'border-b border-emerald-100 p-5 last:border-b-0';
        wrapper.setAttribute('data-item-row', '');
        wrapper.setAttribute('data-item-index', String(index));
        wrapper.innerHTML = `
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-950" data-item-title>${index + 1}. Work item</h3>
                <button class="secondary-button min-h-7 border-red-200 bg-red-50 px-1.5 py-0.5 text-[10px] text-red-700" type="button" data-remove-item>Remove</button>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="md:col-span-2 xl:col-span-2"><label class="form-label">Work Description</label><input class="form-input" name="data[items][${index}][work_description]" data-item-field></div>
                <div><label class="form-label">No. of Unit</label><input class="form-input" name="data[items][${index}][no_of_unit]" value="0.00000" type="number" min="0" step="any" data-item-field data-item-qty></div>
                <div><label class="form-label">Rate</label><input class="form-input" name="data[items][${index}][rate]" value="0.00000" type="number" min="0" step="any" data-item-field data-item-rate></div>
                <div><label class="form-label">Amount</label><input class="form-input bg-stone-50" name="data[items][${index}][amount]" readonly data-item-field data-item-amount></div>
            </div>
        `;

        return wrapper;
    },
});

initEntrySearch('[data-d-wager-salary-search]', '[data-d-wager-salary-row]');

initVoucherForm(document.querySelector('[data-d-wager-salary-form]'), {
    alertId: 'dWagerSalaryFormAlert',
    serialFieldId: 'entry_sr_no',
    copyInputId: 'copy_entry_sr_no',
    deductionSelector: '[data-deduction-input]',
    buildItemRow: () => null,
});

initEntrySearch('[data-d-wager-arrears-search]', '[data-d-wager-arrears-row]');

initVoucherForm(document.querySelector('[data-d-wager-arrears-form]'), {
    alertId: 'dWagerArrearsFormAlert',
    serialFieldId: 'entry_sr_no',
    copyInputId: 'copy_entry_sr_no',
    deductionSelector: '[data-deduction-input]',
    buildItemRow: () => null,
});

initEntrySearch('[data-sf-bene-entry-search]', '[data-sf-bene-entry-row]');

(function initSfBeneficiaryEntryForm() {
    const form = document.querySelector('[data-sf-bene-entry-form]');
    if (!form) return;

    const alert = document.getElementById('sfBeneficiaryEntryFormAlert');
    const copyInput = document.getElementById('copy_entry_sr_no');
    const copyButton = form.querySelector('[data-copy-entry]');
    const editToggle = form.querySelector('[data-edit-copied-entry]');
    const fields = [...form.querySelectorAll('[data-entry-field]')];
    const serialField = document.getElementById('entry_sr_no');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let copiedMode = false;

    let budgetCodes = {};
    let beatsByRound = {};
    let beneficiariesByRoundBeat = {};
    let beneficiaryDetails = {};
    try { budgetCodes = JSON.parse(form.dataset.budgetCodes || '{}'); } catch (e) { budgetCodes = {}; }
    try { beatsByRound = JSON.parse(form.dataset.beatsByRound || '{}'); } catch (e) { beatsByRound = {}; }
    try { beneficiariesByRoundBeat = JSON.parse(form.dataset.beneficiariesByRoundBeat || '{}'); } catch (e) { beneficiariesByRoundBeat = {}; }
    try { beneficiaryDetails = JSON.parse(form.dataset.beneficiaryDetails || '{}'); } catch (e) { beneficiaryDetails = {}; }

    const budgetCodeSelect = form.querySelector('[data-budget-code-select]');
    const schemeField = form.querySelector('[data-scheme-field]');
    const modelField = form.querySelector('[data-model-field]');
    const schemeYearField = form.querySelector('[data-scheme-year-field]');
    const roundSelect = form.querySelector('[data-round-select]');
    const beatSelect = form.querySelector('[data-beat-select]');
    const beneCodeSelect = form.querySelector('[data-bene-code-select]');

    const fieldKey = (field) => {
        const match = field.name.match(/^data\[(.+)\]$/);
        return match ? match[1] : field.name;
    };

    const showAlert = (message, type = 'success') => {
        alert.textContent = message;
        alert.className = type === 'success'
            ? 'rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800'
            : 'rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800';
    };

    const setValue = (field, value) => {
        if (field.type === 'radio') {
            field.checked = field.value === value;
            return;
        }

        field.value = value ?? '';
    };

    const applyBudgetCode = () => {
        const details = budgetCodes[budgetCodeSelect?.value];
        if (schemeField) schemeField.value = details?.scheme ?? '';
        if (modelField) modelField.value = details?.model ?? '';
        if (schemeYearField) schemeYearField.value = details?.scheme_year ?? '';
    };

    const refreshBeatOptions = (selectedBeat = '') => {
        if (!roundSelect || !beatSelect) return;

        const beats = [...(beatsByRound[roundSelect.value] || [])];
        if (selectedBeat && !beats.includes(selectedBeat)) beats.push(selectedBeat);

        beatSelect.innerHTML = '<option value="" disabled>Choose...</option>'
            + beats.map((beat) => `<option ${beat === selectedBeat ? 'selected' : ''}>${beat}</option>`).join('');
    };

    const refreshBeneCodeOptions = (selectedCode = '') => {
        if (!roundSelect || !beatSelect || !beneCodeSelect) return;

        const key = `${roundSelect.value}|${beatSelect.value}`;
        const codes = [...(beneficiariesByRoundBeat[key] || [])];
        if (selectedCode && !codes.includes(selectedCode)) codes.push(selectedCode);

        beneCodeSelect.innerHTML = '<option value="" disabled>Choose...</option>'
            + codes.map((code) => `<option ${code === selectedCode ? 'selected' : ''}>${code}</option>`).join('');
    };

    const applyBeneficiaryDetails = () => {
        const details = beneficiaryDetails[beneCodeSelect?.value] || {};
        form.querySelectorAll('[data-bene-field]').forEach((field) => {
            field.value = details[field.dataset.beneField] ?? '';
        });
    };

    budgetCodeSelect?.addEventListener('change', applyBudgetCode);
    roundSelect?.addEventListener('change', () => { refreshBeatOptions(); refreshBeneCodeOptions(); });
    beatSelect?.addEventListener('change', () => refreshBeneCodeOptions());
    beneCodeSelect?.addEventListener('change', applyBeneficiaryDetails);

    refreshBeatOptions(beatSelect?.value);
    refreshBeneCodeOptions(beneCodeSelect?.value);

    const num = (value) => parseFloat(value) || 0;

    const recalculate = () => {
        const totalPlantsField = document.getElementById('total_no_of_plants');
        const survivedField = form.querySelector('[data-plants-survived]');
        const survivalPercentField = form.querySelector('[data-survival-percent]');
        const ratePerPlantField = form.querySelector('[data-rate-per-plant]');
        const totalAmountField = form.querySelector('[data-total-amount]');

        const totalPlants = num(totalPlantsField?.value);
        const survived = num(survivedField?.value);
        const survivalPercent = totalPlants > 0 ? (survived / totalPlants) * 100 : 0;
        if (survivalPercentField) survivalPercentField.value = survivalPercent.toFixed(2);

        const amount = survived * num(ratePerPlantField?.value);
        if (totalAmountField) totalAmountField.value = amount.toFixed(2);
    };

    form.addEventListener('input', (event) => {
        if (event.target.matches('#total_no_of_plants, [data-plants-survived], [data-rate-per-plant]')) {
            recalculate();
        }
    });

    const setCopiedFieldState = () => {
        if (!copiedMode) {
            fields.forEach((field) => field.disabled = false);
            return;
        }

        fields.forEach((field) => {
            field.disabled = field !== serialField && !editToggle.checked;
        });
    };

    const clearForm = (nextSerial = serialField.value) => {
        fields.forEach((field) => {
            field.disabled = false;
            field.value = '';
        });

        serialField.value = nextSerial;
        copiedMode = false;
        if (editToggle) editToggle.checked = false;
        refreshBeatOptions();
        refreshBeneCodeOptions();
        recalculate();
    };

    editToggle?.addEventListener('change', setCopiedFieldState);

    copyButton?.addEventListener('click', async () => {
        const copyId = copyInput.value.trim();

        if (!copyId) {
            showAlert('Enter Sr. No. before copying entry.', 'error');
            return;
        }

        try {
            const response = await fetch(`${form.dataset.copyUrl}/${encodeURIComponent(copyId)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                showAlert('No entry found in database for this Sr. No.', 'error');
                return;
            }

            const { entry } = await response.json();

            fields.forEach((field) => {
                if (field === beatSelect || field === beneCodeSelect) return;
                setValue(field, entry[fieldKey(field)]);
            });

            refreshBeatOptions(entry.beat);
            refreshBeneCodeOptions(entry.sf_bene_code);
            recalculate();

            copiedMode = true;
            setCopiedFieldState();
            showAlert('Copied entry loaded from database. Turn on Edit copied entry to modify fields.');
        } catch (error) {
            showAlert('Copy failed. Please try again.', 'error');
        }
    });

    form.addEventListener('reset', (event) => {
        event.preventDefault();
        clearForm();
        alert.className = 'hidden rounded-lg border px-4 py-3 text-sm font-semibold';
        alert.textContent = '';
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        fields.forEach((field) => field.disabled = false);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: new FormData(form),
            });

            const result = await response.json();

            if (!response.ok) {
                const message = result.message || Object.values(result.errors || {})[0]?.[0] || 'Please check the form and try again.';
                showAlert(message, 'error');
                return;
            }

            if (result.redirect_url) {
                window.location.href = result.redirect_url;
                return;
            }

            clearForm(result.next_serial);
            showAlert(`Success: Notification - ${result.message}`);
        } catch (error) {
            showAlert('Submit failed. Please try again.', 'error');
        }
    });

    recalculate();
})();