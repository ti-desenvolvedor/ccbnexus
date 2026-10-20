<?php

use App\Models\BaptismListReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo 'dompdf_class='.(class_exists(Pdf::class) ? 'yes' : 'no').PHP_EOL;
echo 'vendor_dir='.(is_dir(base_path('vendor/barryvdh/laravel-dompdf')) ? 'yes' : 'no').PHP_EOL;
echo 'reports='.BaptismListReport::query()->count().PHP_EOL;
$last = BaptismListReport::query()->latest('id')->first();
if ($last) {
    echo 'last_id='.$last->id.' items='.$last->items()->count().' issue='.$last->issue_date.PHP_EOL;
    try {
        $pdf = Pdf::loadHTML('<h1>ok</h1>')->setPaper('a4');
        $bytes = strlen($pdf->output());
        echo 'pdf_bytes='.$bytes.PHP_EOL;
    } catch (Throwable $e) {
        echo 'pdf_error='.$e->getMessage().PHP_EOL;
    }
} else {
    echo 'last=none'.PHP_EOL;
}
