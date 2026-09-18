<x-layouts.admin title="Office Profile & Letterhead | Forest Inventory" heading="Office Profile & Letterhead" subheading="Configure Division/Range address, officer designations, contact details and official logo">
    <div class="space-y-6">

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <svg class="h-5 w-5 text-emerald-600 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950 font-bold">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-900 shadow-sm space-y-1">
                <p class="font-bold">Please check the errors below:</p>
                <ul class="list-disc list-inside space-y-0.5 text-xs">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Left Column: Form Settings (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">
                <form action="{{ route('office-profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Card 1: Logo & Branding -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <svg class="h-5 w-5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>
                                    </svg>
                                    <span>Official Logo &amp; Emblem</span>
                                </h2>
                                <p class="text-xs text-slate-500">Upload custom division/range emblem or use official Gujarat Forest Department logo.</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $profile->hasCustomLogo() ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $profile->hasCustomLogo() ? 'Custom Logo Active' : 'Official Default Emblem' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-center">
                            <div class="sm:col-span-4 flex flex-col items-center justify-center p-3 rounded-xl border border-slate-200 bg-slate-50/50">
                                <div class="h-24 w-24 rounded-lg overflow-hidden border border-slate-200 bg-white p-1.5 flex items-center justify-center shadow-inner">
                                    <img id="logoPreviewImg" src="{{ $profile->logo_url }}" alt="Office Logo" class="max-h-full max-w-full object-contain">
                                </div>
                                <span class="text-[11px] font-medium text-slate-500 mt-2 text-center" id="logoStatusText">
                                    {{ $profile->hasCustomLogo() ? 'Uploaded Custom Logo' : 'Default Forest Emblem' }}
                                </span>
                            </div>

                            <div class="sm:col-span-8 space-y-3">
                                <div>
                                    <label for="logoInput" class="block text-xs font-bold text-slate-700 mb-1">
                                        Choose New Logo File (PNG, JPG, SVG, WebP)
                                    </label>
                                    <input type="file" name="logo" id="logoInput" accept="image/png, image/jpeg, image/jpg, image/svg+xml, image/webp"
                                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer border border-slate-200 rounded-lg">
                                    <p class="text-[11px] text-slate-500 mt-1">Recommended size: 400x400 px, transparent background or crisp square aspect ratio.</p>
                                </div>

                                @if($profile->hasCustomLogo())
                                    <div class="pt-1">
                                        <button type="button" onclick="document.getElementById('removeLogoForm').submit()"
                                            class="text-xs text-rose-600 hover:text-rose-800 font-semibold underline flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            Reset to Default Official Gujarat Emblem
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Office Identification -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <svg class="h-5 w-5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M8 10h.01M16 10h.01"/>
                                </svg>
                                <span>Office Identification &amp; Statutory Titles</span>
                            </h2>
                            <p class="text-xs text-slate-500">Official names displayed on report headers, schedules, and letterheads.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="office_name" class="block text-xs font-bold text-slate-700 mb-1">
                                    Office Name (English) <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="office_name" id="office_name" value="{{ old('office_name', $profile->office_name) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. Social Forestry Division Sabarkantha" oninput="updateLivePreview()">
                            </div>

                            <div>
                                <label for="office_name_gujarati" class="block text-xs font-bold text-slate-700 mb-1">
                                    કચેરીનું નામ (ગુજરાતી)
                                </label>
                                <input type="text" name="office_name_gujarati" id="office_name_gujarati" value="{{ old('office_name_gujarati', $profile->office_name_gujarati) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="દા.ત. સામાજિક વનીકરણ વિભાગ, સાબરકાંઠા" oninput="updateLivePreview()">
                            </div>

                            <div>
                                <label for="ddo_code" class="block text-xs font-bold text-slate-700 mb-1">
                                    DDO / Treasury Office Code
                                </label>
                                <input type="text" name="ddo_code" id="ddo_code" value="{{ old('ddo_code', $profile->ddo_code) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. 040601 / 2406-SF" oninput="updateLivePreview()">
                            </div>

                            <div>
                                <label for="tan_no" class="block text-xs font-bold text-slate-700 mb-1">
                                    TAN / Tax Assessment No.
                                </label>
                                <input type="text" name="tan_no" id="tan_no" value="{{ old('tan_no', $profile->tan_no) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-900 uppercase focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. AHMD01234F" oninput="updateLivePreview()">
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Officer Details -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <svg class="h-5 w-5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                                </svg>
                                <span>Officer in-Charge Details</span>
                            </h2>
                            <p class="text-xs text-slate-500">Name and designations utilized on official signature lines, certificates, and work orders.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="officer_name" class="block text-xs font-bold text-slate-700 mb-1">
                                    Officer Full Name
                                </label>
                                <input type="text" name="officer_name" id="officer_name" value="{{ old('officer_name', $profile->officer_name) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. Shri V. R. Patel, IFS" oninput="updateLivePreview()">
                            </div>

                            <div>
                                <label for="officer_designation" class="block text-xs font-bold text-slate-700 mb-1">
                                    Designation (English)
                                </label>
                                <input type="text" name="officer_designation" id="officer_designation" value="{{ old('officer_designation', $profile->officer_designation) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. Deputy Conservator of Forests" oninput="updateLivePreview()">
                            </div>

                            <div>
                                <label for="officer_designation_gujarati" class="block text-xs font-bold text-slate-700 mb-1">
                                    હોદ્દો (ગુજરાતી)
                                </label>
                                <input type="text" name="officer_designation_gujarati" id="officer_designation_gujarati" value="{{ old('officer_designation_gujarati', $profile->officer_designation_gujarati) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="દા.ત. નાયબ વન સંરક્ષક" oninput="updateLivePreview()">
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Address & Contact Details -->
                    <div class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <svg class="h-5 w-5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span>Official Address &amp; Contact Details</span>
                            </h2>
                            <p class="text-xs text-slate-500">Address line printed on official letters, certificates, and voucher schedules.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                            <div class="sm:col-span-12">
                                <label for="office_address" class="block text-xs font-bold text-slate-700 mb-1">
                                    Office Address Line
                                </label>
                                <input type="text" name="office_address" id="office_address" value="{{ old('office_address', $profile->office_address) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. Near Circuit House, Modasa Road" oninput="updateLivePreview()">
                            </div>

                            <div class="sm:col-span-4">
                                <label for="city" class="block text-xs font-bold text-slate-700 mb-1">
                                    City / Town
                                </label>
                                <input type="text" name="city" id="city" value="{{ old('city', $profile->city) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. Himatnagar" oninput="updateLivePreview()">
                            </div>

                            <div class="sm:col-span-4">
                                <label for="taluka" class="block text-xs font-bold text-slate-700 mb-1">
                                    Taluka
                                </label>
                                <input type="text" name="taluka" id="taluka" value="{{ old('taluka', $profile->taluka) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. Himatnagar" oninput="updateLivePreview()">
                            </div>

                            <div class="sm:col-span-4">
                                <label for="district" class="block text-xs font-bold text-slate-700 mb-1">
                                    District
                                </label>
                                <input type="text" name="district" id="district" value="{{ old('district', $profile->district) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. Sabarkantha" oninput="updateLivePreview()">
                            </div>

                            <div class="sm:col-span-3">
                                <label for="pincode" class="block text-xs font-bold text-slate-700 mb-1">
                                    PIN Code
                                </label>
                                <input type="text" name="pincode" id="pincode" value="{{ old('pincode', $profile->pincode) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. 383001" oninput="updateLivePreview()">
                            </div>

                            <div class="sm:col-span-3">
                                <label for="phone" class="block text-xs font-bold text-slate-700 mb-1">
                                    Office Phone (Landline)
                                </label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $profile->phone) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. 02772-240123" oninput="updateLivePreview()">
                            </div>

                            <div class="sm:col-span-3">
                                <label for="mobile" class="block text-xs font-bold text-slate-700 mb-1">
                                    Officer Mobile
                                </label>
                                <input type="text" name="mobile" id="mobile" value="{{ old('mobile', $profile->mobile) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. 9876543210" oninput="updateLivePreview()">
                            </div>

                            <div class="sm:col-span-3">
                                <label for="email" class="block text-xs font-bold text-slate-700 mb-1">
                                    Official Email ID
                                </label>
                                <input type="email" name="email" id="email" value="{{ old('email', $profile->email) }}"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600"
                                    placeholder="e.g. dcf-sf-sab@gujarat.gov.in" oninput="updateLivePreview()">
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('dashboard') }}" class="secondary-button">
                            Cancel
                        </a>
                        <button type="submit" class="primary-button flex items-center gap-2 font-bold px-6 py-2.5 shadow-md">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span>Save Profile &amp; Update Reports</span>
                        </button>
                    </div>
                </form>

                <!-- Hidden Form for Remove Logo -->
                <form id="removeLogoForm" action="{{ route('office-profile.logo.destroy') }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>

            <!-- Right Column: Live Letterhead & Report Preview (4 Cols Sticky) -->
            <div class="lg:col-span-4 space-y-4 lg:sticky lg:top-20">
                <div class="rounded-xl border-2 border-emerald-600/30 bg-white p-5 shadow-lg space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-950 flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-emerald-600 animate-pulse"></span>
                            Live Report Letterhead Preview
                        </span>
                        <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">A4 Report Header</span>
                    </div>

                    <!-- Miniature Letterhead Representation -->
                    <div class="rounded-lg border border-slate-300 bg-stone-50/50 p-4 shadow-inner space-y-3">
                        <div class="flex items-center gap-3 border-b-2 border-slate-900 pb-2.5">
                            <img id="livePreviewLogo" src="{{ $profile->logo_url }}" alt="Logo Preview" class="h-12 w-12 object-contain shrink-0 rounded">
                            <div class="min-w-0 flex-1 text-center space-y-0.5">
                                <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-900 leading-tight">
                                    GUJARAT STATE FOREST DEPARTMENT
                                </h3>
                                <h4 id="prevOfficeName" class="text-xs font-extrabold text-slate-950 truncate leading-tight">
                                    {{ $profile->display_name }}
                                </h4>
                                <h5 id="prevOfficeNameGuj" class="text-[10px] font-bold text-emerald-900 leading-tight">
                                    {{ $profile->display_name_gujarati }}
                                </h5>
                                <p id="prevAddress" class="text-[9px] text-slate-600 leading-tight truncate">
                                    {{ $profile->formatted_address ?: 'Near Circuit House, Himatnagar - 383001' }}
                                </p>
                            </div>
                        </div>

                        <!-- Mini Sample Body -->
                        <div class="py-2 space-y-1 text-center">
                            <span class="inline-block px-2 py-0.5 bg-emerald-100 text-emerald-900 rounded font-bold text-[9px]">
                                SAMPLE STATUTORY REPORT
                            </span>
                            <div class="h-2 bg-slate-200 rounded w-3/4 mx-auto"></div>
                            <div class="h-2 bg-slate-100 rounded w-1/2 mx-auto"></div>
                        </div>

                        <!-- Mini Signature Block -->
                        <div class="flex justify-between items-end pt-3 border-t border-slate-200 text-[9px] font-semibold text-slate-800">
                            <div>
                                <span class="text-[8px] text-slate-500 block">Chief Accountant</span>
                                <span>મુખ્ય હિસાબનીશ</span>
                            </div>
                            <div class="text-right">
                                <span id="prevOfficerName" class="block font-bold text-slate-950">
                                    {{ $profile->display_officer_name ?: 'Officer In-Charge' }}
                                </span>
                                <span id="prevOfficerDesig" class="block text-[8px] text-slate-600">
                                    {{ $profile->display_designation }}
                                </span>
                                <span id="prevOfficerDesigGuj" class="block text-[8px] font-bold text-emerald-900">
                                    {{ $profile->display_designation_gujarati }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-emerald-50/70 p-3 text-xs text-emerald-950 border border-emerald-200/60 space-y-1">
                        <p class="font-bold flex items-center gap-1 text-emerald-900">
                            <svg class="h-4 w-4 text-emerald-700" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            Dynamic Report Integration
                        </p>
                        <p class="text-[11px] leading-relaxed text-emerald-800">
                            આ પ્રોફાઇલ વિગતો અને લોગો તમામ <strong>Monthly Reports</strong>, <strong>Summary Reports</strong>, <strong>Form 35 / Cashbook</strong>, <strong>Bill/Advice Reports</strong> અને <strong>Range Voucher Prints</strong> પર ઓટોમેટીક દર્શાવાશે.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const logoInput = document.getElementById('logoInput');
            const logoPreviewImg = document.getElementById('logoPreviewImg');
            const livePreviewLogo = document.getElementById('livePreviewLogo');
            const logoStatusText = document.getElementById('logoStatusText');

            if (logoInput) {
                logoInput.addEventListener('change', function () {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            logoPreviewImg.src = e.target.result;
                            livePreviewLogo.src = e.target.result;
                            logoStatusText.textContent = 'Selected New Image: ' + file.name;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            window.updateLivePreview = function () {
                const officeName = document.getElementById('office_name')?.value || 'Social Forestry Division';
                const officeNameGuj = document.getElementById('office_name_gujarati')?.value || 'સામાજિક વનીકરણ વિભાગ';
                const address = document.getElementById('office_address')?.value || '';
                const city = document.getElementById('city')?.value || '';
                const district = document.getElementById('district')?.value || '';
                const pincode = document.getElementById('pincode')?.value || '';
                const officerName = document.getElementById('officer_name')?.value || 'Officer In-Charge';
                const officerDesig = document.getElementById('officer_designation')?.value || 'Deputy Conservator of Forests';
                const officerDesigGuj = document.getElementById('officer_designation_gujarati')?.value || 'નાયબ વન સંરક્ષક';

                document.getElementById('prevOfficeName').textContent = officeName;
                document.getElementById('prevOfficeNameGuj').textContent = officeNameGuj;
                
                const addrParts = [address, city, district, pincode ? 'PIN - ' + pincode : ''].filter(Boolean);
                document.getElementById('prevAddress').textContent = addrParts.join(', ') || 'Official Address Line';

                document.getElementById('prevOfficerName').textContent = officerName;
                document.getElementById('prevOfficerDesig').textContent = officerDesig;
                document.getElementById('prevOfficerDesigGuj').textContent = officerDesigGuj;
            };
        });
    </script>
</x-layouts.admin>
