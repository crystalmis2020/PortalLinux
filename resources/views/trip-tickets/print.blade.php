<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trip Ticket {{ $ticket->ticket_number ?: '#' . $ticket->id }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5px;
            line-height: 1.25;
            background: #f3f4f6;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            width: 210mm;
            max-width: calc(100vw - 24px);
            margin: 14px auto;
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

        .button-primary { color: #fff; background: #1d4ed8; }

        .document {
            width: 210mm;
            height: 148.5mm;
            min-height: 148.5mm;
            margin: 0 auto 18px;
            padding: 8mm 9mm;
            background: #fff;
            border: 1px solid #d1d5db;
        }

        .header {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 30mm;
            gap: 8mm;
            align-items: start;
            padding-bottom: 4mm;
            border-bottom: 2px solid #111827;
        }

        .title-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .title {
            margin: 0;
            font-size: 18px;
            line-height: 1;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .status {
            border: 1px solid #15803d;
            padding: 3px 7px;
            color: #15803d;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .subtitle {
            margin: 5px 0 0;
            color: #4b5563;
            font-size: 10px;
        }

        .qr-box {
            text-align: center;
        }

        .qr-box svg {
            display: block;
            width: 27mm;
            height: 27mm;
            margin: 0 auto;
        }

        .qr-label {
            margin-top: 2px;
            font-size: 7.5px;
            color: #374151;
            word-break: break-all;
        }

        .section { margin-top: 4mm; }

        .section-title {
            margin: 0 0 2mm;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 1mm;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 2.3mm 4mm;
        }

        .grid-two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .span-2 { grid-column: span 2; }
        .span-4 { grid-column: 1 / -1; }

        .field-label {
            color: #6b7280;
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .field-value {
            min-height: 14px;
            padding-top: 1px;
            font-weight: 700;
            border-bottom: 1px solid #e5e7eb;
            overflow-wrap: anywhere;
        }

        .field-block {
            min-height: 18px;
            max-height: 38px;
            padding: 3px 4px;
            border: 1px solid #e5e7eb;
            white-space: pre-wrap;
            overflow: hidden;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8mm;
            margin-top: 7mm;
        }

        .signature-line {
            min-height: 13mm;
            display: flex;
            align-items: end;
            justify-content: center;
            text-align: center;
            border-bottom: 1px solid #111827;
            font-weight: 700;
        }

        .signature-label {
            margin-top: 1.5mm;
            text-align: center;
            color: #4b5563;
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        @page { size: A4 portrait; margin: 0; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .document {
                width: 210mm;
                height: 148mm;
                min-height: 148mm;
                margin: 0;
                border: 0;
                page-break-after: avoid;
                overflow: hidden;
            }

            .header {
                grid-template-columns: minmax(0, 1fr) 22mm;
                gap: 4mm;
                padding-bottom: 2mm;
            }
            .qr-box svg { width: 20mm; height: 20mm; }
            .section { margin-top: 2mm; }
            .section-title { margin-bottom: 1mm; padding-bottom: 0.5mm; }
            .grid { gap: 1.2mm 3mm; }
            .field-value { min-height: 11px; }
            .field-block { min-height: 12px; max-height: 20px; padding: 1px 3px; }
            .signatures { gap: 6mm; margin-top: 3mm; }
            .signature-line { min-height: 8mm; }
            .signature-label { margin-top: 0.5mm; }
            .document > .section h2[style] { margin-bottom: 3px !important; }
            .document > .section div[style*="display: flex"] { gap: 6px !important; }
            .document > .section div[style*="min-height: 60px"] {
                min-height: 34px !important;
                padding: 4px !important;
            }
            .document > .section div[style*="margin-bottom: 12px"] { margin-bottom: 3px !important; }
            .document > .section div[style*="height: 20px"] { height: 10px !important; }

        }

        .odometer-row {
        display: grid;
        grid-template-columns: 28mm 1fr 1fr;
        gap: 5mm;
        align-items: end;
        }

        .odometer-title {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        }

        .odometer-field {
        display: flex;
        align-items: end;
        gap: 4px;
        }

        .odometer-field .field-label {
        white-space: nowrap;
        }

        .odometer-line {
        flex: 1;
        min-height: 15px;
        border-bottom: 1px solid #111827;
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
                <div class="title-row">
                    <h1 class="title">Trip Ticket</h1>
                    <div class="status">Approved</div>
                </div>
                <p class="subtitle">Support Portal official travel document</p>
                <div class="section">
                    <div class="grid grid-two">
                        <div>
                            <div class="field-label">Ticket No.</div>
                            <div class="field-value">{{ $ticket->ticket_number ?: 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="field-label">Approved</div>
                            <div class="field-value">{{ $ticket->approved_at?->format('M d, Y h:i A') ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="qr-box">
                {!! $qrSvg !!}
                <div class="qr-label">{{ $qrValue }}</div>
            </div>
        </header>

        <section class="section">
            <h2 class="section-title">Trip Details</h2>
            <div class="grid">
                <div class="span-2">
                    <div class="field-label">Requester</div>
                    <div class="field-value">{{ $ticket->requester?->full_name ?? 'N/A' }}</div>
                </div>
                <div class="span-2">
                    <div class="field-label">Department / Section</div>
                    <div class="field-value">{{ $ticket->department?->name ?? 'N/A' }} / {{ $ticket->section?->name ?? 'N/A' }}</div>
                </div>
                <div class="span-2">
                    <div class="field-label">Destination</div>
                    <div class="field-value">{{ $ticket->destination ?: 'N/A' }}</div>
                </div>
                <div class="span-2">
                    <div class="field-label">Vehicle</div>
                    <div class="field-value">{{ $ticket->vehicle_details ?: ($ticket->vehicle ? trim($ticket->vehicle->plate_number . ' - ' . $ticket->vehicle->description, ' -') : 'N/A') }}</div>
                </div>
                <div>
                    <div class="field-label">Driver</div>
                    <div class="field-value">{{ $ticket->driver_name ?: ($ticket->driver?->name ?? 'N/A') }}</div>
                </div>
                <div>
                    <div class="field-label">Contact</div>
                    <div class="field-value">{{ $ticket->contact_number ?: 'N/A' }}</div>
                </div>
                <div class="span-4">
                    <div class="field-label">Purpose</div>
                    <div class="field-block">{{ $ticket->purpose ?: 'N/A' }}</div>
                </div>
                <div class="span-4">
                    <div class="field-label">Passengers / Personnel</div>
                    <div class="field-block">{{ $ticket->passengers ?: 'N/A' }}</div>
                </div>
            </div>
        </section>

    <section class="section">
        <h2 class="section-title" style="text-align: center; margin-bottom: 10px;">
            Vehicle Odometer Record
        </h2>

        <div style="display: flex; gap: 12px;">

            <div style="flex: 1; border: 1px solid #000; padding: 10px; min-height: 60px;">
                <div style="font-weight: bold; margin-bottom: 12px;">Departure:</div>
                <div style="border-bottom: 1px solid #000; height: 20px;"></div>
            </div>

            <div style="flex: 1; border: 1px solid #000; padding: 10px; min-height: 60px;">
                <div style="font-weight: bold; margin-bottom: 12px;">Return:</div>
                <div style="border-bottom: 1px solid #000; height: 20px;"></div>
            </div>

        </div>
    </section>
</section>
    </main>
</body>
</html>
