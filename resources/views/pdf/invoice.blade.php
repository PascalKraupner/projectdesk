<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>{{ $filename }}</title>
    <style>
        @font-face {
            font-family: 'Open Sans';
            font-style: normal;
            font-weight: 400;
            src: url('file://{{ $font_regular }}') format('truetype');
        }
        @font-face {
            font-family: 'Open Sans';
            font-style: normal;
            font-weight: 700;
            src: url('file://{{ $font_bold }}') format('truetype');
        }

        /* Margins sit on the text edges, so cells need no horizontal padding
           and right-aligned columns land exactly on their column edge. */
        @page {
            margin: 43.02pt 47.67pt 60pt 47.02pt;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            font-size: 9pt;
            color: #333;
            margin: 0;
        }

        /* Offsets are negative because dompdf places fixed elements against the
           content box, and the frame has to reach the paper edge. */
        .frame {
            position: fixed;
            top: -42.06pt;
            left: -47.02pt;
            width: 593.30pt;
            height: 840.4pt;
            border: 1pt solid #d8dbdf;
        }

        /* Sender, meta block and recipient overlap vertically, so each line is
           placed absolutely. The rest of the document flows normally. */
        .head { position: relative; height: 252.96pt; }
        .head > div { position: absolute; }

        .head .row { position: absolute; line-height: 1; white-space: nowrap; }
        .head .name { left: 10.38pt; font-weight: 700; font-size: 9pt; }
        .head .small { left: 10.38pt; font-size: 8pt; }
        .head .rcp { left: 10.38pt; font-size: 8pt; font-weight: 700; }
        .head .lbl { left: 283.45pt; font-size: 9pt; font-weight: 700; }
        .head .val { left: 425.18pt; font-size: 9pt; }

        h1 {
            font-size: 14pt;
            font-weight: 700;
            line-height: 14pt;
            margin: 0 0 20.18pt -3.72pt;
            padding: 0;
        }

        .service-period { margin: 0 0 10pt 0; }

        /* Reaches 4pt past the text on both sides to inset the header band.
           The outer cells trade 4pt of width for 4pt of padding, so the column
           edges stay put. */
        table.items {
            width: 509.23pt;
            margin-left: -4pt;
            border-collapse: collapse;
        }
        table.items th.desc, table.items td.desc { padding-left: 4pt; }
        table.items th.amount, table.items td.amount { padding-right: 4pt; }
        table.items th {
            font-weight: 700;
            background-color: #dddddd;
            line-height: 9.3pt;
            padding: 1.6pt 0 5.8pt 0;
            text-align: right;
            white-space: nowrap;
        }
        table.items th.desc, table.items td.desc { text-align: left; }
        table.items td {
            /* dompdf scales line-height in a table cell by ~1.49: this yields a
               13.85pt row pitch. */
            line-height: 9.28pt;
            padding: 0;
            text-align: right;
            white-space: nowrap;
        }
        table.items tbody tr:first-child td { padding-top: 2.1pt; }

        /* margin-top moves the box, and with it the border, without moving the
           text: the rule sits 11.8pt above the total. */
        table.total {
            width: 509.23pt;
            margin-top: 10.6pt;
            margin-left: -4pt;
            border-collapse: collapse;
        }
        table.total td.amount { padding-right: 4pt; }
        table.total td {
            font-weight: 700;
            font-size: 10pt;
            line-height: 10pt;
            border-top: 0.5pt solid #000;
            padding: 9.71pt 0 0 0;
            text-align: right;
            white-space: nowrap;
        }

        .note {
            position: fixed;
            left: 0;
            right: 0;
            top: 692.05pt;
            line-height: 9pt;
            text-align: center;
        }

        /* Both footer lines centre on 292.0, not the page centre 297.96. */
        .foot {
            position: fixed;
            left: 0;
            right: 0;
            top: 727pt;
            /* Wider than the table: line 1 starts left of the table edge. */
            margin-left: -4pt;
            padding-right: 7.25pt;
            text-align: center;
            font-size: 9pt;
        }
        .foot .line { line-height: 9.58pt; white-space: nowrap; }
        /* Spacer, not padding: dompdf ignores horizontal padding on inline
           elements. The real space in the markup must stay or words merge. */
        .foot .sp { display: inline-block; width: 11.26pt; }
        /* Narrower than the footer text and centred on the page. The negative
           top margin is offset by the bottom one so the text stays put. */
        .foot hr {
            border: none;
            border-top: 1pt solid #000;
            width: 479.5pt;
            margin: -4.9pt 0 11.9pt 14.58pt;
        }
        .foot b { font-weight: 700; }
    </style>
</head>
<body>

<div class="frame"></div>

<div class="head">
    <div class="row name" style="top: 4.78pt">{{ $issuer['name'] }}</div>
    <div class="row small" style="top: 18.84pt">{{ $issuer['contact_name'] }}</div>
    <div class="row small" style="top: 31.64pt">{{ $issuer_address_line }}</div>

    @foreach ($meta as $i => $row)
        <div class="row lbl" style="top: {{ 81.28 + $i * 16.875 }}pt">{{ $row['label'] }}</div>
        <div class="row val" style="top: {{ 81.28 + $i * 16.875 }}pt">{{ $row['value'] }}</div>
    @endforeach

    @foreach ($recipient_lines as $i => $line)
        <div class="row rcp" style="top: {{ 141.14 + $i * 12.5 }}pt">{{ $line }}</div>
    @endforeach
</div>

<h1>Rechnung</h1>

@if ($service_period)
    <div class="service-period">{{ $service_period }}</div>
@endif

<table class="items">
    {{-- Widths belong on the th: dompdf ignores <col style="width"> and would
         distribute the columns evenly instead. --}}
    <thead>
        <tr>
            <th class="desc" style="width: 202.52pt">Beschreibung</th>
            <th style="width: 49.89pt">Datum</th>
            <th style="width: 53.26pt">Menge</th>
            <th style="width: 55.35pt">Einheit</th>
            <th style="width: 79.24pt">Einzelpreis</th>
            <th class="amount" style="width: 60.97pt">Betrag</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td class="desc">{{ $item['description'] }}</td>
                <td>{{ $item['date'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ $item['unit'] }}</td>
                <td>{{ $item['unit_price'] }}</td>
                <td class="amount">{{ $item['amount'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="total">
    <tr>
        <td style="width: 444.26pt">Gesamtsumme</td>
        <td class="amount" style="width: 60.97pt">{{ $total }}</td>
    </tr>
</table>

<div class="note">{{ $small_business_note }}</div>

<div class="foot">
    <hr>
    <div class="line"><b>Adresse</b> {{ $issuer_address_line }}<span class="sp"></span> <b>Steuernummer</b> {{ $issuer['tax_number'] }}<span class="sp"></span> <b>E-Mail</b> {{ $issuer['email'] }}</div>
    <div class="line"><b>Bank</b> {{ $issuer['bank'] }}<span class="sp"></span> <b>SWIFT/BIC</b> {{ $issuer['bic'] }}<span class="sp"></span> <b>IBAN</b> {{ $issuer['iban'] }}</div>
</div>

</body>
</html>
