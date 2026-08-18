{{-- Kích thước thẻ — một nguồn dùng chung cho xem trước và trang in --}}
<style>
    .bang-ten-spec {
        --bt-card-w: 85mm;
        --bt-card-h: 50mm;
        --bt-header-h: 10mm;
        --bt-body-h: 40mm;
        --bt-photo-w: 30mm;
        --bt-photo-h: 40mm;
        --bt-border: 0.4mm solid #000;
        --bt-border-inner: 0.25mm solid #000;
    }
    .bang-ten-grid {
        display: grid;
        grid-template-columns: repeat(2, var(--bt-card-w));
        gap: 4mm;
        justify-content: flex-start;
    }
    .bang-ten-card {
        width: var(--bt-card-w);
        height: var(--bt-card-h);
        box-sizing: border-box;
        border: var(--bt-border);
        background: #fff;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        break-inside: avoid;
        page-break-inside: avoid;
        font-family: "Times New Roman", Times, serif;
        font-style: normal;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .bang-ten-header {
        flex-shrink: 0;
        height: var(--bt-header-h);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 0.4mm 1.5mm;
        text-align: center;
        line-height: 1.18;
        border-bottom: var(--bt-border-inner);
        box-sizing: border-box;
    }
    .bang-ten-header-line {
        font-size: 10pt;
        font-weight: 400;
        text-transform: uppercase;
        color: #000;
        white-space: nowrap;
        letter-spacing: 0.01em;
    }
    .bang-ten-body {
        flex-shrink: 0;
        height: var(--bt-body-h);
        display: flex;
        flex-direction: row;
        align-items: stretch;
    }
    .bang-ten-photo-wrap {
        width: var(--bt-photo-w);
        height: var(--bt-photo-h);
        flex-shrink: 0;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-right: var(--bt-border-inner);
        box-sizing: border-box;
    }
    .bang-ten-photo-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .bang-ten-photo-placeholder {
        font-size: 7pt;
        color: #666;
        text-align: center;
        padding: 1mm;
        line-height: 1.2;
    }
    .bang-ten-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        text-align: center;
        padding: 1.5mm 2.5mm;
    }
    .bang-ten-title {
        font-size: 13pt;
        font-weight: 700;
        text-transform: uppercase;
        color: #000;
        line-height: 1.15;
        white-space: nowrap;
        letter-spacing: 0.02em;
    }
    .bang-ten-name {
        font-size: 14pt;
        font-weight: 700;
        text-transform: uppercase;
        color: #000;
        line-height: 1.2;
        word-break: break-word;
        max-width: 100%;
        padding: 0 0.5mm;
    }
    .bang-ten-name.is-long {
        font-size: 11.5pt;
        line-height: 1.15;
    }
    .bang-ten-hang {
        font-size: 14pt;
        font-weight: 400;
        color: #000;
        line-height: 1.15;
        white-space: nowrap;
    }
    .bang-ten-hang strong {
        font-weight: 700;
        text-transform: uppercase;
    }
</style>
