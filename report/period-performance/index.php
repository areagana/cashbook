<?php
require_once __DIR__.'/bootstrap.php';
$data=ppData($bookId,$from,$endExclusive);
$totals=['Cash In'=>0,'Cash Out'=>0];
foreach($data['cash'] as $r) $totals[$r['kind']]=(float)$r['amount'];
$customerTotal=0;
foreach($data['customers'] as $c) $customerTotal+=(float)$c['closing'];
pageHeaderLevel2('Period Performance Report');
?>
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div><h2>Period Performance &amp; Customer Balances</h2><p class="text-muted mb-0"><?=ppEsc($book->name);?> — cash, items, stock and receivables</p></div>
        <a class="btn btn-outline-secondary" href="../?bkid=<?=urlencode($encryptedBookId);?>">Back to Reports</a>
    </div>
    <form method="get" class="card card-body mb-4">
        <input type="hidden" name="bkid" value="<?=ppEsc($encryptedBookId);?>">
        <div class="row align-items-end">
            <div class="col-md-3"><label>From</label><input type="date" class="form-control" name="from" value="<?=ppEsc($from);?>" required></div>
            <div class="col-md-3"><label>To</label><input type="date" class="form-control" name="to" value="<?=ppEsc($to);?>" required></div>
            <div class="col-md-6 mt-3 mt-md-0"><button class="btn btn-primary">Apply filters</button>
                <a class="btn btn-success" href="export.php?<?=http_build_query(['bkid'=>$encryptedBookId,'from'=>$from,'to'=>$to]);?>">Export Excel</a></div>
        </div>
    </form>
    <div class="alert alert-info">Cash movement is not profit. Customer ledger credits can include payments, returns or other adjustments; they are not automatically treated as cash collections. Item-level cash movement is indicative, not an audited sales margin.</div>
    <div class="row mb-4">
        <?php foreach(['Cash In'=>$totals['Cash In'],'Cash Out'=>$totals['Cash Out'],'Net Cash Movement'=>$totals['Cash In']-$totals['Cash Out'],'Closing Customer Receivables'=>$customerTotal] as $label=>$value): ?>
            <div class="col-md-3 mb-2"><div class="card card-body"><small class="text-muted"><?=ppEsc($label);?></small><h4>UGX <?=ppAmount($value);?></h4></div></div>
        <?php endforeach; ?>
    </div>
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
            <h4 class="mb-1">Detailed Performance Analysis</h4>
            <small class="text-muted">Select a section to see its graph and detailed records.</small>
        </div>
        <div class="btn-group btn-group-sm" role="group" aria-label="Report section controls">
            <button type="button" class="btn btn-outline-primary" id="ppExpandAll">Expand all</button>
            <button type="button" class="btn btn-outline-secondary" id="ppCollapseAll">Collapse all</button>
        </div>
    </div>
    <?php
    /* =============================================================
       REPORT SECTIONS — PRESERVE THE EXISTING DATA AND COLUMNS
       ============================================================= */
    $sections = [
        'Categories' => ['category', [
            'name' => 'Category', 'cash_in' => 'Transaction Credits',
            'cash_out' => 'Transaction Debits', 'records' => 'Records'
        ]],
        'Items' => ['items', [
            'name' => 'Item', 'units' => 'Unit',
            'cash_in' => 'Transaction Credits', 'cash_out' => 'Transaction Debits',
            'qty_sold' => 'Credit-side Quantity', 'qty_purchased' => 'Debit-side Quantity'
        ]],
        'Stock movements and purchases' => ['stock', [
            'name' => 'Item', 'units' => 'Unit', 'qty_in' => 'Stock In',
            'qty_out' => 'Stock Out', 'purchase_cost' => 'Recorded Stock-in Cost',
            'missing_cost_rows' => 'Stock-in Rows Missing Cost'
        ]],
        'Customer receivables' => ['customers', [
            'name' => 'Customer', 'opening' => 'Opening Debt',
            'charges' => 'New Debits', 'credits' => 'Credits / Adjustments',
            'closing' => 'Closing Debt'
        ]]
    ];
    foreach ($sections as $heading => [$key, $columns]):
        $rows = $data[$key] ?? [];
    ?>
        <details class="card shadow-sm mb-3 pp-section" <?= $key === 'category' ? 'open' : ''; ?>>
            <summary class="card-header bg-white pp-section-header">
                <span class="d-flex align-items-center flex-wrap">
                    <i class="fa fa-chevron-right pp-chevron mr-2 text-primary" aria-hidden="true"></i>
                    <strong><?= ppEsc($heading); ?></strong>
                    <span class="badge badge-light border ml-2"><?= count($rows); ?> records</span>
                </span>
                <small class="text-muted">Click to <?= $key === 'category' ? 'collapse' : 'expand'; ?></small>
            </summary>
            <div class="card-body">
                <?php if ($rows): ?>
                    <div class="pp-chart-container mb-4">
                        <canvas id="ppChart-<?= ppEsc($key); ?>" role="img" aria-label="<?= ppEsc($heading); ?> performance graph"></canvas>
                    </div>
                    <div class="alert alert-warning py-2 pp-chart-warning d-none" id="ppWarning-<?= ppEsc($key); ?>"></div>
                <?php else: ?>
                    <p class="text-muted mb-3">No chart data for the selected period.</p>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm table-bordered mb-0">
                        <thead class="thead-light"><tr>
                            <?php foreach ($columns as $title): ?>
                                <th><?= ppEsc($title); ?></th>
                            <?php endforeach; ?>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <?php foreach ($columns as $column => $title): ?>
                                        <td><?= ppEsc(is_numeric($row[$column] ?? null)
                                            ? number_format((float) $row[$column], 2)
                                            : ($row[$column] ?? '')); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$rows): ?>
                                <tr><td colspan="<?= count($columns); ?>" class="text-center text-muted py-3">No records in selected period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    <?php endforeach; ?>
