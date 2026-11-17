{{-- Medicine & Stock Inventory Modal --}}
<div class="modal fade" id="medicineStockModal" tabindex="-1" aria-labelledby="medicineStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 24px; border: 1px solid rgba(196,200,188,0.45); overflow: hidden; box-shadow: 0 20px 50px rgba(46,50,48,0.18);">
            <div class="modal-header border-0 px-4 pt-4 pb-3" style="background: #FAF6F0; border-bottom: 1px solid #EAE6DE !important;">
                <div>
                    <h5 class="modal-title d-flex align-items-center gap-2 mb-1" id="medicineStockModalLabel"
                        style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230;">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                              style="width: 34px; height: 34px; background: #E1F3E6; color: #2A6038; font-size: 15px;">
                            <i class="fas fa-pills"></i>
                        </span>
                        Pet Medicine &amp; Stock Inventory
                    </h5>
                    <p class="mb-0" style="font-family: 'Nunito Sans', sans-serif; font-size: 12px; color: #6B6358;">
                        Manage live medicine stock for consultations and the VetBook AI Chatbot
                        @if($branch)
                            &middot; Scoped to <strong>{{ $branch }}</strong> &amp; Shared Inventory
                        @else
                            &middot; All Branches
                        @endif
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4" style="background: #FFFFFF;">
                {{-- Add New Medicine Form --}}
                <div class="p-3 mb-4" style="background: #FAF6F0; border: 1px solid #EAE6DE; border-radius: 18px;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span style="font-family: 'Literata', serif; font-size: 15px; font-weight: 700; color: #2E3230;">
                            <i class="fas fa-plus-circle me-1" style="color: #4A7C59;"></i> Add Pet Medicine
                        </span>
                    </div>

                    <form method="POST" action="{{ route('admin.medicines.store') }}">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label mb-1" style="font-size: 11px; font-weight: 700; color: #6B6358; text-transform: uppercase;">
                                    Medicine Name <span style="color: #B83230;">*</span>
                                </label>
                                <input type="text" name="name" class="form-control form-control-sm"
                                       placeholder="e.g. Amoxicillin 250mg" maxlength="150" required
                                       style="border-radius: 12px; padding: 8px 12px; border-color: #DCD6C8;">
                            </div>

                            <div class="col-md-2 col-6">
                                <label class="form-label mb-1" style="font-size: 11px; font-weight: 700; color: #6B6358; text-transform: uppercase;">
                                    Category
                                </label>
                                <select name="category" class="form-select form-select-sm"
                                        style="border-radius: 12px; padding: 8px 10px; border-color: #DCD6C8;">
                                    <option value="Antibiotic">Antibiotic</option>
                                    <option value="Dewormer">Dewormer</option>
                                    <option value="Vaccine">Vaccine</option>
                                    <option value="Vitamin">Vitamin</option>
                                    <option value="Flea & Tick">Flea &amp; Tick</option>
                                    <option value="Pain Relief">Pain Relief</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-2 col-6">
                                <label class="form-label mb-1" style="font-size: 11px; font-weight: 700; color: #6B6358; text-transform: uppercase;">
                                    Target Pets
                                </label>
                                <select name="species" class="form-select form-select-sm"
                                        style="border-radius: 12px; padding: 8px 10px; border-color: #DCD6C8;">
                                    <option value="All Pets">All Pets</option>
                                    <option value="Dogs">Dogs</option>
                                    <option value="Cats">Cats</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-2 col-6">
                                <label class="form-label mb-1" style="font-size: 11px; font-weight: 700; color: #6B6358; text-transform: uppercase;">
                                    Unit
                                </label>
                                <select name="unit" class="form-select form-select-sm"
                                        style="border-radius: 12px; padding: 8px 10px; border-color: #DCD6C8;">
                                    <option value="tablets">tablets</option>
                                    <option value="bottles">bottles</option>
                                    <option value="vials">vials</option>
                                    <option value="boxes">boxes</option>
                                    <option value="capsules">capsules</option>
                                    <option value="units">units</option>
                                </select>
                            </div>

                            <div class="col-md-2 col-6">
                                <label class="form-label mb-1" style="font-size: 11px; font-weight: 700; color: #6B6358; text-transform: uppercase;">
                                    Stock Qty <span style="color: #B83230;">*</span>
                                </label>
                                <input type="number" name="stock" class="form-control form-control-sm"
                                       min="0" max="100000" value="0" required
                                       style="border-radius: 12px; padding: 8px 10px; border-color: #DCD6C8;">
                            </div>

                            <div class="col-md-4 col-12">
                                <label class="form-label mb-1" style="font-size: 11px; font-weight: 700; color: #6B6358; text-transform: uppercase;">
                                    Branch
                                </label>
                                @if($branch)
                                    <input type="hidden" name="branch" value="{{ $branch }}">
                                    <input type="text" class="form-control form-control-sm" value="{{ $branch }}" disabled
                                           style="border-radius: 12px; padding: 8px 10px; background: #EFEAE0; border-color: #DCD6C8;">
                                @else
                                    <select name="branch" class="form-select form-select-sm"
                                            style="border-radius: 12px; padding: 8px 10px; border-color: #DCD6C8;">
                                        <option value="">All Branches / Shared</option>
                                        <option value="Mabini Branch">Mabini Branch</option>
                                        <option value="Batangas City Branch">Batangas City Branch</option>
                                    </select>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label mb-1" style="font-size: 11px; font-weight: 700; color: #6B6358; text-transform: uppercase;">
                                    Notes
                                </label>
                                <input type="text" name="notes" class="form-control form-control-sm"
                                       maxlength="500" placeholder="Optional notes or storage instructions"
                                       style="border-radius: 12px; padding: 8px 12px; border-color: #DCD6C8;">
                            </div>

                            <div class="col-md-2 d-grid">
                                <button type="submit" class="btn btn-sm"
                                        style="background: #4A7C59; color: #FFFFFF; font-weight: 700; border-radius: 9999px; padding: 8px 14px;">
                                    <i class="fas fa-plus me-1"></i> Add Medicine
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Search & Feedback --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div class="position-relative" style="min-width: 260px; flex: 1; max-width: 420px;">
                        <i class="fas fa-search position-absolute" style="left: 14px; top: 50%; transform: translateY(-50%); color: #6B6358; font-size: 13px;"></i>
                        <input type="text" id="medicineSearchInput" class="form-control form-control-sm"
                               placeholder="Search medicine by name or category..."
                               style="padding: 9px 14px 9px 36px; border-radius: 9999px; background: #FAF6F0; border-color: #E5DFD3;">
                    </div>
                    <div id="medicineStockFeedback" class="small fw-semibold" style="display: none;"></div>
                </div>

                {{-- Inventory Table --}}
                @if($medicines->isEmpty())
                    <div class="text-center py-5" style="background: #FAF6F0; border-radius: 18px; border: 1px dashed #DCD6C8;">
                        <i class="fas fa-prescription-bottle-medical mb-2" style="font-size: 32px; color: #78A886;"></i>
                        <div style="font-family: 'Literata', serif; font-weight: 700; color: #2E3230;">No medicines listed yet</div>
                        <div style="font-size: 13px; color: #6B6358;">Add medicines above to track live branch stock counts.</div>
                    </div>
                @else
                    <div class="table-responsive" style="border: 1px solid #EAE6DE; border-radius: 16px; overflow: hidden;">
                        <table class="table table-hover align-middle mb-0" style="font-family: 'Nunito Sans', sans-serif; font-size: 13px;">
                            <thead style="background: #F5F1EA; color: #6B6358; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                                <tr>
                                    <th class="ps-3 py-3 border-0">Medicine Name</th>
                                    <th class="py-3 border-0">Category</th>
                                    <th class="py-3 border-0">Target Pets</th>
                                    <th class="py-3 border-0">Branch</th>
                                    <th class="py-3 border-0">Stock Status</th>
                                    <th class="py-3 border-0">Adjust Stock</th>
                                    <th class="pe-3 py-3 border-0 text-end">Remove</th>
                                </tr>
                            </thead>
                            <tbody id="medicineTableBody">
                                @foreach($medicines as $med)
                                    @php
                                        $status = $med->stock_status;
                                        $badgeStyles = match ($status) {
                                            'out' => ['bg' => '#FDE8E8', 'fg' => '#B83230', 'label' => 'Out of Stock (0)'],
                                            'low' => ['bg' => '#FDF3DC', 'fg' => '#9A6700', 'label' => "Low Stock ({$med->stock})"],
                                            default => ['bg' => '#E1F3E6', 'fg' => '#2A6038', 'label' => "In Stock ({$med->stock})"],
                                        };
                                    @endphp
                                    <tr class="medicine-row"
                                        data-search="{{ strtolower(trim($med->name . ' ' . ($med->category ?? '') . ' ' . ($med->species ?? '') . ' ' . ($med->branch ?? 'All Branches'))) }}"
                                        data-stock-url="{{ route('admin.medicines.stock', $med->id) }}"
                                        data-threshold="{{ $med->low_stock_threshold ?? 5 }}">
                                        <td class="ps-3 py-2">
                                            <div class="fw-bold" style="color: #2E3230;">{{ $med->name }}</div>
                                            @if($med->notes)
                                                <div class="text-muted" style="font-size: 11px;">{{ $med->notes }}</div>
                                            @endif
                                        </td>
                                        <td class="py-2">{{ $med->category ?: 'Other' }}</td>
                                        <td class="py-2">
                                            <span class="badge rounded-pill" style="background: #F5F1E8; color: #4A4E4A; font-weight: 600;">
                                                {{ $med->species ?: 'All Pets' }}
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <span class="badge rounded-pill" style="background: #EEF2F5; color: #35607E; font-weight: 700;">
                                                {{ $med->branch ?: 'All Branches' }}
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <span class="badge rounded-pill med-status-badge"
                                                  style="background: {{ $badgeStyles['bg'] }}; color: {{ $badgeStyles['fg'] }}; font-weight: 700; padding: 6px 10px;">
                                                {{ $badgeStyles['label'] }}
                                            </span>
                                            <span class="text-muted ms-1" style="font-size: 11px;">{{ $med->unit ?: 'units' }}</span>
                                        </td>
                                        <td class="py-2">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-secondary med-delta-btn" data-delta="-1"
                                                        title="Decrease stock by 1"
                                                        style="width: 28px; height: 28px; padding: 0; border-radius: 8px; font-weight: 700;">
                                                    &minus;
                                                </button>
                                                <input type="number" min="0" max="100000" value="{{ $med->stock }}"
                                                       class="form-control form-control-sm text-center med-stock-input"
                                                       style="width: 68px; height: 28px; border-radius: 8px; font-weight: 700;"
                                                       aria-label="Stock quantity for {{ $med->name }}">
                                                <button type="button" class="btn btn-sm btn-outline-secondary med-delta-btn" data-delta="1"
                                                        title="Increase stock by 1"
                                                        style="width: 28px; height: 28px; padding: 0; border-radius: 8px; font-weight: 700;">
                                                    +
                                                </button>
                                                <button type="button" class="btn btn-sm med-save-stock-btn ms-1"
                                                        style="background: #E1F3E6; color: #2A6038; border: 1px solid #B7DEC2; border-radius: 8px; font-size: 11px; font-weight: 700; height: 28px; padding: 0 10px;">
                                                    Save
                                                </button>
                                            </div>
                                        </td>
                                        <td class="pe-3 py-2 text-end">
                                            <form method="POST" action="{{ route('admin.medicines.destroy', $med->id) }}"
                                                  onsubmit="return confirm('Delete {{ addslashes($med->name) }} from inventory?');"
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm"
                                                        title="Delete medicine"
                                                        style="background: #FFF0F0; color: #B83230; border-radius: 8px; width: 30px; height: 30px; padding: 0;">
                                                    <i class="fas fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr id="medicineNoMatchRow" style="display: none;">
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No medicines match your search filter.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-2" style="background: #FFFFFF;">
                <button type="button" class="btn-soft" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const searchInput = document.getElementById('medicineSearchInput');
    const feedbackEl = document.getElementById('medicineStockFeedback');
    const noMatchRow = document.getElementById('medicineNoMatchRow');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function showFeedback(text, isError) {
        if (!feedbackEl) return;
        feedbackEl.textContent = text;
        feedbackEl.style.color = isError ? '#B83230' : '#2A6038';
        feedbackEl.style.display = 'inline-block';
        clearTimeout(feedbackEl._timer);
        feedbackEl._timer = setTimeout(() => {
            feedbackEl.style.display = 'none';
        }, 3000);
    }

    function applyStatusBadge(row, stock, stockStatus) {
        const badge = row.querySelector('.med-status-badge');
        const input = row.querySelector('.med-stock-input');
        if (input) input.value = stock;
        if (!badge) return;

        if (stockStatus === 'out' || stock <= 0) {
            badge.style.background = '#FDE8E8';
            badge.style.color = '#B83230';
            badge.textContent = 'Out of Stock (0)';
        } else if (stockStatus === 'low') {
            badge.style.background = '#FDF3DC';
            badge.style.color = '#9A6700';
            badge.textContent = `Low Stock (${stock})`;
        } else {
            badge.style.background = '#E1F3E6';
            badge.style.color = '#2A6038';
            badge.textContent = `In Stock (${stock})`;
        }
    }

    async function patchStock(row, payload) {
        const url = row.getAttribute('data-stock-url');
        if (!url) return;

        const controls = row.querySelectorAll('.med-delta-btn, .med-save-stock-btn');
        controls.forEach(btn => { btn.disabled = true; });

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                showFeedback(data?.message || 'Could not update stock.', true);
                return;
            }

            const newStock = Number(data.medicine?.stock ?? 0);
            applyStatusBadge(row, newStock, data.stock_status);
            showFeedback(`Saved stock for ${data.medicine?.name || 'medicine'}: ${newStock}.`, false);
        } catch (err) {
            showFeedback('Network error updating stock.', true);
        } finally {
            controls.forEach(btn => { btn.disabled = false; });
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            const rows = document.querySelectorAll('#medicineTableBody .medicine-row');
            let visible = 0;

            rows.forEach(row => {
                const hay = row.getAttribute('data-search') || '';
                const matched = q === '' || hay.includes(q);
                row.style.display = matched ? '' : 'none';
                if (matched) visible++;
            });

            if (noMatchRow) {
                noMatchRow.style.display = (rows.length > 0 && visible === 0) ? '' : 'none';
            }
        });
    }

    document.querySelectorAll('#medicineTableBody .medicine-row').forEach(row => {
        row.querySelectorAll('.med-delta-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const delta = parseInt(this.getAttribute('data-delta'), 10);
                patchStock(row, { delta });
            });
        });

        const saveBtn = row.querySelector('.med-save-stock-btn');
        const stockInput = row.querySelector('.med-stock-input');
        if (saveBtn && stockInput) {
            saveBtn.addEventListener('click', function () {
                const stock = Math.max(0, parseInt(stockInput.value || '0', 10) || 0);
                patchStock(row, { stock });
            });
        }
    });
})();
</script>
