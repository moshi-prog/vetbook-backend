{{-- Floating Batch Action Bar --}}
<div id="batchActionBar" style="display: none; position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%); z-index: 1060; background: #2E3230; color: #FFFFFF; padding: 10px 22px; border-radius: 9999px; align-items: center; gap: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); transition: all 0.2s ease;">
    <div class="d-flex align-items-center gap-2">
        <span class="badge rounded-pill" id="selectedCountBadge" style="background: #4A7C59; font-size: 13px; font-weight: 700; padding: 6px 12px;">0</span>
        <span style="font-family: 'Nunito Sans', sans-serif; font-size: 13px; font-weight: 600; white-space: nowrap;" id="selectedCountText">items selected</span>
    </div>
    <div style="height: 22px; width: 1px; background: rgba(255,255,255,0.2);"></div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm" id="btnExportSelected"
                style="background: rgba(255,255,255,0.15); color: #FFFFFF; border-radius: 9999px; padding: 6px 16px; font-weight: 700; font-size: 13px; border: 1px solid rgba(255,255,255,0.2); white-space: nowrap;">
            <i class="fas fa-file-pdf me-1 text-warning"></i> Export Selected to PDF
        </button>
        <button type="button" class="btn btn-sm btn-danger" id="btnDeleteSelected" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal"
                style="background: #BA1A1A; border: none; border-radius: 9999px; padding: 6px 16px; font-weight: 700; font-size: 13px; white-space: nowrap;">
            <i class="fas fa-trash-alt me-1"></i> Delete Selected
        </button>
        <button type="button" class="btn btn-sm" id="btnDeselectAll"
                style="background: transparent; color: #C4C0B6; border: none; font-size: 13px; font-weight: 600; padding: 6px 10px; white-space: nowrap;">
            <i class="fas fa-times me-1"></i> Deselect All
        </button>
    </div>
</div>

{{-- Bulk Delete Confirmation Modal --}}
<div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <form method="POST" action="{{ $bulkDeleteRoute }}" id="bulkDeleteForm">
                @csrf
                <div id="bulkDeleteHiddenInputs"></div>
                <div class="modal-header" style="border-bottom: 1px solid #EAE6DE; padding: 20px 24px;">
                    <h5 class="modal-title" style="font-family: 'Literata', serif; font-weight: 700; color: #BA1A1A;">
                        <i class="fas fa-trash-alt me-2"></i> Delete Selected {{ ucfirst($itemName ?? 'Items') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start" style="font-family: 'Nunito Sans', sans-serif; font-size: 14px; color: #4A4E4A; padding: 24px;">
                    <p class="mb-3">
                        Are you sure you want to permanently delete <strong id="bulkDeleteCount">0</strong> selected {{ $itemName ?? 'items' }}?
                    </p>
                    @if(($module ?? '') === 'appointments')
                        <div style="background: #E8F5E9; border: 1px solid rgba(46,125,50,0.3); border-radius: 12px; padding: 12px 16px; color: #1B5E20; font-size: 13px;">
                            <i class="fas fa-shield-alt me-1"></i>
                            <strong>Safe Deletion:</strong> The pet profile and medical records will remain completely untouched.
                        </div>
                    @elseif(($module ?? '') === 'medical-records')
                        <div style="background: #E8F5E9; border: 1px solid rgba(46,125,50,0.3); border-radius: 12px; padding: 12px 16px; color: #1B5E20; font-size: 13px;">
                            <i class="fas fa-shield-alt me-1"></i>
                            <strong>Safe Deletion:</strong> This only removes clinical consultation logs. <strong>Pet profiles and appointments will remain completely untouched.</strong>
                        </div>
                    @elseif(($module ?? '') === 'pets')
                        <div style="background: #FFDAD8; border: 1px solid rgba(184,50,48,0.3); border-radius: 12px; padding: 12px 16px; color: #690005; font-size: 13px;">
                            <i class="fas fa-triangle-exclamation me-1"></i>
                            <strong>Warning:</strong> You are deleting Pet Profiles. This removes the pet from the owner's mobile app and deletes all appointments and medical records.
                        </div>
                    @else
                        <div style="background: #FFDAD8; border: 1px solid rgba(184,50,48,0.3); border-radius: 12px; padding: 12px 16px; color: #690005;">
                            <i class="fas fa-triangle-exclamation me-1"></i>
                            <strong>Warning:</strong> This action cannot be undone. Associated records will be permanently removed.
                        </div>
                    @endif
                </div>
                <div class="modal-footer" style="border-top: 1px solid #EAE6DE; padding: 16px 24px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal"
                            style="font-family: 'Nunito Sans', sans-serif; font-weight: 600; font-size: 14px;">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4"
                            style="background: #BA1A1A; border: none; font-family: 'Nunito Sans', sans-serif; font-weight: 700; font-size: 14px;">
                        Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Selection Javascript --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAllCheckbox = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const batchActionBar = document.getElementById('batchActionBar');
    const selectedCountBadge = document.getElementById('selectedCountBadge');
    const selectedCountText = document.getElementById('selectedCountText');
    const bulkDeleteCount = document.getElementById('bulkDeleteCount');
    const bulkDeleteHiddenInputs = document.getElementById('bulkDeleteHiddenInputs');
    const btnDeselectAll = document.getElementById('btnDeselectAll');
    const btnExportSelected = document.getElementById('btnExportSelected');

    function updateSelection() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        const count = checkedBoxes.length;

        if (count > 0) {
            batchActionBar.style.display = 'flex';
            selectedCountBadge.textContent = count;
            selectedCountText.textContent = count === 1 ? 'item selected' : 'items selected';
            bulkDeleteCount.textContent = count;

            // Populate hidden inputs for modal form
            bulkDeleteHiddenInputs.innerHTML = '';
            checkedBoxes.forEach(function (cb) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'selected_ids[]';
                hiddenInput.value = cb.value;
                bulkDeleteHiddenInputs.appendChild(hiddenInput);
            });
        } else {
            batchActionBar.style.display = 'none';
            bulkDeleteHiddenInputs.innerHTML = '';
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = (rowCheckboxes.length > 0 && checkedBoxes.length === rowCheckboxes.length);
            selectAllCheckbox.indeterminate = (count > 0 && count < rowCheckboxes.length);
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            rowCheckboxes.forEach(function (cb) {
                cb.checked = selectAllCheckbox.checked;
            });
            updateSelection();
        });
    }

    rowCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', updateSelection);
    });

    if (btnDeselectAll) {
        btnDeselectAll.addEventListener('click', function () {
            rowCheckboxes.forEach(function (cb) {
                cb.checked = false;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
            updateSelection();
        });
    }

    if (btnExportSelected) {
        btnExportSelected.addEventListener('click', function () {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const ids = Array.from(checkedBoxes).map(function (cb) { return cb.value; });
            if (ids.length === 0) return;

            const exportBaseUrl = '{{ $exportRoute }}';
            const url = exportBaseUrl + '?ids=' + encodeURIComponent(ids.join(',')) + '&format=pdf';
            window.open(url, '_blank');
        });
    }
});
</script>

