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