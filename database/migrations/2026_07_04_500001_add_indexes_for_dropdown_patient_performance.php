<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIndexesForDropdownPatientPerformance extends Migration
{
    private $indexes = [
        ['list_of_payment_medicine_groups', 'detail_medicine_group_check_up_result_id', 'lopmg_detail_medicine_group_check_up_result_id_idx'],
        ['list_of_payment_services', 'check_up_result_id', 'list_of_payment_services_check_up_result_id_index'],
        ['detail_service_patients', 'check_up_result_id', 'detail_service_patients_check_up_result_id_index'],
        ['detail_medicine_group_check_up_results', 'check_up_result_id', 'detail_medicine_group_check_up_results_check_up_result_id_index'],
        ['check_up_results', 'created_at', 'check_up_results_created_at_index'],
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->indexes as [$table, $column, $indexName]) {
            if ($this->indexExists($table, $indexName)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($column, $indexName) {
                $blueprint->index($column, $indexName);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->indexes as [$table, $column, $indexName]) {
            if (! $this->indexExists($table, $indexName)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropIndex($indexName);
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [DB::getDatabaseName(), $table, $indexName]
        );

        return count($result) > 0;
    }
}
