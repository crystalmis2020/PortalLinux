<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trip Ticket {{ $ticket->ticket_number ?: '#' . $ticket->id }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.45;
            background: #f3f4f6;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            max-width: 816px;
            margin: 18px auto;
        }

        .button {
            display: inline-block;
            border: 1px solid #1d4ed8;
            border-radius: 6px;
            padding: 8px 12px;
            color: #1d4ed8;
            text-decoration: none;
            background: #fff;
            cursor: pointer;
        }

        .button-primary {
            color: #fff;
            background: #1d4ed8;
        }

        .document {
            width: 816px;
            min-height: 1056px;
            margin: 0 auto 24px;
            padding: 42px;
            background: #fff;
            border: 1px solid #d1d5db;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 18px;
            border-bottom: 2px solid #111827;
        }

        .title {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .subtitle {
            margin: 4px 0 0;
            color: #4b5563;
        }

        .status {
            align-self: flex-start;
            border: 1px solid #15803d;
            padding: 6px 10px;
            color: #15803d;
            font-weight: 700;
            text-transform: uppercase;
        }

        .section {
            margin-top: 22px;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 6px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 24px;
        }

        .grid-three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .field-label {
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
        }

        .field-value {
            min-height: 20px;
            padding-top: 2px;
            font-weight: 700;
            border-bottom: 1px solid #e5e7eb;
        }

        .field-block {
            min-height: 58px;
            padding: 8px;
            border: 1px solid #e5e7eb;
            white-space: pre-wrap;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
            margin-top: 56px;
        }

        .signature-line {
            padding-top: 42px;
            text-align: center;
            border-bottom: 1px solid #111827;
        }

        .signature-label {
            margin-top: 6px;
            text-align: center;
            color: #4b5563;
            font-size: 11px;
            text-transform: uppercase;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .document {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                border: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="button" href="{{ route('trip-tickets.show', $ticket) }}">Back</a>
        <button class="button button-primary" type="button" onclick="window.print()">Print</button>
    </div>

    <main class="document">
        <header class="header">
            <div>
                <h1 class="title">Trip Ticket</h1>
                <p class="subtitle">Support Portal Official Approved Travel Document</p>
            </div>
            <div class="status">Approved</div>
        </header>

        <section class="section">
            <h2 class="section-title">Ticket Information</h2>
            <div class="grid grid-three">
                <div>
                    <div class="field-label">Ticket Number</div>
                    <div class="field-value">{{ $ticket->ticket_number ?: 'N/A' }}</div>
                </div>
                <div>
                    <div class="field-label">Request Date</div>
                    <div class="field-value">{{ $ticket->created_at?->format('M d, Y h:i A') }}</div>
                </div>
                <div>
                    <div class="field-label">Approved Date</div>
                    <div class="field-value">{{ $ticket->approved_at?->format('M d, Y h:i A') }}</div>
                </div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">Requester</h2>
            <div class="grid">
                <div>
                    <div class="field-label">Requested By</div>
                    <div class="field-value">{{ $ticket->requester?->full_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="field-label">Department / Section</div>
                    <div class="field-value">{{ $ticket->department?->name ?? 'N/A' }} / {{ $ticket->section?->name ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="field-label">Contact Number</div>
                    <div class="field-value">{{ $ticket->contact_number ?: 'N/A' }}</div>
                </div>
                <div>
                    <div class="field-label">Destination</div>
                    <div class="field-value">{{ $ticket->destination }}</div>
                </div>
                @if ($ticket->distance_km !== null && (float) $ticket->distance_km > 0)
                    <div>
                        <div class="field-label">Road KM from Maramag</div>
                        <div class="field-value">{{ number_format($ticket->distance_km, 2) }} km</div>
                    </div>
                @endif
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">Schedule</h2>
            <div class="grid">
                <div>
                    <div class="field-label">Requested Departure</div>
                    <div class="field-value">{{ $ticket->requested_start_datetime?->format('M d, Y') }}</div>
                </div>
                <div>
                    <div class="field-label">Requested Return</div>
                    <div class="field-value">{{ $ticket->requested_end_datetime?->format('M d, Y') }}</div>
                </div>
                <div>
                    <div class="field-label">Actual Departure</div>
                    <div class="field-value">{{ $ticket->actual_departure_datetime?->format('M d, Y') ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="field-label">Actual Return</div>
                    <div class="field-value">{{ $ticket->actual_return_datetime?->format('M d, Y') ?? 'N/A' }}</div>
                </div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">Trip Details</h2>
            <div class="grid">
                <div>
                    <div class="field-label">Vehicle</div>
                    <div class="field-value">{{ $ticket->vehicle_details ?: ($ticket->vehicle ? ($ticket->vehicle->plate_number . ' - ' . $ticket->vehicle->description) : 'N/A') }}</div>
                </div>
                <div>
                    <div class="field-label">Driver</div>
                    <div class="field-value">{{ $ticket->driver_name ?: ($ticket->driver?->name ?? 'N/A') }}</div>
                </div>
            </div>
            <div style="margin-top: 12px;">
                <div class="field-label">Purpose</div>
                <div class="field-block">{{ $ticket->purpose }}</div>
            </div>
            <div style="margin-top: 12px;">
                <div class="field-label">Passengers / Personnel</div>
                <div class="field-block">{{ $ticket->passengers ?: 'N/A' }}</div>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title">Approval</h2>
            <div class="grid">
                <div>
                    <div class="field-label">Encoded By</div>
                    <div class="field-value">{{ $ticket->encoder?->full_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="field-label">Approved By</div>
                    <div class="field-value">{{ $ticket->approver?->full_name ?? 'N/A' }}</div>
                </div>
            </div>
            <div style="margin-top: 12px;">
                <div class="field-label">Approval Remarks</div>
                <div class="field-block">{{ $ticket->approval_remarks ?: 'N/A' }}</div>
            </div>
        </section>

        <section class="signatures">
            <div>
                <div class="signature-line">{{ $ticket->requester?->full_name ?? '' }}</div>
                <div class="signature-label">Requested By</div>
            </div>
            <div>
                <div class="signature-line">{{ $ticket->driver_name ?: ($ticket->driver?->name ?? '') }}</div>
                <div class="signature-label">Driver</div>
            </div>
            <div>
                <div class="signature-line">{{ $ticket->approver?->full_name ?? '' }}</div>
                <div class="signature-label">Approved By</div>
            </div>
        </section>
    </main>
</body>
</html>
