<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $table = 'order_items';

    private function columnExists(string $col): bool {
        return Schema::hasColumn($this->table, $col);
    }

    private function setDecimalDefault(string $col, string $precision = '10,2', $default = 0): void {
        if (!$this->columnExists($col)) return;
        DB::statement(sprintf(
            "ALTER TABLE `%s` MODIFY `%s` DECIMAL(%s) NOT NULL DEFAULT %s",
            $this->table, $col, $precision, is_numeric($default) ? $default : 0
        ));
    }

    private function setIntDefault(string $col, int $default = 1): void {
        if (!$this->columnExists($col)) return;
        DB::statement(sprintf(
            "ALTER TABLE `%s` MODIFY `%s` INT NOT NULL DEFAULT %d",
            $this->table, $col, $default
        ));
    }

    private function setCharDefault(string $col, string $default = 'MXN', int $len = 3): void {
        if (!$this->columnExists($col)) return;
        DB::statement(sprintf(
            "ALTER TABLE `%s` MODIFY `%s` CHAR(%d) NOT NULL DEFAULT '%s'",
            $this->table, $col, $len, $default
        ));
    }

    public function up(): void
    {
        // Crea columnas que falten con defaults seguros
        Schema::table($this->table, function (Blueprint $tb) {
            if (!Schema::hasColumn($this->table, 'price'))    $tb->decimal('price', 10, 2)->default(0)->after('sku');
            if (!Schema::hasColumn($this->table, 'qty'))      $tb->integer('qty')->default(1)->after('price');
            if (!Schema::hasColumn($this->table, 'amount'))   $tb->decimal('amount', 10, 2)->default(0)->after('qty');
            if (!Schema::hasColumn($this->table, 'currency')) $tb->char('currency', 3)->default('MXN')->after('amount');
        });

        // Fuerza defaults (por si existían sin default o NULL)
        $this->setDecimalDefault('price',  '10,2', 0);
        $this->setIntDefault   ('qty', 1);
        $this->setDecimalDefault('amount', '10,2', 0);
        $this->setCharDefault  ('currency', 'MXN', 3);
    }

    public function down(): void
    {
        // No quitamos columnas ni defaults para no romper datos previos.
    }
};
