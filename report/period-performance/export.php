<?php
require_once __DIR__.'/bootstrap.php';
$data=ppData($bookId,$from,$endExclusive);
$paths=[__DIR__.'/../../vendor/autoload.php',__DIR__.'/../../../vendor/autoload.php'];
foreach($paths as $path){if(is_file($path)){require_once $path;break;}}
if(!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')){
    http_response_code(503);exit('PhpSpreadsheet unavailable. Check your Composer installation.');
}
$spreadsheet=new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$spreadsheet->removeSheetByIndex(0);
$definitions=[
    'Cash Movements'=>['cash',['kind'=>'Movement','amount'=>'Amount (UGX)','records'=>'Records']],
    'Categories'=>['category',['name'=>'Category','cash_in'=>'Credits','cash_out'=>'Debits','records'=>'Records']],
    'Items'=>['items',['name'=>'Item','units'=>'Unit','cash_in'=>'Credits','cash_out'=>'Debits','qty_sold'=>'Credit-side Qty','qty_purchased'=>'Debit-side Qty']],
    'Stock & Purchases'=>['stock',['name'=>'Item','units'=>'Unit','qty_in'=>'Stock In','qty_out'=>'Stock Out','purchase_cost'=>'Stock-in Cost','missing_cost_rows'=>'Missing Cost Rows']],
    'Customer Balances'=>['customers',['name'=>'Customer','opening'=>'Opening Debt','charges'=>'New Debits','credits'=>'Credits / Adjustments','closing'=>'Closing Debt']]
];
foreach($definitions as $title=>[$key,$columns]){
    $sheet=$spreadsheet->createSheet();$sheet->setTitle($title);
    $sheet->setCellValue('A1',$book->name.' — '.$title);
    $sheet->setCellValue('A2',$from.' to '.$to);
    $columnIndex=1;
    foreach($columns as $heading){$sheet->setCellValue([$columnIndex++,4],$heading);}
    $rowNumber=5;
    foreach($data[$key] as $row){$columnIndex=1;foreach($columns as $field=>$heading){
        $value=$row[$field]??'';$sheet->setCellValue([$columnIndex++,$rowNumber],is_numeric($value)?(float)$value:(string)$value);
    }$rowNumber++;}
    $lastColumn=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($columns));
    $sheet->getStyle('A4:'.$lastColumn.'4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle('A4:'.$lastColumn.'4')->getFill()->setFillType('solid')->getStartColor()->setRGB('176B51');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
    $sheet->freezePane('A5');
    $sheet->setAutoFilter('A4:'.$lastColumn.max(5,$rowNumber-1));
    foreach(range(1,count($columns)) as $i){$letter=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);$sheet->getColumnDimension($letter)->setAutoSize(true);}
}
// Native chart included in Excel for category movement.
$chartSheet=$spreadsheet->getSheetByName('Categories');
$lastRow=count($data['category'])+4;
if($lastRow>=5){
    $labels=[new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('String','Categories!$A$5:$A$'.$lastRow,null,$lastRow-4)];
    $values=[new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number','Categories!$B$5:$B$'.$lastRow,null,$lastRow-4),new \PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues('Number','Categories!$C$5:$C$'.$lastRow,null,$lastRow-4)];
    $series=new \PhpOffice\PhpSpreadsheet\Chart\DataSeries(\PhpOffice\PhpSpreadsheet\Chart\DataSeries::TYPE_BARCHART,\PhpOffice\PhpSpreadsheet\Chart\DataSeries::GROUPING_CLUSTERED,range(0,1),[], $labels,$values);
    $plot=new \PhpOffice\PhpSpreadsheet\Chart\PlotArea(null,[$series]);
    $chart=new \PhpOffice\PhpSpreadsheet\Chart\Chart('category_movement',new \PhpOffice\PhpSpreadsheet\Chart\Title('Category Credits and Debits'),null,$plot);
    $chart->setTopLeftPosition('H4');$chart->setBottomRightPosition('T20');$chartSheet->addChart($chart);
}
while(ob_get_level()>0)ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="KashHub_Period_'.preg_replace('/[^0-9-]/','',$from).'_'.preg_replace('/[^0-9-]/','',$to).'.xlsx"');
$writer=new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);$writer->setIncludeCharts(true);$writer->save('php://output');exit;
