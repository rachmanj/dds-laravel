# Invoice import — sample PDF test results

**Related**: Implementation plan [`INVOICE-FROM-DOCUMENT-IMPLEMENTATION-PLAN.md`](INVOICE-FROM-DOCUMENT-IMPLEMENTATION-PLAN.md), architecture overview [`architecture.md`](architecture.md) (section *Invoice creation from PDF/image*).

**Date**: 2026-03-27  
**Mechanism**: When embedded PDF text is empty (scanned PDFs), the app sends the file to OpenRouter using the [`file` content type](https://openrouter.ai/docs/guides/overview/multimodal/pdfs) with `plugins.file-parser.pdf.engine` = `mistral-ocr` (config: `OPEN_ROUTER_PDF_ENGINE`). Multi-page PDFs may be trimmed to **page 1** for that path when `OPEN_ROUTER_PDF_FIRST_PAGE_ONLY` is enabled.

## Files tested (user-provided)

| File | Supplier (extracted) | Invoice # | PO | Amount (IDR) | Invoice date | Notes |
|------|----------------------|------------|-----|--------------|--------------|--------|
| `trakindo 260200878_0001.pdf` | PT Trakindo Utama | 5311606724 | 260200878 | 621,980,269 | 2026-03-17 | Faktur 04.00.26.000-97434260 |
| `UT 260201474.pdf` | PT UNITED TRACTORS Tbk | 915276620-BP | 260201474 | 16,480,325 | 2026-03-16 | Faktur 04002600095826364 |
| `Yontomo 260201592.pdf` | PT. Yontomo Sukses Abadi | YSA/F/26030138 | 260201592 | 130,402,800 | 2026-03-17 | Faktur 04002600101856924 |

**Confidence** reported by the model: **0.95** for all three.

**Important**: Always **manually verify** totals, tax lines, and thousand separators before posting — LLM extraction can misread formatted numbers on scans.

## Environment

- OpenRouter PDF path used when `smalot/pdfparser` returns fewer than 80 characters of text (typical for image-only PDFs).
- Optional env: `OPEN_ROUTER_PDF_ENGINE` (`mistral-ocr` vs `cloudflare-ai`), `OPEN_ROUTER_PDF_TIMEOUT` (default 240s).