</div>
<style>
    .pp-section-header { cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
    .pp-section-header::-webkit-details-marker { display: none; }
    .pp-section-header::marker { content: ''; }
    .pp-chevron { transition: transform .2s ease; }
    .pp-section[open] .pp-chevron { transform: rotate(90deg); }
    .pp-chart-container { position: relative; width: 100%; height: 300px; max-height: 50vh; }
    .pp-chart-container canvas { width: 100% !important; height: 100% !important; }
    @media(max-width: 575px) { .pp-chart-container { height: 245px; } .pp-section-header small { display: none; } }
    @media print { .pp-section { break-inside: avoid; } .pp-chart-container { height: 220px; } }
</style>
<?php
/* ================================================================
   CHART DATA — JSON encoded safely from existing report results
   ================================================================ */
$chartPayload = [
    'category' => [
        'labels' => array_column($data['category'], 'name'),
        'datasets' => [
            ['label' => 'Transaction Credits', 'values' => array_map('floatval', array_column($data['category'], 'cash_in'))],
            ['label' => 'Transaction Debits', 'values' => array_map('floatval', array_column($data['category'], 'cash_out'))]
        ]
    ],
    'items' => [
        'labels' => array_column($data['items'], 'name'),
        'datasets' => [
            ['label' => 'Credit-side Quantity', 'values' => array_map('floatval', array_column($data['items'], 'qty_sold'))],
            ['label' => 'Debit-side Quantity', 'values' => array_map('floatval', array_column($data['items'], 'qty_purchased'))]
        ]
    ],
    'stock' => [
        'labels' => array_column($data['stock'], 'name'),
        'datasets' => [
            ['label' => 'Stock In', 'values' => array_map('floatval', array_column($data['stock'], 'qty_in'))],
            ['label' => 'Stock Out', 'values' => array_map('floatval', array_column($data['stock'], 'qty_out'))]
        ]
    ],
    'customers' => [
        'labels' => array_column($data['customers'], 'name'),
        'datasets' => [
            ['label' => 'Opening Debt', 'values' => array_map('floatval', array_column($data['customers'], 'opening'))],
            ['label' => 'Closing Debt', 'values' => array_map('floatval', array_column($data['customers'], 'closing'))]
        ]
    ]
];
?>
<script>
(function () {
    'use strict';
    const reportCharts = <?= json_encode($chartPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
    const chartInstances = {};
    const palette = ['#2874a6', '#e67e22', '#16a085', '#8e44ad'];

    function showWarning(key, message) {
        const warning = document.getElementById('ppWarning-' + key);
        if (warning) {
            warning.textContent = message;
            warning.classList.remove('d-none');
        }
    }

    function renderChart(key) {
        if (chartInstances[key]) {
            chartInstances[key].resize();
            return;
        }
        const canvas = document.getElementById('ppChart-' + key);
        if (!canvas) return;
        if (typeof window.Chart !== 'function') {
            showWarning(key, 'Charts could not load. Check your internet connection or install Chart.js locally. The table data remains available below.');
            return;
        }
        const payload = reportCharts[key];
        if (!payload || !payload.labels.length) return;
        try {
            chartInstances[key] = new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels: payload.labels,
                    datasets: payload.datasets.map(function (series, index) {
                        return {
                            label: series.label,
                            data: series.values,
                            backgroundColor: palette[index % palette.length],
                            borderRadius: 3,
                            maxBarThickness: 45
                        };
                    })
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        x: { ticks: { autoSkip: true, maxRotation: 45, minRotation: 0 } },
                        y: { beginAtZero: true, ticks: { callback: function (value) { return Number(value).toLocaleString('en-UG'); } } }
                    }
                }
            });
        } catch (error) {
            console.error('KashHub report chart failed:', key, error);
            showWarning(key, 'Unable to draw this graph. The table data is still available.');
        }
    }

    function initPage() {
        const sections = document.querySelectorAll('.pp-section');
        sections.forEach(function (section) {
            const key = section.querySelector('canvas')?.id.replace('ppChart-', '');
            section.addEventListener('toggle', function () {
                if (section.open && key) renderChart(key);
            });
            if (section.open && key) renderChart(key);
        });
        document.getElementById('ppExpandAll')?.addEventListener('click', function () {
            sections.forEach(function (section) { section.open = true; });
        });
        document.getElementById('ppCollapseAll')?.addEventListener('click', function () {
            sections.forEach(function (section) { section.open = false; });
        });
    }

    function loadChartLibrary() {
        if (typeof window.Chart === 'function') { initPage(); return; }
        const sources = [
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js'
        ];
        function attempt(index) {
            if (index >= sources.length) { initPage(); return; }
            const script = document.createElement('script');
            script.src = sources[index];
            script.onload = function () { typeof window.Chart === 'function' ? initPage() : attempt(index + 1); };
            script.onerror = function () { attempt(index + 1); };
            document.head.appendChild(script);
        }
        attempt(0);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadChartLibrary);
    } else {
        loadChartLibrary();
    }
})();
</script>
<?php if(function_exists('pageFooter')) pageFooterLevel2(); ?>
