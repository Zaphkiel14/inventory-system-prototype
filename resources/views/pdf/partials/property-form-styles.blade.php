{{-- Shared dompdf styles for the RIS, ICS and PAR printouts. --}}
<style>
    @page { margin: 28px 32px 40px 32px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #000; }
    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; }

    .appendix { text-align: right; font-style: italic; font-size: 10px; }
    .revision { text-align: right; font-size: 7px; color: #444; }
    .letterhead-table { margin-top: 4px; }
    .letterhead-table td { vertical-align: middle; }
    .logo-cell { width: 15%; }
    .logo { height: 48px; width: auto; }
    .letterhead { text-align: center; }
    .letterhead .entity { font-size: 12px; font-weight: bold; text-transform: uppercase; }
    .letterhead .address { font-size: 9px; }
    .letterhead .section { font-size: 9px; font-weight: bold; margin-top: 2px; }
    .title { text-align: center; font-size: 13px; font-weight: bold; margin: 12px 0 10px 0; letter-spacing: 0.5px; }

    .fill { border-bottom: 1px solid #000; padding: 0 4px; }
    .meta td { padding: 2px 4px; }
    .label { white-space: nowrap; }

    table.grid { margin-top: 6px; }
    table.grid th, table.grid td { border: 1px solid #000; padding: 3px 4px; word-wrap: break-word; }
    table.grid th { font-weight: bold; text-align: center; vertical-align: middle; font-size: 8px; }
    table.grid thead { display: table-header-group; }
    table.grid tr { page-break-inside: avoid; }

    .c { text-align: center; }
    .r { text-align: right; white-space: nowrap; }
    .item { font-weight: bold; }
    .item-type { font-size: 8px; }
    .unit-line { font-size: 7px; color: #333; margin-top: 1px; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }
    .total td { font-weight: bold; }

    .keep-together { page-break-inside: avoid; }
    table.sign { margin-top: 0; }
    table.sign td { border: 1px solid #000; padding: 3px 4px; height: 14px; }
    table.sign .col-head { font-weight: bold; font-style: italic; }
    table.sign .row-label { width: 13%; white-space: nowrap; border-right: none; }
    table.sign .value { width: 37%; border-left: none; text-align: center; }
    table.sign .sig { height: 30px; }

    /* 'shared' signature layout (RIS): one label column, one value column per signatory */
    table.sign-shared .row-head { width: 14%; white-space: nowrap; }
    table.sign-shared .sig { height: 26px; }
</style>
