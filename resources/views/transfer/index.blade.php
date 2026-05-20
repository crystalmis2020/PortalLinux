@extends('layout.app')
@section('css-custom')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('content')
    <div class="row">
        <div class="col-xl-9">
            <div class="card-body">
                <button id="transfer-all">Transfer All</button>
                <div id="progress">0 out of {{ count($reports) }}</div>
                <table class="table mb-0" id="reports-table">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Issue</th>
                            <th>Is Transferred</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $report)
                            <tr data-id="{{ $report->id }}">
                                <td>{{ $report->id }}</td>
                                <td>{{ is_resource($report->issue) ? '[BLOB]' : Str::limit($report->issue, 50) }}</td>
                                <td class="status">No</td>
                                <td><button class="transfer-btn">Transfer</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @section('js-custom')
    <script>
        let transferred = 0;
        const total = {{ count($reports) }};

        function transferRow(row) {
            const id = row.data('id');

            return $.ajax({
                url: "{{ route('reports.back.transfer') }}",
                type: 'POST',
                data: { id },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function () {
                    row.find('.status').text('Yes');
                    row.remove(); // remove from DOM
                    transferred++;
                    $('#progress').text(`${transferred} out of ${total}`);
                },
                error: function () {
                    console.warn('Failed to transfer report ID:', id);
                }
            });
        }

        $('.transfer-btn').on('click', function () {
            const row = $(this).closest('tr');
            transferRow(row);
        });

        $('#transfer-all').on('click', async function () {
            const rows = $('#reports-table tbody tr').toArray();

            for (const row of rows) {
                await transferRow($(row));
            }
        });
    </script>

    @endsection

@endsection
