@extends('layout.app')

@section('content')

<div class="container mt-4">
    <h2 class="mb-3">Slot Locator</h2>

    <div class="slot-container">
        {{-- LEFT COLUMN — GRID --}}
        <div class="slot-left">
            <div class="slot-grid">
                @php
                    $cols = range('A', 'H'); // A–H
                @endphp
                {{-- ROW 1 (row = 1) --}}
                @foreach ($cols as $index => $colLetter)
                    @if ($index === 0)
                        {{-- 1st cell: 1A --}}
                        <div class="cell header"
                             data-row="1"
                             data-col="A">
                            1A
                        </div>
                    @else
                        {{-- B–H --}}
                        <div class="cell header"
                             data-row="1"
                             data-col="{{ $colLetter }}">
                            {{ $colLetter }}
                        </div>
                    @endif
                @endforeach

                {{-- ROWS 2–7 --}}
                @for ($row = 2; $row <= 7; $row++)
                    @foreach ($cols as $index => $colLetter)
                        @if ($index === 0)
                            {{-- First column: row number only, but still col = A --}}
                            <div class="cell header"
                                 data-row="{{ $row }}"
                                 data-col="A">
                                {{ $row }}
                            </div>
                        @else
                            {{-- Other cells: empty, but still have row + col --}}
                            <div class="cell"
                                 data-row="{{ $row }}"
                                 data-col="{{ $colLetter }}">
                            </div>
                        @endif
                    @endforeach
                @endfor

            </div>
        </div>
         {{-- RIGHT COLUMN — INFORMATION PANEL --}}
        <div class="slot-right">
            <h4>Information</h4>
            <div id="slot-input-area" class="mt-3" style="display:none;">
                <div class="input-group mb-3">
                    <input type="text" id="slot-item-input" class="form-control"
                        placeholder="Enter item" aria-label="Item" aria-describedby="button-addon2">
                    <button type="button" id="button-addon2"
                        class="btn btn-outline-secondary fadeIn animated bx bx-save">
                    </button>
                </div>
            </div>
            <p>Click any slot on the left grid.</p>

            <div class="slot-info-box">
                Selected Slot:
                <strong id="selected-slot">None</strong>
            </div>

            {{-- Items Table --}}
            <div class="mt-3">
                <table class="table table-sm table-bordered mb-0" id="slot-items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="width: 70px; text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="2" class="text-center text-muted">
                                No slot selected.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<style>
    .slot-grid {
        display: grid;
        grid-template-columns: repeat(8, 80px); /* 8 equal columns */
        grid-auto-rows: 60px; /* height of each row */
        border: 2px solid #333;
        width: max-content;
    }

    .cell {
        border: 1px solid #333;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #fff;
        font-size: 16px;
        color: #333;
    }

    .cell.header {
        background: #f2f2f2;
        font-weight: bold;
    }

    .cell:hover {
        background:#e3f2fd;
        cursor: pointer;
    }

    .slot-container {
        display: grid;
        grid-template-columns: 2fr 1fr; /* Left bigger, right smaller */
        gap: 20px;
        align-items: start;
    }

     /* INFO COLUMN */
    .slot-right {
        /* background: #fafafa; */
        /* border: 1px solid #ccc; */
        padding: 15px;
        min-height: 200px;
        border-radius: 4px;
        color: #fff;
    }

    .cell.selected {
        background: #ffecb3;   /* light highlight */
        outline: 2px solid #ffa000;
        outline-offset: -2px;
    }

    .action-icon:hover {
        background:#e3f2fd0e;
        cursor: pointer;
    }

