<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 20mm 16mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #18181b;
        }

        h1 { font-size: 16pt; font-weight: normal; margin: 0; }

        .subtitle { color: #71717a; margin-top: 2px; }

        .summary { width: 100%; margin: 18px 0 22px; border-collapse: collapse; }
        .summary td { padding: 6px 0; border-top: 1px solid #e4e4e7; border-bottom: 1px solid #e4e4e7; }
        .summary .label { color: #71717a; font-size: 8pt; text-transform: uppercase; }

        table.logs { width: 100%; border-collapse: collapse; }
        table.logs th {
            text-align: left;
            font-size: 8pt;
            text-transform: uppercase;
            color: #71717a;
            font-weight: normal;
            border-bottom: 1px solid #d4d4d8;
            padding: 5px 4px;
        }
        table.logs td { padding: 5px 4px; border-bottom: 1px solid #f4f4f5; vertical-align: top; }

        .num { text-align: right; white-space: nowrap; }
        .muted { color: #a1a1aa; }

        .group-title td {
            padding-top: 14px;
            font-weight: bold;
            border-bottom: 1px solid #d4d4d8;
        }
        .group-total td {
            border-bottom: none;
            border-top: 1px solid #d4d4d8;
            color: #52525b;
        }
        .grand-total td {
            border-top: 2px solid #18181b;
            border-bottom: none;
            padding-top: 8px;
            font-weight: bold;
        }
        .grand-amount td {
            border-top: none;
            border-bottom: none;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            color: #a1a1aa;
        }
        .footer .right { float: right; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="subtitle">
        Timesheet{{ $subtitle ? ' · '.$subtitle : '' }}
    </div>

    <table class="summary">
        <tr>
            <td class="label">Period</td>
            @if ($rate)
                <td class="label">Hourly rate</td>
            @endif
            <td class="label num">Hours</td>
            <td class="label num">Duration</td>
            @if ($total_amount)
                <td class="label num">Amount</td>
            @endif
        </tr>
        <tr>
            <td>{{ $period }}</td>
            @if ($rate)
                <td>{{ $rate }}</td>
            @endif
            <td class="num">{{ $total_hours }}</td>
            <td class="num">{{ $total_duration }}</td>
            @if ($total_amount)
                <td class="num">{{ $total_amount }}</td>
            @endif
        </tr>
    </table>

    @if (count($groups))
        <table class="logs">
            <thead>
                <tr>
                    <th style="width: 18%">Date</th>
                    <th style="width: 10%">Start</th>
                    <th>Note</th>
                    <th class="num" style="width: 12%">Hours</th>
                    <th class="num" style="width: 16%">Duration</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group)
                    @if ($group['title'])
                        <tr class="group-title">
                            <td colspan="3">{{ $group['title'] }}</td>
                            <td class="num">{{ $group['hours'] }}</td>
                            <td class="num">{{ $group['duration'] }}</td>
                        </tr>
                    @endif

                    @foreach ($group['logs'] as $log)
                        <tr>
                            <td>{{ $log['date'] }}</td>
                            <td>{{ $log['started'] }}</td>
                            <td>{{ $log['note'] ?: '—' }}</td>
                            <td class="num">{{ $log['hours'] }}</td>
                            <td class="num">{{ $log['duration'] }}</td>
                        </tr>
                    @endforeach

                    @if ($group['title'] && $group['amount'])
                        <tr class="group-total">
                            <td colspan="4" class="num">Subtotal</td>
                            <td class="num">{{ $group['amount'] }}</td>
                        </tr>
                    @endif
                @endforeach

                <tr class="grand-total">
                    <td colspan="3">Total</td>
                    <td class="num">{{ $total_hours }}</td>
                    <td class="num">{{ $total_duration }}</td>
                </tr>
                @if ($total_amount)
                    <tr class="grand-amount">
                        <td colspan="4" class="num">Amount</td>
                        <td class="num">{{ $total_amount }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <p class="muted">No time tracked in this period.</p>
    @endif

    <div class="footer">
        Generated {{ $generated_at }}
        <span class="right">Project Desk</span>
    </div>
</body>
</html>
