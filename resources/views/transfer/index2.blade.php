@extends('layout.app')
@section('css-custom')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('content')
    <div class="row">
        <div class="col-xl-9">
            <div class="card-body">
                <button id="transfer-all">Transfer All</button>
                <div id="progress">0 out of {{ count($logs) }}</div>
                <table class="table mb-0" id="logs-table">
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Message</th>
                            <th>Is Transferred</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr data-id="{{ $log->id }}">
                                <td>{{ $log->id }}</td>
                                <td>{{ Str::limit($log->message, 50) }}</td>
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
        const total = {{ count($logs) }};

        function transferRow(row) {
            const id = row.data('id');

            return $.ajax({
                url: "{{ route('report_logs.back.transfer') }}",
                type: 'POST',
                data: { id },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function () {
                    row.find('.status').text('Yes');
                    row.remove();
                    transferred++;
                    $('#progress').text(`${transferred} out of ${total}`);
                },
                error: function () {
                    console.warn('Failed to transfer log ID:', id);
                }
            });
        }

        $('.transfer-btn').on('click', function () {
            const row = $(this).closest('tr');
            transferRow(row);
        });

        $('#transfer-all').on('click', async function () {
            const rows = $('#logs-table tbody tr').toArray();

            for (const row of rows) {
                await transferRow($(row));
            }
        });
    </script>

    @endsection

@endsection