</style>
    @section('js-custom')
        <script>

            let selectedCoord = null;
            let editingId = null;

            $(document).ready(function () {
                $('.slot-grid').on('click', '.cell', function () {
                    const row = $(this).data('row');
                    const col = $(this).data('col');

                    if (!row || !col) {
                        return;
                    }

                    const coord = row + col;
                    selectedCoord = coord;
                    editingId = null;


                    // Remove highlight from all cells, then highlight the clicked one
                    $('.slot-grid .cell').removeClass('selected');
                    $(this).addClass('selected');
                    $('#selected-slot').text(coord);

                    // Show input group
                    $('#slot-input-area').fadeIn(150);

                    // Optional: auto-fill hidden coordinate
                    $('#slot-item-input').val('');
                    $('#slot-item-input').focus();

                    loadSlotItems(coord);

                });

                // Click add button
                $('#button-addon2').on('click', function () {
                    const itemValue = $('#slot-item-input').val().trim();

                    if (!selectedCoord) {
                        alert('Please select a slot first.');
                        return;
                    }

                    if (!itemValue) {
                        alert('Please enter an item.');
                        return;
                    }

                    // If editingId is set -> UPDATE
                    if (editingId) {
                        $.ajax({
                            url: '{{ route('slot-locator.items.update', ':id') }}'.replace(':id', editingId),
                            method: 'PATCH',
                            data: {
                                _token: '{{ csrf_token() }}',
                                items: itemValue
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#slot-item-input').val('');
                                    editingId = null;
                                    loadSlotItems(selectedCoord);
                                } else {
                                    alert('Failed to update item.');
                                }
                            },
                            error: function () {
                                alert('Error updating item.');
                            }
                        });
                    } else {
                        // Otherwise -> ADD new
                        $.ajax({
                            url: '{{ route('slot-locator.items.store') }}',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                coordinates: selectedCoord,
                                items: itemValue
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('#slot-item-input').val('');
                                    loadSlotItems(selectedCoord);
                                } else {
                                    alert('Failed to save item.');
                                }
                            },
                            error: function () {
                                alert('Error saving item.');
                            }
                        });
                    }
                });

                $('#slot-items-table').on('click', '.bx-edit', function () {
                    const $tr = $(this).closest('tr');
                    const id = $tr.data('id');
                    const itemText = $tr.find('td').first().text().trim();

                    editingId = id;
                    $('#slot-item-input').val(itemText).focus();
                });

                $('#slot-items-table').on('click', '.bx-trash', function () {
                    const $tr = $(this).closest('tr');
                    const id = $tr.data('id');

                    if (!id) {
                        return;
                    }

                    if (!selectedCoord) {
                        alert('Please select a slot first.');
                        return;
                    }

                    const confirmed = confirm('Are you sure you want to delete this item?');
                    if (!confirmed) {
                        return;
                    }

                    $.ajax({
                        url: '{{ route('slot-locator.items.destroy', ':id') }}'.replace(':id', id),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function (response) {
                            if (response.success) {
                                // If we were editing this row, reset edit mode
                                if (editingId === id) {
                                    editingId = null;
                                    $('#slot-item-input').val('');
                                }

                                // Reload items for the current slot
                                loadSlotItems(selectedCoord);
                            } else {
                                alert('Failed to delete item.');
                            }
                        },
                        error: function () {
                            alert('Error deleting item.');
                        }
                    });
                });

            });


            function loadSlotItems(coord) {
                $.ajax({
                    url: '{{ route('slot-locator.items.get', ':coord') }}'.replace(':coord', coord),
                    method: 'GET',
                    success: function (response) {
                        const $tbody = $('#slot-items-table tbody');
                        $tbody.empty();

                        if (!response.items || response.items.length === 0) {
                            $tbody.append(
                                '<tr>' +
                                    '<td colspan="2" class="text-center text-muted">No items for this slot.</td>' +
                                '</tr>'
                            );
                            return;
                        }

                        response.items.forEach(function (slot) {
                            const rowHtml =
                                '<tr data-id="'+ slot.id +'">' +
                                    '<td>' + escapeHtml(slot.items) + '</td>' +
                                    '<td class="text-center font-22">' +
                                        '<i class="bx bx-edit action-icon text-primary"></i>' +
                                        '<i class="bx bx-trash action-icon text-danger"></i>' +
                                    '</td>' +
                                '</tr>';

                            $tbody.append(rowHtml);
                        });
                    },
                    error: function () {
                        const $tbody = $('#slot-items-table tbody');
                        $tbody.empty().append(
                            '<tr>' +
                                '<td colspan="2" class="text-center text-danger">Error loading items.</td>' +
                            '</tr>'
                        );
                    }
                });
            }

            // simple HTML escape helper
            function escapeHtml(text) {
                return $('<div/>').text(text).html();
            }

        </script>
    @endsection
@endsection
